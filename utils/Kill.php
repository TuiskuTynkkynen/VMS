<?php


$KillsId = $_REQUEST["KillsId"];
$UID = $_REQUEST["UID"];

$mysqli = new mysqli("localhost", "root", "", "cardgame");
if($mysqli->connect_error) {exit('Could not connect');}

//Get PID
$sql = "SELECT playerid FROM players WHERE id = $UID";
if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
$result = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);
$PID = $result[0];

if ($KillsId !== "-1"){KillField($mysqli, $KillsId, $PID); }
else { DrawField($mysqli, $PID); }

$now = time();
$sql = "UPDATE gamestates SET lastupdated = $now WHERE id = 1";
if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}

function KillField($mysqli, $KillsId, $PID){
	$Card_String = $_REQUEST["Card"];
	$token = strtok($Card_String, ",");
	$Card[0] = $token;
	$token = strtok(",");
	$Card[1] = $token;
		
	$sql = "SELECT * FROM Table_field";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	$x = $mysqli->query($sql)->num_rows;
	
	$sql = "DELETE FROM table_player$PID WHERE hand0 = $Card[0] AND  hand1 = $Card[1]";
	if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error; }
	
	$sql = "INSERT INTO Table_field (id, card0, card1, state, killsid)
	VALUES ($x, $Card[0], $Card[1],  2, $KillsId)";
	if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error; }
	
	$sql = "SELECT * FROM Table_field WHERE id = $KillsId";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	$x = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);
	
	$sql = "UPDATE table_field SET state = 1 WHERE id = $KillsId";
	if ($mysqli->query($sql) === FALSE) { echo "Error deleting record: " . $sql . "<br>" . $mysqli->error; }
	echo "0";
}

function DrawField($mysqli, $PID){
	//changes ids to be in order -> 0,1,2...
	$sql = "SELECT id FROM table_player$PID";
	$result = $mysqli->query($sql);
	for ($x = 0; $x < $result->num_rows; $x++) {
		$row = $result->fetch_array(MYSQLI_NUM);
		if ($row[0] != $x){ 
		$sql = "UPDATE table_player$PID SET id=$x WHERE id=$row[0]";
		if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}
		}
	}
	
	$sql = "SELECT card0, card1 FROM Table_field WHERE state=0";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	$result = $mysqli->query($sql);
	
	$sql = "SELECT * FROM table_player$PID";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	$x = $mysqli->query($sql)->num_rows;
	
	for($i = 0; $i < $result->num_rows; $i++){
		$row = $result->fetch_array(MYSQLI_NUM);
		$y = $row[0];
		$z = $row[1];
		$sql = "INSERT INTO table_player$PID (id, hand0, hand1)
		VALUES ($x + $i, $y, $z)";
		if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}
	}

	$sql = "SELECT * FROM Table_field WHERE state = 0";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	$x = $mysqli->query($sql)->num_rows;

	if ($x !== 0){
		$sql = "SELECT playercount, chargerid FROM gamestates";
		if ($mysqli->query($sql) === FALSE) { echo "Error getting record: " . $mysqli->error; }
		
		$result = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);
		$CID = $result[1] + 1;
		echo ($CID >= $result[0]);
		if ($CID >= $result[0]) { $CID = 0;}
	} else { $CID = $PID;}

	$sql = "UPDATE gamestates SET ischargeturn=1, chargerid=$CID";
	if ($mysqli->query($sql) === FALSE) { echo "Error updating record: " . $mysqli->error; }

	$sql = "DELETE FROM table_field";
	if ($mysqli->query($sql) === FALSE) { echo "Error deleting record: " . $sql . "<br>" . $mysqli->error; }

	echo "1";
}

?>
