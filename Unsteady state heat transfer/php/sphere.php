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

	//transcendental function part of analytic solution
	function fx($theta,$Bi,$M) {
		$result=(atan((pi()*$M+$theta)/($Bi-1))+$theta);
		return $result;
	}

	//First derivative of transcendental function of analytic solution
	function fdashx($theta,$Bi,$M) {
		$result=(1/(((($theta + pi()*$M)*($theta + pi()*$M))/(($Bi - 1)*($Bi - 1)) + 1)*($Bi - 1)) + 1);
		return $result;
	}

 	//determine roots of transcendental function part of analytic solution 
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

	//Calculate roots of b*cot(b)+(1-Bi)=0
	$betam = SphereBm($Bi,$M);

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
			$Ycterm=(2*$Bi*(10000)*($betamm*$betamm+(($Bi-1)*($Bi-1)))/(($betamm*$betamm)*($betamm*$betamm+($Bi-1)*$Bi)))*sin($betamm)*sin($betamm*(1/10000))*exp(-($betamm*$betamm)*$Fon);
			$Ysterm=2*$Bi*($betamm*$betamm+($Bi-1)*($Bi-1))/(($betamm*$betamm)*($betamm*$betamm+($Bi-1)*$Bi))*sin($betamm)*sin($betamm)*exp(-($betamm*$betamm)*$Fon);
			$Yavterm=6*($Bi*$Bi)/($betamm*$betamm*($betamm*$betamm+$Bi*($Bi-1)))*exp(-($betamm*$betamm)*$Fon);
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
