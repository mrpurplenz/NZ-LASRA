<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Cooling of Hides and Skins</title>
</head>
<body>
<?php session_start();
	$array = unserialize(urldecode($_SESSION['serialized_data']));
	echo "<table border=\"1\">";
	echo "<tr>";
	echo "<th>Time [s]</th>";
	echo "<th>Centre temperature [&deg;C]</th>";
	echo "<th>Surface temperature [&deg;C]</th>";
	echo "<th>Mass average temperature [&deg;C]</th>";
	echo "</tr>";
//var_dump($array);
	foreach($array as $value=>$row) {
		echo "<tr>";
		foreach($row as $value2=>$row2){
			echo "<td>" . $row2 . "</td>";
		}
		echo "</tr>";
	}
	echo "</table>";
?>
</body>
</html>
