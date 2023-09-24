<?php
	$ip_server = $_SERVER['SERVER_ADDR'];
	$ip_client = $_SERVER['REMOTE_ADDR'];

	if ($ip_client != $ip_server){
        exit("Insufficient credentials");
	}  
	
	define("deck_size", $_POST['deck_size']);
	define("suits",  $_POST['suit_count']);
	define("suit_size", $_POST['suit_size']);
	define("hand_size", $_POST['hand_size']);
	
	//Connection to database
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "cardgame";

	$conn = new mysqli($servername, $username, $password, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }

	define("player_count", $conn->query("SELECT * FROM sessions WHERE status > 0")->num_rows);
	

	//Deletes old sessions, players, field and deck
	$now = time();
	$y = $now - 15*60;
	$sql = "DELETE FROM sessions WHERE last_seen < $y";
	if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }

	$sql = "DELETE FROM players";
	if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }

	$sql = "DELETE FROM Table_field";
	if ($conn->query($sql) === FALSE) { echo "Error deleting record: " . $conn->error; }

	$sql = "DELETE FROM Table_deck";
	if ($conn->query($sql) === FALSE) { echo "Error deleting record: " . $conn->error; }

	//Add new players to table and set sessions.status to ingame
	
	$sql = "SELECT id, nickname FROM sessions WHERE status > 0 ORDER BY last_seen DESC";
	$result = $conn->query($sql);
	$z = $result->num_rows;

	if  ($z == 0){
		$sql = "UPDATE gamestates SET isactive=0, lastupdated = $now";
		if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }
		exit("No players are in game or ready");
	}

	for ($i = 0; $i < $z; $i++) {
		$row = $result->fetch_array(MYSQLI_NUM);
		$id = $row[0];
		$nick = '"' . $row[1] . '"';
		$sql = "INSERT INTO players (id, playerid, nickname) VALUES ($id, $i, $nick)";
		if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }
		$sql = "UPDATE sessions SET status = 2, last_seen = $now";
		if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }
	}
	

	function GenerateSubDeck(){
		$z = 0;
		for ($x = 0; $x < suits; $x++){
		  for ($y = 0; $y < suit_size; $y++){
			$tempdeck[$z][0] = $x +1;
			$tempdeck[$z][1] = $y +2;
			$z++;
		  }
		}
		return $tempdeck;
	}
	
	//Generates new sub decks if deck is smaller that it's supposed to be and merges into deck
	$x = deck_size / (suits*suit_size);
	$deck = array();
	for ($i = 0; $i < $x; $i++){
		$tempdeck = GenerateSubDeck();
	  	$deck = array_merge_recursive($deck, $tempdeck);
	}
	shuffle($deck);

	//Removes cards if deck is smaller that it's supposed to be
	$x = count($deck) - deck_size;
	for ($i = 0; $i < $x; $i++){
		array_pop($deck);
	}
	
	//Sets first card of deck as trumpcard
	$tcard0 = $deck[0][0];
	$tcard1 = $deck[0][1];
	
	$hands = array();
	$tcardchange;
	
	//Adds last card of deck to player x's hand
	for ($x = 0; $x < hand_size; $x++) {
		$y = 0;
		while( $y < player_count && count($deck) > 0) {
  			$z = count($deck) -1;
  			$hands[$y][$x] = $deck[$z];
			//Checks if card added to deck can be switch for trump card
			if ($hands[$y][$x][0] == $tcard0 && $hands[$y][$x][1] == "2"){ $tcardchange = $y;}
  			array_pop($deck);
			$y++;
		}
	}

	//Inserts deck to database
	for ($x = 0; $x < count($deck); $x++) {
	
		$y = $deck[$x][0];
		$z= $deck[$x][1];
		$sql = "INSERT INTO Table_deck (id, deck0, deck1)
		VALUES ($x, $y, $z)";
		if ($conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $conn->error; }
	}

	//Adds hands to database
	for ($x = 0; $x < player_count; $x++) {
	
		//Creates table for player x hand
		$sql = "DROP TABLE IF EXISTS Table_player$x";
	  	if ($conn->query($sql) === FALSE) { echo "Error deleting record: " . $conn->error; }

		$sql = "CREATE TABLE Table_player$x (
		id INT(6) UNSIGNED PRIMARY KEY,
		hand0 INT(6) UNSIGNED,
		hand1 INT(6) UNSIGNED
		)";
		if ($conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $conn->error;	}
	
		//Inserts values into player x hand
	   for ($h = 0; $h < count($hands[$x]); $h++) {
	
		$y = $hands[$x][$h][0];
		$z= $hands[$x][$h][1];
		$sql = "INSERT INTO Table_player$x (id, hand0, hand1)
		VALUES ($h, $y, $z)";
		if ($conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $conn->error;}
	   }
	}

	//If trump card can be changed updates player
	if (isset($tcardchange)){
		$sql = "UPDATE players SET canchangetrump = 1 WHERE playerid = $tcardchange";
		if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }
	}
	
	//Updates gamestates
	$p = player_count;
	$h = hand_size;
	$decksize = count($deck);
	$sql = "UPDATE gamestates
	SET isactive=1, playercount=$p, handsize=$h, ischargeturn=1, chargerid=0, trumpcard0=$tcard0, trumpcard1=$tcard1, deckleft = $decksize, winnerid = NULL, lastupdated = $now
	WHERE id=1";

	if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }

	echo 'Initialized <br>';
	echo "Redirecting..";
?>
<script>
	setTimeout(() => {
		window.location.replace("CGSettings.php");
	}, "5000");
</script>
