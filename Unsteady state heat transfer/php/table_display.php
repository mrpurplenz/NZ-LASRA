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
	echo  "<td>Time [s]</td>";
	echo  "<td>Centre temperature [&deg;C]</td>";
	echo  "<td>Surface temperature [&deg;C]</td>";
	echo  "<td>Mass average temperature [&deg;C]</td>";
    echo "</tr>";
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
