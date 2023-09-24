<?php


$UID = $_REQUEST["UID"];

$mysqli = new mysqli("localhost", "root", "", "cardgame");
if($mysqli->connect_error) {exit('Could not connect');}

//Get PID
$sql = "SELECT playerid FROM players WHERE id = $UID";
if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
$result = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);
$PID = $result[0];

$sql = "SELECT id FROM Table_deck";
if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
$a = $mysqli->query($sql)->num_rows;

$sql = "SELECT * FROM Table_player$PID";
if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
$b = $mysqli->query($sql)->num_rows;

$sql = "SELECT handsize FROM gamestates";
if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
$result = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);
$Num = $result[0] - $b;

$sql = "SELECT id FROM table_player$PID";
$result = $mysqli->query($sql);
for ($x = 0; $x < $b; $x++) {
	$row = $result->fetch_array(MYSQLI_NUM);
	if ($row[0] != $x){ 
	$sql = "UPDATE table_player$PID SET id=$x WHERE id=$row[0]";
	if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}
	}
}

for ($x = 0; $x < $Num; $x++) {
	$a--;
	if ($a < 0) { break; }
	$sql = "SELECT deck0, deck1 FROM table_deck WHERE id=$a ";	
	if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}

	$row = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);
	$y = $row[0];
	$z= $row[1];
	echo $y .",". $z . "/";
	
	$temp = $b+$x;
	$sql = "INSERT INTO Table_player$PID (id, hand0, hand1)
	VALUES ($temp, $y, $z)";	
	if ($mysqli->query($sql) === FALSE) {
		echo "Error: " . $sql . "<br>" . $mysqli->error;
	} else {
		$sql = "DELETE FROM table_deck WHERE id=$a ";	
		if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}
	}

}

$sql = "SELECT id FROM table_deck";	
if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}
$deckleft = $mysqli->query($sql)->num_rows;

$sql = "UPDATE gamestates SET deckleft = $deckleft";
if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}
	

?>
