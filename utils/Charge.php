<?php

$isSupporting = $_REQUEST["Support"];
$Cards_String = $_REQUEST["Cards"];
$UID = $_REQUEST["UID"];
$ChargeTurn = $_REQUEST["ChargeTurn"];
$CID = $_REQUEST["CID"];

$mysqli = new mysqli("localhost", "root", "", "cardgame");
if($mysqli->connect_error) {exit('Could not connect');}

//Get PID
$sql = "SELECT playerid FROM players WHERE id = $UID";
if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
$result = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);
$PID = $result[0];

$sql = "SELECT ischargeturn, chargerid, playercount FROM gamestates";
if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
$result = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);


if($ChargeTurn != $result[0] | $CID != $result[1]){
	echo 1;
	exit();
}

$token = strtok($Cards_String, ",");
$i = 0;
	
while ($token !== false)
{
	if ($i % 2 == 0){ $Cards[$i / 2][0] = $token; }
	else {$Cards[$i / 2][1] = $token; }

	$i++;
	$token = strtok(",");
}

$tmp = $result[1];
if ($isSupporting == 0) { 
	$tmp++;
	if ($tmp >= $result[2]) { $tmp = 0; }
}

$sql = "SELECT id FROM table_player$tmp";
if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
$hand = $mysqli->query($sql)->num_rows;

$sql = "SELECT id FROM Table_field WHERE state = 0";
if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
$field = $mysqli->query($sql)->num_rows;

$total = $field + count($Cards);

if($hand < $total){
	echo 2;
	exit();
}


if ($isSupporting == 0){MainCharge($mysqli, $Cards); }
else { SupportingCharge($mysqli, $Cards); }

for ($x = 0; $x < count($Cards); $x++) {
	$y = $Cards[$x][0];
	$z= $Cards[$x][1];
	$sql = "DELETE FROM table_player$PID WHERE hand0=$y AND hand1=$z";
	if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}
}
$now = time();
$sql = "UPDATE gamestates SET lastupdated = $now WHERE id = 1";
if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}

echo 0;

function MainCharge($mysqli, $Cards){ echo "main";

	for ($x = 0; $x < count($Cards); $x++) { 
		
		$y = $Cards[$x][0];
		$z= $Cards[$x][1];
		$sql = "INSERT INTO Table_field (id, card0, card1, state)
		VALUES ($x, $y, $z, 0)";
		if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}
	}
	$sql = "SELECT playercount, chargerid FROM gamestates";
	if ($mysqli->query($sql) === FALSE) { echo "Error getting record: " . $mysqli->error; }
	
	$result = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);
	$CID = $result[1] + 1;
	if ($CID >= $result[0]) { $CID = 0;}
	
	$sql = "UPDATE gamestates SET ischargeturn=0, chargerid=$CID";
	if ($mysqli->query($sql) === FALSE) { echo "Error updating record: " . $mysqli->error; }
}

function SupportingCharge($mysqli, $Cards){ echo "support";
	$sql = "SELECT * FROM Table_field";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	$a = $mysqli->query($sql)->num_rows;
	
	for ($x = 0; $x < count($Cards); $x++) {
		
		$y = $Cards[$x][0];
		$z= $Cards[$x][1];
		$sql = "INSERT INTO Table_field (id, card0, card1, state)
		VALUES ($a + $x, $y, $z, 0)";
		if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}
	}
}
?>
