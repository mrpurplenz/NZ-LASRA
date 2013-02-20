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
