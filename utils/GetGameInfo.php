<?php

$UID = $_REQUEST["UID"];
$Ingame = $_REQUEST["Ingame"];

$mysqli = new mysqli("localhost", "root", "", "cardgame");
if($mysqli->connect_error) {
  exit('Could not connect');
}

//Get PID
$sql = "SELECT playerid FROM players WHERE id = $UID";
if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
$result = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);
$PID = $result[0];
if ($PID == null){
	echo 1;
	exit();
}
$sql = "SELECT isactive, playercount, ischargeturn, chargerid, trumpcard0, trumpcard1, deckleft
FROM gamestates";

if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }

$result = $mysqli->query($sql);
$row = $result->fetch_array(MYSQLI_NUM);

$ischargeturn = $row[2];
$cid = $row[3];
$nextcid = $cid +1;

if ($nextcid >= $row[1]){
	$nextcid = 0;
}

echo "<?xml version=".'"1.0"'." encoding=".'"UTF-8"'."?>";

echo "<info>";

echo "<gamestates>";

for($i = 0; $i < $mysqli->field_count; $i++){
$name = $result -> fetch_field() -> name;

echo "<" .$name. ">". $row[$i]. "</" .$name. ">";
}

echo "</gamestates>";

echo "<PID>";
echo $PID;
echo "</PID>";

$sql = "SELECT playerid, nickname FROM players WHERE playerid IS NOT NULL";
$result = $mysqli->query($sql);

$sql = "SELECT playercount FROM gamestates";
$playercount = $mysqli->query($sql)->fetch_array(MYSQLI_NUM)[0];

if($playercount == 1){
	$sql = "DELETE FROM table_player$PID";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
}

$sql = "SELECT hand0, hand1 FROM table_player$PID";
$hand = $mysqli->query($sql);

if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }

echo "<ingamecontainer>";
if ($hand->num_rows == 0 && $Ingame !== 0) { 
	$now = time();
	$sql = "UPDATE sessions SET status = 0, last_seen = $now WHERE id = $UID";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	
	$sql = "UPDATE players SET playerid = NULL WHERE playerid = $PID";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	
	$sql = "UPDATE players SET playerid = playerid-1 WHERE playerid > $PID";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	
	$sql = "DROP TABLE table_player$PID";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	
	$x = $result->num_rows;
	for ($i = $PID; $i < $x-1; $i++){
		$y = $i+1;
		$sql = "ALTER TABLE table_player$y RENAME TO table_player$i";
		if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	}

	if($x - 1 >= $PID){
		$sql = "UPDATE gamestates SET chargerid = 0";
		if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	}

	$sql = "UPDATE gamestates SET playercount = playercount-1 WHERE playercount != 0";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	
	$sql = "UPDATE gamestates SET isactive = 0 WHERE playercount = 0";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	
	$sql = "UPDATE gamestates SET winnerid = $UID WHERE winnerid IS NULL";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }

	$sql = "UPDATE gamestates SET lastupdated = $now";
	if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}

	echo "<ingame>";
	echo 0;
	echo "</ingame>";

	//get leaderboard id
	$sql = "SELECT lbid FROM sessions WHERE id=$UID";
	if ($mysqli->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $mysqli->error;}
	$lbid = $mysqli->query($sql)->fetch_array(MYSQLI_NUM)[0];
	
	//increase number of games played
	$sql = "UPDATE leaderboard SET games = games+1 WHERE id = $lbid";
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }

	echo "<winner>";
	$sql = "SELECT winnerid, isactive FROM gamestates";
	$x = $mysqli->query($sql)->fetch_array(MYSQLI_NUM);
	if($x[0] == $UID){
		//increase number of games won
		$sql = "UPDATE leaderboard SET wins = wins+1 WHERE id = $lbid";
		if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
		echo 0;
	} else if($x[1] != "0"){
		echo 1;
	} else {
		//increase number of games lost
		$sql = "UPDATE leaderboard SET losses = losses+1 WHERE id = $lbid";
		if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
		echo 2;
	}
	echo "</winner>";
}
echo "</ingamecontainer>";

echo "<players>";
for($i = 0; $i < $result->num_rows; $i++){

	$row = $result->fetch_array(MYSQLI_NUM);
	echo "<pid" . $i .">";
	echo $row[0];
	echo "</pid" . $i .">";
	
	echo "<nick" . $i .">";
	echo $row[1];
	echo "</nick" . $i .">";
}
echo "</players>";

echo "<opponent>";
	if($ischargeturn == 1){
		$sql = "SELECT id FROM table_player$nextcid";
	} else {
		$sql = "SELECT id FROM table_player$cid";
	}
	$opponent = $mysqli->query($sql)->num_rows;
	echo $opponent;
echo "</opponent>";



echo "<hand>";

for($i = 0; $i < $hand->num_rows; $i++) {
	$temp_array = $hand -> fetch_array(MYSQLI_NUM);

	echo "<card" .$i. ">";
	echo $temp_array[0];
	echo "</card" .$i. ">";
	
	echo "<card" .$i. ">";
	echo $temp_array[1];
	echo "</card" .$i. ">";
}

echo "</hand>";

echo "<fieldcontainer>";

$sql = "SELECT * FROM Table_field";
if ($mysqli->query($sql)->num_rows > 0){
	
	echo "field";
	echo "<field>";
	$sql = "SELECT card0, card1, state, killsid FROM Table_field";
	
	if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }
	
	$result = $mysqli->query($sql);

	for($i = 0; $i < $result->num_rows; $i++) {
	
	$tempresult = $mysqli->query($sql);
	$row = $result->fetch_array(MYSQLI_NUM);	
	if ($row[3] == "") {$row[3] = " ";}
	  for($j = 0; $j < $mysqli->field_count; $j++){
		$name = $tempresult -> fetch_field() -> name;
	
		echo "<" .$name. ">". $row[$j]. "</" .$name. ">";
	  }
	
	}

	echo "</field>";
}
echo "</fieldcontainer>";

echo "</info>";


?>
