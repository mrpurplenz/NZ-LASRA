import ij.*;
import ij.plugin.*;
import ij.gui.*;
import ij.process.*;
import static java.lang.Math.*;


/** This plugin does various calculations on two images or stacks.
This java plugin is a modification of the brightfeild correction plugin modified
as below.
Adapted to operate on the RGB planes independently by G. Landini 17/July/2004
The plugin was further modified by R. Edmonds 20/December/2011 in order to correct images for pincushion/barrel lens distortion
Make sure both images are RGB!
*/

public class Microscope_Correction implements PlugIn {

    static String title = "Mircoscope correction";
    static final int SCALE=0, ADD=1, SUBTRACT=2, MULTIPLY=3, DIVIDE=4;
    static String[] ops = {"Scale: i2 = i1 x k1 + k2", "Add: i2 = (i1+i2) x k1 + k2", "Subtract: i2 = (i1-i2) x k1 + k2",
        "Multiply: i2 = (i1*i2) x k1 + k2", "Divide: i2 = (i1/i2) x k1 + k2"};
    static int operation = SCALE;
    private double focallength = -1.2;
    static double k1 = 255;
    static double k2 = 0;
    static boolean createWindow = true;
    static boolean rgbPlanes = false;
    int[] wList;
    private String[] titles;
    int i1Index;
    int i2Index;
    int i3Index;
    ImagePlus i1;
    ImagePlus i2;
    ImagePlus i3;
    boolean replicate;

    public void run(String arg) {
        if (IJ.versionLessThan("1.27w"))
            return;
        wList = WindowManager.getIDList();
        if (wList==null || wList.length<3) {
            IJ.showMessage(title, "There must be at least three windows open");
            return;
        }
        titles = new String[wList.length];
        for (int i=0; i<wList.length; i++) {
            ImagePlus imp = WindowManager.getImage(wList[i]);
            if (imp!=null)
                titles[i] = imp.getTitle();
            else
                titles[i] = "";
        }

        if (!showDialog())
            return;

        long start = System.currentTimeMillis();
        boolean calibrated = i1.getCalibration().calibrated() || i2.getCalibration().calibrated();
        calibrated = i3.getCalibration().calibrated() || i2.getCalibration().calibrated();
       if (calibrated)
           createWindow = true;
       if (createWindow) {
           if (replicate)
               i2 = replicateImage(i2, calibrated, i1.getStackSize());
           else
              i2 = duplicateImage(i2, calibrated);
           if (i2==null)
               {IJ.showMessage(title, "Out of memory"); return;}
           i2.show();
       }
        calculate(i1, i2, i3, k1, k2);
        if (focallength != 1.0){

                //correction(i1,i2,i3,focallength);
                //Need to delete working i2 image
        }
        IJ.showStatus(IJ.d2s((System.currentTimeMillis()-start)/1000.0, 2)+" seconds");
    }

    public boolean showDialog() {
        GenericDialog gd = new GenericDialog(title);
        gd.addChoice("brightfield:", titles, titles[0]);
        gd.addChoice("darkfield:", titles, titles[1]);
        gd.addChoice("image:", titles, titles[2]);
        gd.addNumericField("focal length", focallength, 1);
        //gd.addChoice("Operation:", ops, ops[operation]);
        //gd.addNumericField("k1:", k1, 1);
        //gd.addNumericField("k2:", k2, 1);
        //gd.addCheckbox("RGB operations", rgbPlanes);
        //gd.addCheckbox("Create New Window", createWindow);
        gd.showDialog();
        if (gd.wasCanceled())
            return false;
        int i1Index = gd.getNextChoiceIndex();
        int i2Index = gd.getNextChoiceIndex();
        int i3Index = gd.getNextChoiceIndex();
        //operation = gd.getNextChoiceIndex();
        k1 = 255;//gd.getNextNumber();
        k2 = 1;//gd.getNextNumber();
        focallength=gd.getNextNumber();

        //rgbPlanes = gd.getNextBoolean();
        createWindow = true;
        i1 = WindowManager.getImage(wList[i1Index]);
        i2 = WindowManager.getImage(wList[i2Index]);
        i3 = WindowManager.getImage(wList[i3Index]);
	if ( i1.getBitDepth()==24 &&(i2.getBitDepth()==24 && i3.getBitDepth()==24))
		rgbPlanes = true;
        int d1 = i1.getStackSize();
        int d2 = i2.getStackSize();
        int d3 = i3.getStackSize();
        if (d2==1 && d1>1) {
            createWindow = true;
            replicate = true;
        }
        return true;
    }

    public void calculate(ImagePlus i1,ImagePlus i2, ImagePlus i3, double k1, double k2) {
        double v1, v2=0, r1, g1, b1, r2, g2, b2, r3, g3, b3,r4, g4, b4, rgb, numr, numg, numb, divr, divg, divb;
        double ir1, ig1, ib1, ir2, ig2, ib2,  rdouble, gdouble, bdouble;
		int iv1, iv2, iv3, iv4, r, g=0, b=0;
        int width  = i1.getWidth();
        int height = i1.getHeight();
        ImageProcessor ip1, ip2, ip3;
        int slices1 = i1.getStackSize();
        int slices2 = i2.getStackSize();
        int slices3 = i3.getStackSize();
        float[] ctable1 = i1.getCalibration().getCTable();
        float[] ctable2 = i2.getCalibration().getCTable();
        float[] ctable3 = i3.getCalibration().getCTable();
        ImageStack stack1 = i1.getStack();
        ImageStack stack2 = i2.getStack();
        ImageStack stack3 = i3.getStack();
        int currentSlice = i2.getCurrentSlice();
        double[][] imagearray = new double[width][height];
        double fY=0;
        double fX=0;
        int ifY=0;
        int ifX=0;
        double dy=0;
        double dx=0;

        for (int n=1; n<=slices2; n++) {
            ip1 = stack1.getProcessor(n<=slices1?n:slices1);
            ip2 = stack2.getProcessor(n);
            ip3 = stack3.getProcessor(n<=slices3?n:slices3);
            ip1.setCalibrationTable(ctable1);
            ip2.setCalibrationTable(ctable2);
            ip3.setCalibrationTable(ctable3);
			if (rgbPlanes == true) {

				for (int x=0; x<width; x++) {
					for (int y=0; y<height; y++) {
						iv1 = ip1.getPixel(x,y);
						iv2 = ip2.getPixel(x,y);
						iv3 = ip3.getPixel(x,y);
						r1 = (double) ((iv1 & 0xff0000)>>16);
						g1 = (double) ((iv1 & 0x00ff00)>>8);
						b1 = (double) ( iv1 & 0x0000ff);
						r2 = (double) ((iv2 & 0xff0000)>>16);
						g2 = (double) ((iv2 & 0x00ff00)>>8);
						b2 = (double) ( iv2 & 0x0000ff);
						r3 = (double) ((iv3 & 0xff0000)>>16);
						g3 = (double) ((iv3 & 0x00ff00)>>8);
						b3 = (double) ( iv3 & 0x0000ff);
						//switch (operation) {
						//	case SCALE: r2 = r1; g2 = g1; b2 = b1; break;
						//	case ADD: r2 += r1; g2 += g1; b2 += b1; break;
						//	case SUBTRACT: r2 = r1-r2; g2 = g1-g2; b2 = b1-b2; break;
						//	case MULTIPLY: r2 *= r1; g2 *= g1; b2 *= b1; break;
						//	case DIVIDE: r2 = r2!=0.0?r1/r2:0.0; g2 = g2!=0.0?g1/g2:0.0; b2 = b2!=0.0?b1/b2:0.0; break;
						//}

						//r2 = r2 * k1 + k2;
						//g2 = g2 * k1 + k2;
						//b2 = b2 * k1 + k2;
	                    //num=v3-v2;
	                    //div=v1-v2;
	                    //v2 = num/div*255;
						numr=(r3-r2);
						numg=(g3-g2);
						numb=(b3-b2);
						divr=r1-r2;
						divg=g1-g2;
						divb=b1-b2;
						r2=numr/divr*k1;
						g2=numg/divg*k1;
						b2=numb/divb*k1;
						r = (int) Math.floor((r2>255.0?255:(r2<0.0?0:r2))+.5);
						g = (int) Math.floor((g2>255.0?255:(g2<0.0?0:g2))+.5);
						b = (int) Math.floor((b2>255.0?255:(b2<0.0?0:b2))+.5);
						//r=(int) r3;
						//g=(int) g3;
						//b=(int) b3;
						imagearray[x][y]=((r & 0xff)<<16)+((g & 0xff)<<8)+(b & 0xff);
						//ip2.putPixel(x, y,(int)imagearray[x][y]);
						//ip2.putPixel(x, y,((r & 0xff)<<16)+((g & 0xff)<<8)+(b & 0xff));

					}
				}


			}
			else{
				for (int x=0; x<width; x++) {
					for (int y=0; y<height; y++) {
						v1 = ip1.getPixelValue(x,y);
						v2 = ip2.getPixelValue(x,y);
						switch (operation) {
							case SCALE: v2 = v1; break;
							case ADD: v2 += v1; break;
							case SUBTRACT: v2 = v1-v2; break;
							case MULTIPLY: v2 *= v1; break;
							case DIVIDE: v2 = v2!=0.0?v1/v2:0.0; break;
						}
						v2 = v2*k1 + k2;
						ip2.putPixelValue(x, y, v2);
						//imagearray[x][y]=(int)v2;
					}
				}
			}
        }


        double correctionarray[][][] = shiftarray(focallength,width,height);


	    for (int n=1; n<=slices2; n++) {
            ip1 = stack1.getProcessor(n<=slices1?n:slices1);
            ip2 = stack2.getProcessor(n);
            ip3 = stack3.getProcessor(n<=slices3?n:slices3);
            ip1.setCalibrationTable(ctable1);
            ip2.setCalibrationTable(ctable2);
            ip3.setCalibrationTable(ctable3);
			if (rgbPlanes == true) {
				for (int x=0; x<width-1; x++) {
					for (int y=0; y<height-1; y++) {



						//Grab mapping coordinates
						ifY=(int)correctionarray[x][y][0];
						ifX=(int)correctionarray[x][y][1];
						dy = correctionarray[x][y][2];
						dx = correctionarray[x][y][3];

						//Determine the four nearest pixels from the source

						//grab 24bit pixels
						iv1 = (int)imagearray[ifX][ifY];
						iv2 = (int)imagearray[ifX+1][ifY];
						iv3 = (int)imagearray[ifX][ifY+1];
						iv4 = (int)imagearray[ifX+1][ifY+1];

						//Convert 24bit to RGB
						r1 = (double) ((iv1 & 0xff0000)>>16);
						g1 = (double) ((iv1 & 0x00ff00)>>8);
						b1 = (double) ( iv1 & 0x0000ff);
						r2 = (double) ((iv2 & 0xff0000)>>16);
						g2 = (double) ((iv2 & 0x00ff00)>>8);
						b2 = (double) ( iv2 & 0x0000ff);
						r3 = (double) ((iv3 & 0xff0000)>>16);
						g3 = (double) ((iv3 & 0x00ff00)>>8);
						b3 = (double) ( iv3 & 0x0000ff);
						r4 = (double) ((iv4 & 0xff0000)>>16);
						g4 = (double) ((iv4 & 0x00ff00)>>8);
						b4 = (double) ( iv4 & 0x0000ff);

						//Interpolate x direction
						//Tops
						ir1=r1*(1-dy)+r3*dy;
						ig1=g1*(1-dy)+g3*dy;
						ib1=b1*(1-dy)+b3*dy;
						//Bottoms
						ir2=r2*(1-dy)+r4*dy;
						ig2=g2*(1-dy)+g4*dy;
						ib2=b2*(1-dy)+b4*dy;

						//Interpolate y direction
						rdouble=ir1*(1-dx)+ir2*dx;
						gdouble=ig1*(1-dx)+ig2*dx;
						bdouble=ib1*(1-dx)+ib2*dx;

						r = (int) Math.floor((rdouble>255.0?255:(rdouble<0.0?0:rdouble))+.5);
						g = (int) Math.floor((gdouble>255.0?255:(gdouble<0.0?0:gdouble))+.5);
						b = (int) Math.floor((bdouble>255.0?255:(bdouble<0.0?0:bdouble))+.5);
						//r = 55;//(int) Math.floor((rdouble>255.0?255:(rdouble<0.0?0:rdouble))+.5);
						//g = 55;//(int) Math.floor((gdouble>255.0?255:(gdouble<0.0?0:gdouble))+.5);
						//b = 55;//(int) Math.floor((bdouble>255.0?255:(bdouble<0.0?0:bdouble))+.5);







						rgb=((r & 0xff)<<16)+((g & 0xff)<<8)+(b & 0xff);
						//rgb=imagearray[x][y];
						ip2.putPixel(x, y,(int)rgb);
					}
				}
			    if (n==currentSlice) {
			    	i2.getProcessor().resetMinAndMax();
			    	i2.updateAndDraw();
			    }
			    IJ.showProgress((double)n/slices2);
			    IJ.showStatus(n+"/"+slices2);
			}
    	}
	}
    public double[][][] shiftarray(double focallength, int imwidth,int imheight){

            //int imwidth  = i2.getWidth();
            //int imheight = i2.getHeight();
            //double predy[][]=new double[imwidth][imheight];
            //double predx[][]=new double[imwidth][imheight];
            ////int preifY[][]=new int[imwidth][imheight];
            //int preifX[][]=new int[imwidth][imheight];

            //int preifY[][] = new int[imwidth][imheight];
            double Result[][][]=new double[imwidth][imheight][4];

            //ImageProcessor ip1, ip2, ip3;
            //ImageProcessor ip2, ip3;

            //int slices1 = i1.getStackSize();
            //int slices2 = i2.getStackSize();
            //int slices3 = i3.getStackSize();
            //float[] ctable1 = i1.getCalibration().getCTable();
            //float[] ctable2 = i2.getCalibration().getCTable();
            //float[] ctable3 = i3.getCalibration().getCTable();
            //ImageStack stack1 = i1.getStack();
            //ImageStack stack2 = i2.getStack();
            //ImageStack stack3 = i3.getStack();
            //int currentSlice = i2.getCurrentSlice();

            //Calculate values for correction
            double centerX = imwidth/2;
            double centerY = imheight/2;
            //Determine new picture center
            double ncenterX = centerX;
            double ncenterY= centerY;

            //Normalise the focal length values
            double pixelFL = focallength;//(400 - focallength/200*(400-20))*imwidth/160;

            //Calculate distance to Top dead center TDC
            double top_dead_center_radial_distance=ncenterY;
            double tdcrd = top_dead_center_radial_distance;

            //Calculate lens correction shift to TDC
            double top_dead_center_premapped_radial_distance = corrector(tdcrd,pixelFL);//pixelFL*Math.log(tdcrd/pixelFL+(1+tdcrd*tdcrd/ Math.sqrt(pixelFL*pixelFL)));
            double tdcprd = top_dead_center_premapped_radial_distance;

            //Caclulate multipier for TDC lenscorrection shift
            double top_dead_center_multiplier = tdcprd/tdcrd;
            double tdcm = top_dead_center_multiplier;


            double  ndcY = 0;
            double  ndcX = 0;
            double new_pixel_radial_distance = 0;
            double nprd = 0;
            double nangle = 0;
            //int quadrant = 0;
            double premapped_radial_distance =0;
            double prd=0;
            double radial_distance_multiplier=0;
            double rdm=0;
            double angle=0;
            double mapped_radial_distance=0;
            double mrd=0;
            double fYa=0;
            double fXa=0;
            double vectorx=0;
    		double vectory=0;
            double fY=0;
            double fX=0;
            int ifY=0;
            int ifX=0;
            double dy=0;
            double dx=0;


            //Calculate pixel shifts
            for (int x=1; x<imwidth; x++) {
    			for (int y=1; y<imheight; y++) {
    				//Do lens shift calculation
    				//Calculate cartesian shifts and radial distance from image center to pixel center
    				ndcY = (y-0.5)-ncenterY;
    				ndcX = (x-0.5)-ncenterX;
    				new_pixel_radial_distance=sqrt((abs(ndcY))*(abs(ndcY))+(abs(ndcX))*(abs(ndcX)));
    				nprd=new_pixel_radial_distance;

    				//set non zero shifts so no div-zero in angle calc
    				if (ndcY ==0) {
    					ndcY=4.94065645841246544e-324;
    				}

    				//Determine the angle from the top to the new pixel
    				nangle=atan(abs(ndcX))/abs(ndcY);

    				//Adjust angle for correct quandrant of picture
    				if (ndcX>0){
    					if (ndcY>0){
    						//quadrant=1;
    						angle=nangle;
    					}
    						else{
    							//quadrant=2;
    							angle=PI-nangle;
    					}
    				}
    				else{
    					if (ndcY<0){
    						//quadrant=3;
    						angle=PI+nangle;
    					}
    						else{
    							//quadrant=4;
    							angle=2*PI-nangle;
    					}
    				}
    				//Calculate the radial distance multiplier
    				premapped_radial_distance=corrector(nprd, pixelFL);//pixelFL*Math.log(nprd/pixelFL+(1+nprd*nprd/ Math.sqrt(pixelFL*pixelFL)));
    				prd=premapped_radial_distance;

    				//Create pincushion
    				radial_distance_multiplier=prd/nprd;
    				rdm=radial_distance_multiplier;

    				angle=nangle;

    				//Adjust radial distance zooming inside pincushion shape
    				mapped_radial_distance=nprd*rdm/tdcm;
    				mrd=mapped_radial_distance;
    				    				/////////////////////////////////////////////////
    				//////////////////////////////////////////////////
    				//reset correct to no correction
    				//mrd=nprd;
    				//////////////////////////////////////////////////
    				/////////////////////////////////////////////////

    				//Calculate cartesian position of lookup pixel for lens correction
    				//fYa=mrd*cos(angle);
    				//fXa=mrd*sin(angle);
    				fYa=mrd/nprd*ndcY;
    				fXa=mrd/nprd*ndcX;


    				//Adjust angle for correct quandrant of picture

    				vectorx=0;
    				vectory=0;

    				//Calculate vector shifts for lens correction
    				vectorx=fXa-ndcX;
    				vectory=fYa-ndcY;



    				//Calculate pixel position of lookup pixel
    				fY=(vectory+ndcY)+centerY;
    				fX=(vectorx+ndcX)+centerX;

    				//Round off lookup pixel to whole pixel position
    				ifY=(int) floor(fY);
    				ifX=(int) floor(fX);

    				//Calculate pixel shift from true
    				dy=fY-ifY;
    				dx=fX-ifX;

    				//Reset outside pixels to image edge
    				if (ifY>(imheight-2))
    				{
    					ifY=imheight-2;
    				}
    				if (ifX>(imwidth-2))
    				{
    					ifX=imwidth-2;
    				}
    				if (ifY<0)
    				{
    					ifY=0;
    				}
    				if (ifX<0)
    				{
    					ifX=0;
    				}

    				//Load pixel data into lens info arrays
    				//preifY[x][y] = ifY;
    				//preifX[x][y] = ifX;
    				//predy[x][y] = dy;
    				//predx[x][y] = dx;
    				Result[x][y][0] = (double)ifY;
    				Result[x][y][1] = (double)ifX;
    				Result[x][y][2] = (double)dy;
    				Result[x][y][3] = (double)dx;
    			}
            }

    	return Result;
    }
    public double corrector(double R1, double A){
    	double result;

    	result=R1*(1-(0.00000001)*A*R1*R1);
    	//result=R1;
    	return result;
    }
    public void correction(ImagePlus i1, ImagePlus i2, ImagePlus i3, double focallength) {
        double  v2=0, r1, g1, b1, r2, g2, b2, r3, g3, b3, r4, g4, b4,  rdouble, gdouble, bdouble;
		int iv1, iv2, iv3, iv4, r, g=0, b=0;
		double ir1, ig1, ib1, ir2, ig2, ib2;
        int imwidth  = i2.getWidth();
        int imheight = i2.getHeight();
        double predy[][]=new double[imwidth][imheight];
        double predx[][]=new double[imwidth][imheight];
        //int preifY[][]=new int[imwidth][imheight];
        int preifX[][]=new int[imwidth][imheight];

        int preifY[][] = new int[imwidth][imheight];


        ImageProcessor ip1, ip2, ip3;
        //ImageProcessor ip2, ip3;

        int slices1 = i1.getStackSize();
        int slices2 = i2.getStackSize();
        int slices3 = i3.getStackSize();
        float[] ctable1 = i1.getCalibration().getCTable();
        float[] ctable2 = i2.getCalibration().getCTable();
        float[] ctable3 = i3.getCalibration().getCTable();
        ImageStack stack1 = i1.getStack();
        ImageStack stack2 = i2.getStack();
        ImageStack stack3 = i3.getStack();
        int currentSlice = i2.getCurrentSlice();

        //Calculate values for correction
        double centerX = imwidth/2;
        double centerY = imheight/2;
        //Determine new picture center
        double ncenterX = centerX;
        double ncenterY= centerY;

        //Normalise the focal length values
        double pixelFL = (400 - focallength/200*(400-20))*imwidth/160;

        //Calculate distance to Top dead center TDC
        double top_dead_center_radial_distance=ncenterY;
        double tdcrd = top_dead_center_radial_distance;

        //Calculate lens correction shift to TDC
        double top_dead_center_premapped_radial_distance = pixelFL*Math.log(tdcrd/pixelFL+(1+tdcrd*tdcrd/ Math.sqrt(pixelFL*pixelFL)));
        double tdcprd = top_dead_center_premapped_radial_distance;

        //Caclulate multipier for TDC lenscorrection shift
        double top_dead_center_multiplier = tdcprd/tdcrd;
        double tdcm = top_dead_center_multiplier;


        double  ndcY = 0;
        double  ndcX = 0;
        double new_pixel_radial_distance = 0;
        double nprd = 0;
        double nangle = 0;
        int quadrant;
        double premapped_radial_distance =0;
        double prd=0;
        double radial_distance_multiplier=0;
        double rdm=0;
        double angle=0;
        double mapped_radial_distance=0;
        double mrd=0;
        double fYa=0;
        double fXa=0;
        double vectorx=0;
		double vectory=0;
        double fY=0;
        double fX=0;
        int ifY=0;
        int ifX=0;
        double dy=0;
        double dx=0;


        //Calculate pixel shifts
        for (int x=1; x<imwidth; x++) {
			for (int y=1; y<imheight; y++) {
				//Do lens shift calculation
				//Calculate cartesian shifts and radial distance from image center to pixel center
				ndcY = (y-0.5)-ncenterY;
				ndcX = (x-0.5)-ncenterX;
				new_pixel_radial_distance=sqrt((abs(ndcY))*(abs(ndcY))+(abs(ndcX))*(abs(ndcX)));
				nprd=new_pixel_radial_distance;

				//set non zero shifts so no div-zero in angle calc
				if (ndcY ==0) {
					ndcY=4.94065645841246544e-324;
				}

				//Determine the angle from the top to the new pixel
				nangle=atan(abs(ndcX))/abs(ndcY);

				//Adjust angle for correct quandrant of picture
				if (ndcX>0){
					if (ndcY>0){
						quadrant=1;
						//nangle=nangle;
					}
						else{
							quadrant=2;
							nangle=PI-nangle;
					}
				}
				else{
					if (ndcY<0){
						quadrant=3;
						nangle=PI+nangle;
					}
						else{
							quadrant=4;
							nangle=2*PI-nangle;
					}
				}
				//Calculate the radial distance multiplier
				premapped_radial_distance=pixelFL*log(nprd/pixelFL+sqrt(1+nprd*nprd/(pixelFL*pixelFL)));
				prd=premapped_radial_distance;

				//Create pincushion
				radial_distance_multiplier=prd/nprd;
				rdm=radial_distance_multiplier;
				angle=nangle;

				//Adjust radial distance zooming inside pincushion shape
				mapped_radial_distance=nprd*rdm/tdcm;
				mrd=mapped_radial_distance;

				//Calculate cartesian position of lookup pixel for lens correction
				fYa=mrd*cos(angle);
				fXa=mrd*sin(angle);

				vectorx=0;
				vectory=0;

				//Calculate vector shifts for lens correction
				vectorx=fXa-ndcX;
				vectory=fYa-ndcY;

				//Calculate pixel position of lookup pixel
				fY=(vectory+ndcY)+centerY;
				fX=(vectorx+ndcX)+centerX;

				//Round off lookup pixel to whole pixel position
				ifY=(int) floor(fY);
				ifX=(int) floor(fX);

				//Calculate pixel shift from true
				dy=fY-ifY;
				dx=fX-ifX;

				//Reset outside pixels to image edge
				if (ifY>(imheight-2))
				{
					ifY=imheight-2;
				}
				if (ifX>(imwidth-2))
				{
					ifX=imwidth-2;
				}
				if (ifY<0)
				{
					ifY=0;
				}
				if (ifX<0)
				{
					ifX=0;
				}

				//Load pixel data into lens info arrays
				preifY[x][y] = ifY;
				preifX[x][y] = ifX;
				predy[x][y] = dy;
				predx[x][y] = dx;
			}
        }


        for (int n=1; n<=slices2; n++) {
            ip1 = stack1.getProcessor(n<=slices1?n:slices1);
            ip2 = stack2.getProcessor(n);
            ip3 = stack3.getProcessor(n<=slices3?n:slices3);
            ip1.setCalibrationTable(ctable1);
            ip2.setCalibrationTable(ctable2);
            ip3.setCalibrationTable(ctable3);
			if (rgbPlanes == true) {
				for (int x=1; x<imwidth; x++) {
					for (int y=1; y<imheight; y++) {

						//Grab mapping coordinates
						ifY=preifY[x][y];
						ifX=preifX[x][y];
						dy = predy[x][y];
						dx = predx[x][y];

						//Determine the four nearest pixels from the source

						//grab 24bit pixels
						iv1 = ip2.getPixel(x,y);
						iv2 = ip2.getPixel(x+1,y);
						iv3 = ip2.getPixel(x,y+1);
						iv4 = ip2.getPixel(x+1,y+1);

						//Convert 24bit to RGB
						r1 = (double) ((iv1 & 0xff0000)>>16);
						g1 = (double) ((iv1 & 0x00ff00)>>8);
						b1 = (double) ( iv1 & 0x0000ff);
						r2 = (double) ((iv2 & 0xff0000)>>16);
						g2 = (double) ((iv2 & 0x00ff00)>>8);
						b2 = (double) ( iv2 & 0x0000ff);
						r3 = (double) ((iv3 & 0xff0000)>>16);
						g3 = (double) ((iv3 & 0x00ff00)>>8);
						b3 = (double) ( iv3 & 0x0000ff);
						r4 = (double) ((iv4 & 0xff0000)>>16);
						g4 = (double) ((iv4 & 0x00ff00)>>8);
						b4 = (double) ( iv4 & 0x0000ff);

						//Interpolate x direction
						//Tops
						ir1=r1*(1-dy)+r3*dy;
						ig1=g1*(1-dy)+g3*dy;
						ib1=b1*(1-dy)+b3*dy;
						//Bottoms
						ir2=r2*(1-dy)+r4*dy;
						ig2=g2*(1-dy)+g4*dy;
						ib2=b2*(1-dy)+b4*dy;

						//Interpolate y direction
						rdouble=ir1*(1-dx)+ir2*dx;
						gdouble=ig1*(1-dx)+ig2*dx;
						bdouble=ib1*(1-dx)+ib2*dx;





						//switch (operation) {
						//	case SCALE: r2 = r1; g2 = g1; b2 = b1; break;
						//	case ADD: r2 += r1; g2 += g1; b2 += b1; break;
						//	case SUBTRACT: r2 = r1-r2; g2 = g1-g2; b2 = b1-b2; break;
						//	case MULTIPLY: r2 *= r1; g2 *= g1; b2 *= b1; break;
						//	case DIVIDE: r2 = r2!=0.0?r1/r2:0.0; g2 = g2!=0.0?g1/g2:0.0; b2 = b2!=0.0?b1/b2:0.0; break;
						//}

						//r2 = r2 * k1 + k2;
						//g2 = g2 * k1 + k2;
						//b2 = b2 * k1 + k2;
	                    //num=v3-v2;
	                    //div=v1-v2;
	                    //v2 = num/div*255;
						//numr=(r3-r2);
						//numg=(g3-g2);
						//numb=(b3-b2);
						//divr=r1-r2;
						//divg=g1-g2;
						//divb=b1-b2;
						//r2=numr/divr*k1;
						//g2=numg/divg*k1;
						//b2=numb/divb*k1;
						r = (int) Math.floor((rdouble>255.0?255:(rdouble<0.0?0:rdouble))+.5);
						g = (int) Math.floor((gdouble>255.0?255:(gdouble<0.0?0:gdouble))+.5);
						b = (int) Math.floor((bdouble>255.0?255:(bdouble<0.0?0:bdouble))+.5);
						r=200;
						g=20;
						b=100;
						//r=(int) r3;
						//g=(int) g3;
						//b=(int) b3;
						ip3.putPixel(x, y,((r & 0xff)<<16)+((g & 0xff)<<8)+(b & 0xff));
					}
				}
			}
			else{
				for (int x=0; x<imwidth; x++) {
					for (int y=0; y<imheight; y++) {
						//v1 = ip1.getPixelValue(x,y);
						//v2 = ip2.getPixelValue(x,y);
						//switch (operation) {
						//	case SCALE: v2 = v1; break;
						//	case ADD: v2 += v1; break;
						//	case SUBTRACT: v2 = v1-v2; break;
						//	case MULTIPLY: v2 *= v1; break;
						//	case DIVIDE: v2 = v2!=0.0?v1/v2:0.0; break;
						//}
						v2 = v2*k1 + k2;
						//ip2.putPixelValue(x, y, v2);
					}
				}
			}

            if (n==currentSlice) {
                i3.getProcessor().resetMinAndMax();
                i3.updateAndDraw();
            }
            IJ.showProgress((double)n/slices2);
            IJ.showStatus(n+"/"+slices2);
        }
    }

   ImagePlus duplicateImage(ImagePlus img1, boolean calibrated) {
        ImageStack stack1 = img1.getStack();
        int width = stack1.getWidth();
        int height = stack1.getHeight();
        int n = stack1.getSize();
        ImageStack stack2 = img1.createEmptyStack();
        float[] ctable = img1.getCalibration().getCTable();
        try {
            for (int i=1; i<=n; i++) {
                ImageProcessor ip1 = stack1.getProcessor(i);
                ImageProcessor ip2 = ip1.duplicate();
                if (calibrated) {
                    ip2.setCalibrationTable(ctable);
                    ip2 = ip2.convertToFloat();
                }
                stack2.addSlice(stack1.getSliceLabel(i), ip2);
            }
        }
        catch(OutOfMemoryError e) {
            stack2.trim();
            stack2 = null;
            return null;
        }
        ImagePlus img2 =  new ImagePlus("Result", stack2);
        return img2;
    }

  ImagePlus replicateImage(ImagePlus img1, boolean calibrated, int n) {
        ImageProcessor ip1 = img1.getProcessor();
        int width = ip1.getWidth();
        int height = ip1.getHeight();
        ImageStack stack2 = img1.createEmptyStack();
        float[] ctable = img1.getCalibration().getCTable();
        try {
            for (int i=1; i<=n; i++) {
                ImageProcessor ip2 = ip1.duplicate();
                if (calibrated) {
                    ip2.setCalibrationTable(ctable);
                    ip2 = ip2.convertToFloat();
                }
                stack2.addSlice(null, ip2);
            }
        }
        catch(OutOfMemoryError e) {
            stack2.trim();
            stack2 = null;
            return null;
        }
        ImagePlus img2 =  new ImagePlus("Result", stack2);
        return img2;
    }

}

