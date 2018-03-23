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
$display_type = $_GET["display_type"];
$Tsteps = 1000;
if ($lamda){
	//Sec not avaiable to native php
	function sec($x) {
		$result=1/cos($x);
		return $result;
	}
	function Horner($arr,$v) {
		$z=0;
		$I=sizeof($arr);
		for ($i = 1; $i <= $I; $i++) {
			$z=$v*$z+$arr[$i-1];
		}
    	return $z;
	}
	function J0($x) {
		$W = 0.636619772;
		$a=0;
		$a1=0;
		$a2=0;
		$y=$x*$x;
		$b0_a1a = array(-184.9052456,77392.33017,-11214424.18,651619640.7,-13362590354,57568490574);
		$b0_a2a = array(1,267.8532712,59272.64853,9494680.718,1029532985,57568490411);
		$b0_a1b = array(2.093887211e-07,-2.073370639e-06,2.734510407e-05,-0.001098628627,1);
		$b0_a2b = array(-9.34935152e-08,7.621095161e-07,-6.911147651e-06,0.0001430488765,-0.01562499995);
		if ($x<8) {
			$a1 = Horner($b0_a1a, $y);
			$a2 = Horner($b0_a2a, $y);
        	$a = $a1 / $a2;
		} else {
			$xx=$x - 0.785398164;
			$y = 64 / $y;
			$a1 = Horner($b0_a1b, $y);
			$a2 = Horner($b0_a2b, $y);
			$a = pow(($W/$x),0.5)*(cos($xx)*$a1-sin($xx)*$a2*8/$x);
		}
		return $a;
	}
	function J1($x) {
		$W = 0.636619772;
		$a=0;
		$a1=0;
		$a2=0;
		$y=$x*$x;
		$xx = abs($x) - 2.356194491;
		$b1_a1a = array(-30.16036606,15704.4826,-2972611.439,242396853.1,-7895059235,72362614232);
		$b1_a2a = array(1,376.9991397,99447.43394,18583304.74,2300535178,144725228442);
		$b1_a1b = array(-2.40337019e-07,2.457520174e-06,-3.516396496e-05,0.00183105,1);
		$b1_a2b = array(1.05787412e-07,-8.8228987e-07,8.449199096e-06,-0.0002002690873,0.04687499995);
		if (abs($x)<8) {
			$a1 = $x*Horner($b1_a1a, $y);
			$a2 = Horner($b1_a2a, $y);
        	$a = $a1 / $a2;
		} else {

			$y = 64 / $y;
			$a1 = Horner($b1_a1b, $y);
			$a2 = Horner($b1_a2b, $y);
			$a = pow(($W/$x),0.5)*(cos($xx)*$a1-sin($xx)*$a2*8/$x);
		}
		return $a;
	}
	//transcendental function part of analytic solution
	function fx($theta,$Bi,$M) {
		$result=(atan((pi()*$M+$theta)/($Bi-1))+$theta);
		return $result;
	}
	//First derivative of transcendental function of analytic solution for sphere
	function fdashx($theta,$Bi,$M) {
		$result=(1/(((($theta + pi()*$M)*($theta + pi()*$M))/(($Bi - 1)*($Bi - 1)) + 1)*($Bi - 1)) + 1);
		return $result;
	}
	//J0J1
	function rootfun($beta,$Bi) {
		$result=($beta*J1($beta)-$Bi*J0($beta));
		return $result;
	}
	function rootfundash($beta,$Bi) {
			$result=$Bi*J1($beta) + J1($beta) + $beta*(J0($beta) - J1($beta)/$beta);
			return $result;
	}
	function ffzero($est1,$est2,$Bi){
		$i=0;
		$root=0;
		$rootnext=($est1+$est2)/2;
		while ($i < 1000):
			if ($root==$rootnext) {
				//Stop if solution error is less than operating precision
				$i=1000;
			}
			$root=$rootnext;
			//newton-Raphson formula
			$fxval=rootfun($root,$Bi);
			$fdashxval=rootfundash($root,$Bi);
			$rootnext=$root-$fxval/$fdashxval;
			if $rootnext<$est1 {
				$rootnext=$est1;
				}
			if $rootnext>$est2 {
				$rootnext=$est2;
				}
			$i++;
		endwhile;
		return $rootnext;
	}
	function BETAMJ0J1($Bi,$m,$BETAMlast){
		if ($m == 1){
			$est1=0;
			$est2=2.40485;
		} else {
			$est1=3+$BETAMlast;
			$est2=3.2+$BETAMlast;
			if ($m > 10){
				$est1=3+$BETAMlast;
				$est2=3.2+$BETAMlast;
			}

		}
		$Betam=ffzero($est1,$est2,$Bi);
		return $Betam;
	}

 	//determine roots of transcendental function part of analytic solution

 	function CylinderBm($Bi,$M) {
	 		$result=array();
			if ($Bi == -1){
				//Sphere legacy
				$m=1;
				$root=pi()/2;
				$result[]=$root;
				while ($m <= $M):
					$m++;
					$root=$root+pi();
					$result[]=$root;
				endwhile;
			} else {
				$m=1;
				$resultroot=BETAMJ0J1($Bi,$m);
				$result[]=$resultroot;
				while ($m <= $M):
					$m++;
					$rootlast=$resultroot;
					$resultroot=BETAMJ0J1($Bi,$m, $rootlast);
					$result[]=$resultroot;
				endwhile;
			}
			return $result;
	}
 	function SphereBm($Bi,$M) {
 		$result=array();
		if ($Bi == 1){
			//Solve special case of Bi=1
			$m=1;
			$root=pi()/2;
			$result[]=$root;
			while ($m <= $M):
				$m++;
				$root=$root+pi();
				$result[]=$root;
			endwhile;
		} else {
			$m=0;
			while ($m <= $M):
				$m++;
				//Note root no. solution shift for Bi<1 vs Bi>1
				if ($Bi < 1){
					$n=$m-1;
				} else {
					$n=$m;
				}
				$root=0;
				$rootnext=pi();
				$i=0;
				while ($i < 1000):
					if ($root==$rootnext) {
						//Stop if solution error is less than operating precision
						$i=1000;
					}
					$root=$rootnext;
					//newton-Raphson formula
					$fxval=fx($root,$Bi,$n);
					$fdashxval=fdashx($root,$Bi,$n);
					$rootnext=$root-$fxval/$fdashxval;
					$i++;
				endwhile;
				$resultroot=pi()*$n+$root;
				$result[]=$resultroot;
			endwhile;
		}
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
					$root2=atan($Bi/($n*pi()+$rootm));
					if ($root2 == $rootm) {
						$i=1000;
					} else {
						$rootm=$root2;
					}
					$i++;
				endwhile;
			$root=$n*pi()+$rootm;
			$result[]=$root;
			}
		}
		return $result;
	}
	//Get simulation time information.
	$Tinterval=$Simtime/$Tsteps;
	$Tspan=array();
	$Tspan[] = 0;
	for ($i = 1; $i <= $Tsteps; $i++) {
		$Tspan[] = $Tspan[$i-1]+$Tinterval;
	}
	$t=$Tspan;
	$N=count($t);
	$R=$L/2;
	//prepare fourrier number
	$Fo=array();
	foreach ($t as $time_point) {
		$Fo[] = $time_point*$lamda/($C*$R*$R);
	}
	$Bi=$h*$R/$lamda;
	$M=100;
	//Calculate cylinder roots
	$betam = CylinderBm($Bi,$M);
	//Prepare arrays of analytical solutions
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

			//$Ycterm=(2*$Bi*(10000)*($betamm*$betamm+(($Bi-1)*($Bi-1)))/(($betamm*$betamm)*($betamm*$betamm+($Bi-1)*$Bi)))*sin($betamm)*sin($betamm*(1/10000))*exp(-($betamm*$betamm)*$Fon);
			//$Ysterm=2*$Bi*($betamm*$betamm+($Bi-1)*($Bi-1))/(($betamm*$betamm)*($betamm*$betamm+($Bi-1)*$Bi))*sin($betamm)*sin($betamm)*exp(-($betamm*$betamm)*$Fon);
			//$Yavterm=6*($Bi*$Bi)/($betamm*$betamm*($betamm*$betamm+$Bi*($Bi-1)))*exp(-($betamm*$betamm)*$Fon);

			$Ycterm=(2*$Bi/(($betamm*$betamm+$Bi*$Bi).*J0($betamm))).*exp(-$betamm*$betamm*$Fon);
			$Ysterm=(2*$Bi./(($betamm*$betamm+$Bi*$Bi))).*exp(-$betamm*$betamm*$Fon);
			$Yavterm=4*$Bi*$Bi/($betamm*$betamm.*($betamm*$betamm+$Bi*$Bi)).*exp(-$betamm*$betamm*Fon);


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
 	$array[1][0] = "0";
	$array[1][1] = $Ti;
	$array[1][2] = $Ti;
	$array[1][3] = $Ti;
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
	//encode and package data for display php
	$_SESSION['serialized_data'] = urlencode(serialize($array));
	if($display_type == "table_display"){
		//to display results as table
		Header('Location: table_display.php');
	} elseif ($display_type == "graphical_display"){
		//to display results as graph
		Header('Location: graph_display.php');
	}
}
//var_dump($array);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Cooling of Hides and Skins</title>
</head>
<body>
	<form method="get" action=" ">
		<fieldset>
			<legend>Sphere</legend>
			<label for="Conductivity">Conductivity:</label><br />
			<input type="text" name="lamda" id="lamda"  size="35" value="0.5512" /> [W &deg;C<sup>-1</sup> m<sup>-1</sup>] <br />
			<label for="HeatCapacity">Volumetric Heat Capacity:</label><br />
			<input type="text" name="C" id="C" size="35" value="4200000" /> [J &deg;C<sup>-1</sup> m<sup>-3</sup>]<br />
			<label for="Thickness">Thickness:</label><br />
			<input type="text" name="L" id="L" size="35" value="0.02" /> [m]<br />
			<label for=" HeatTransfer">Heat transfer Coefficeint:</label><br />
			<input type="text" name="h" id="h" size="35" value="65" /> [W &deg;C<sup>-1</sup> m<sup>-2</sup>]<br />
			<label for="AmbientTemperature">Ambient Temperature:</label><br />
			<input type="text" name="Ta" id="Ta" size="35" value="41" /> [&deg;C]<br />
			<label for="InitalTemperature">Inital Temperature:</label><br />
			<input type="text" name="Ti" id="Ti" size="35" value="18" /> [&deg;C]<br />
			<label for="ime">Simulation Time:</label><br />
			<input type="text" name="Simtime" id=" Simtime " size="35" value="1000" /> [s]<br />
                        <label for="display_type">Display type:</label><br />
			<select name="display_type" id="display_type">
			  <option value="table_display">Table display</option>
			  <option value="graphical_display">Graphical display</option>
			</select>
			<br>
                        <input type="submit" value="submit" />
		</fieldset>
	</form>
</body>
</html>
