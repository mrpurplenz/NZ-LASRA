<?php session_start();

    // Prepare simulation systm inputs
    $lamda = $_GET["lamda"];
    $C = $_GET["C"];
    $L = $_GET["L"];
    $J = 40;
    $h = $_GET["h"];
    $Ta = $_GET["Ta"];
    $Ti = $_GET["Ti"];
    $Simtime = $_GET["Simtime"];
    $Tsteps = 1000;

if ($lamda){
    function sec($x) {
//`   //Function of sec which is not avaliable natively to php
        $result=1/cos($x);
        return $result;
    }

    function SlabBm($Bi,$M) {
        $result=array();
        $root1=pi()/4;
	$i = 1;
	while ($i <= 1000):
	    $root2=atan($Bi/$root1);
//echo "first estimate of primary root is $root2 <br>";
	    if ($root2 == $root1) {
		$i=1000;
	    } else {
		$root1=$root2;
	    }
	    $i++;
	endwhile;
        $result[]=$root1;
        $estc=0;
        if ($M > 1) {
            for ($m = 2; $m <= $M; $m++) {
                $n=$m-1;
                $rootm=$estc;
        	$i=1;
                while ($i <= 1000):
//echo "Bi is $Bi <br>";
//echo "m is $m <br>";
//echo "n is $n <br>";
//echo "current rootm is $rootm <br>";
//echo "first estimate of second root term is $root2<br>";
                    $root2=atan($Bi/($n*pi()+$rootm));
                        if ($root2 == $rootm) {
                            $i=1000;
                        } else {
                            $rootm=$root2;
                        }
                        $i++;
//echo "$i <br>";
                endwhile;
		$root=$n*pi()+$rootm;
                $result[]=$root;
		//echo "$root <br>";
            }
        }
        return $result;
  
  }

//%Get simulation time information.
    $Tinterval=$Simtime/$Tsteps;
    $Tspan=array();
    $Tspan[] = 0;
    for ($i = 1; $i <= $Tsteps; $i++) {
        $Tspan[] = $Tspan[$i-1]+$Tinterval;
    }
    $t=$Tspan;
    $N=count($t);
    $R=$L/2;

    $Fo=array();
    foreach ($t as $time_point) {
        $Fo[] = $time_point*$lamda/($C*$R*$R);
    }
    $Bi=$h*$R/$lamda;
    $M=100;
    $betam = SlabBm($Bi,$M);

    $Yc=array();
    $Ys=array();
    $Yav=array();
    $Yc[]=1;
    $Ys[]=1;
    $Yav[]=1;
    //Analytical solution for the third kind of boundary condition
    for ($n = 2; $n <= $N; $n++) {
        $Yctemp=0;
        $Ystemp=0;
        $Yavtemp=0;
        for ($m = 1; $m <= $M; $m++) {
	    $betamm=$betam[$m-1];
            $Fon=$Fo[$n-1];
	    $Ycterm=(2*$Bi*cos(0)*sec($betamm))/(($Bi*($Bi+1)+$betamm*$betamm))*(exp(-$betamm*$betamm*$Fon));
	    $Ysterm=2*$Bi*cos($betamm)*sec($betamm)/($Bi*($Bi+1)+$betamm*$betamm)*exp(-$betamm*$betamm*$Fon);
	    $Yavterm=2*$Bi*$Bi/($betamm*$betamm*($Bi*($Bi+1)+$betamm*$betamm))*exp(-$betamm*$betamm*$Fon);
            $Yctemp=$Yctemp+$Ycterm;
            $Ystemp=$Ystemp+$Ysterm;
            $Yavtemp=$Yavtemp+$Yavterm;
        }
        $Yc[]=$Yctemp;
        $Ys[]=$Ystemp;
        $Yav[]=$Yavtemp;
        $Tc=-$Yctemp*($Ta-$Ti)+($Ta);
        $Ts=-$Ystemp*($Ta-$Ti)+($Ta);
        $Tav=-$Yavtemp*($Ta-$Ti)+($Ta);
    }

    $array = Array();
//for ($n = 1; $n <= $N; $n++) {
//        $Tc=-$Yc[$n]*($Ta-$Ti)+($Ta);
//        $Ts=-$Ys[$n]*($Ta-$Ti)+($Ta);
//        $Tav=-$Yav[$n]*($Ta-$Ti)+($Ta);
//        $array[$n][0] = $Tspan[$n];
//        $array[$n][1] = $Tc;
//        $array[$n][2] = $Ts;
//        $array[$n][3] = $Tav;
//}
        $array[1][0] = "0";
        $array[1][1] = $Ti;
        $array[1][2] = $Ti;
        $array[1][3] = $Ti;
//$N1=$N+1;
for ($n = 2; $n <= $N; $n++) {
	$nm=$n-1;
        $Tc=-$Yc[$nm]*($Ta-$Ti)+($Ta);
        $Ts=-$Ys[$nm]*($Ta-$Ti)+($Ta);
        $Tav=-$Yav[$nm]*($Ta-$Ti)+($Ta);
	$array[$n][0] = $Tspan[$nm];
	$array[$n][1] = $Tc;
	$array[$n][2] = $Ts;
	$array[$n][3] = $Tav;
}
//echo "this is the slab analytical solution php";
//print_r($array);

$_SESSION['serialized_data'] = urlencode(serialize($array));

//to display results as table
Header('Location: table_display.php');

//to display results as graph
//Header('Location: display.php');

}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Cooling of Hides and Skins</title>
</head>
<body>
    <form action="" method="get"  name="slab">
        <p>Conductivity:</p><br />
        <input type="text" name="lamda" size="35" value="0.591" />[W &deg;C<sup>-1</sup> m<sup>-1</sup>]<br /><br />
        <p>Volumetric Heat Capacity:</p><br />
        <input type="text" name="C" size="35" value="4200000" />[J &deg;C<sup>-1</sup> m<sup>-3</sup>]<br /><br />
        <p>Thickness:</p><br />
        <input type="text" name="L" size="35" value="0.01" />[m]<br /><br />
        <p>Heat transfer Coefficeint:</p><br />
        <input type="text" name="h" size="35" value="600" />[W &deg;C<sup>-1</sup> m<sup>-2</sup>]<br /><br />
        <p>Ambient Temperature:</p><br />
        <input type="text" name="Ta" size="35" value="10" />[&deg;C]<br /><br />
        <p>Inital Temperature:</p><br />
        <input type="text" name="Ti" size="35" value="35.5" />[&deg;C]<br /><br />
        <p>Simulation Time:</p><br />
        <input type="text" name="Simtime" size="35" value="500" />[s]<br /><br />
	<input type="submit" value="submit" />
    </form>
</body>
</html>
