<?php session_start();
//<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
//<html xmlns="http://www.w3.org/1999/xhtml">
//<head>
//    <title>Cooling of Hides and Skins</title>
//</head>
//<body>

require_once("GDgraph/gdgraph.php");
$array = unserialize(urldecode($_SESSION['serialized_data']));

foreach($array as $value=>$row) {
    $Time_array[] =  "$row[0]";
    $Center_array[] =  "$row[1]";
    $Surface_array[] = "$row[2]";
    $Average_array[] = "$row[3]";
}

$gdg = new GDGraph(500,300,"Temperature");

$arr = Array('Center Temperature' => $Center_array,
             'Surface Temperature' => $Surface_array,
             'Volume Average Temperature' => $Average_array
             );

$colors = Array('Center Temperature' => Array(255,0,0),
                'Surface Temperature' => Array(0,0,255),
                'Volume Average Temperature' => Array(0,255,0)
                );

$x_labels = $Time_array;
//need to split time up and only list every hundred?

$thicknesses = Array('Center Temperature' => 10,
                     'Surface Temperature' => 6,
                     'Volume Average Temperature' => 3
                     );

//Example 1
$gdg->line_graph($arr, $colors, $x_labels);

//Example 2 (uncomment this next line, and comment the past one and the next one)
//$gdg->line_graph($arr, $colors, $x_labels,"Trimesters","$", false, $thicknesses, 5);

//Example 3 (uncomment this next line, and comment the pasts one)
//$gdg->line_graph($arr, $colors, $x_labels,"Trimesters","$", false, $thicknesses, 5, 0, 2, -900, 900);


//</body>
//</html>
?>
