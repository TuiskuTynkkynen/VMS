<?php

$UID = $_REQUEST["UID"];

$mysqli = new mysqli("localhost", "root", "", "cardgame");
if($mysqli->connect_error) {exit('Could not connect');}

//Get PID
$sql = "SELECT playerid FROM players WHERE id = $UID";
if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
$result = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);
$PID = $result[0];

$sql = "SELECT trumpcard0, trumpcard1 FROM gamestates WHERE id=1";
if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}

$result = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);
$tcard0 = $result[0];
$tcard1 = $result[1];

$sql = "UPDATE table_player$PID SET hand1 = $tcard1 WHERE hand0=$tcard0 AND hand1=2";
if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}

$sql = "UPDATE gamestates SET trumpcard1 = 2 WHERE id = 1";
if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}

$sql = "UPDATE players SET canchangetrump = 0 WHERE playerid = $PID";
if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}

$now = time();
$sql = "UPDATE gamestates SET lastupdated = $now WHERE id = 1";
if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}

?>
