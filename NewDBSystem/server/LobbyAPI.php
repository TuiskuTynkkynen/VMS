<?php
	session_start();
	
	$config = parse_ini_file("config/serverconfig.ini");
	
	$servername = $config['databaseservername'];
	$dbusername = $config['databaseusername'];
	$dbpassword = $config['databasepassword'];

	//session id needs to be turned into double quoted string to work with MySQL
	$phpsessionid = '"' . session_id() . '"';

	$action = $_REQUEST["action"];
	
	switch ($action) {
		case "0":
			GetLobbies();
			break;
		case "1":
			GetLobbyInfo();
			break;
		case "2":
			CreateLobby(GetSessionInfo($phpsessionid, $servername, $dbusername, $dbpassword), $servername, $dbusername, $dbpassword);
			break;
		case "3":
			JoinLobby(GetSessionInfo($phpsessionid, $servername, $dbusername, $dbpassword), $servername, $dbusername, $dbpassword);
			break;
		case "4":
			$info = GetSessionInfo($phpsessionid, $servername, $dbusername, $dbpassword);
			$isadmin = IsAdmin($info[1], $servername, $dbusername, $dbpassword);
			LeaveLobby($info[1], $isadmin, $servername, $dbusername, $dbpassword, -1);
			break;
		case "5":
			$info = GetSessionInfo($phpsessionid, $servername, $dbusername, $dbpassword);
			$isadmin = IsAdmin($info[1], $servername, $dbusername, $dbpassword);
			DeleteLobby($isadmin);
			break;
		case "6":
			$info = GetSessionInfo($phpsessionid, $servername, $dbusername, $dbpassword);
			$isadmin = IsAdmin($info[1], $servername, $dbusername, $dbpassword);
			ChangeLobbySettings($isadmin, $servername, $dbusername, $dbpassword);
			break;
		case "7":
			$info = GetSessionInfo($phpsessionid, $servername, $dbusername, $dbpassword);
			$isadmin = IsAdmin($info[1], $servername, $dbusername, $dbpassword);
			StartGame($isadmin, $servername, $dbusername, $dbpassword);
			break;
		case "8":
			$info = GetSessionInfo($phpsessionid, $servername, $dbusername, $dbpassword);
			$isadmin = IsAdmin($info[1], $servername, $dbusername, $dbpassword);
			StopGame($isadmin, $servername, $dbusername, $dbpassword);
			break;
	}

	exit();
	
	function CreateLobby($sessioninfo, $servername, $dbusername, $dbpassword){
		$sessionid = $sessioninfo[1];
		$nickname = $sessioninfo[2];
		$now = time();
		$lobbyname = $_REQUEST["name"];
		
		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$stmt = $m_conn->prepare("SELECT Count(*) FROM lobbies WHERE lobbyname = ?");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $m_conn->error; }
		$stmt->bind_param("s", $lobbyname);
		$stmt->execute();
		$result = $stmt->get_result() -> fetch_array(MYSQLI_NUM);
		$stmt->close();
		
		if ($result[0] != 0){ //if a lobby with name alrady exists
			$m_conn->close();
			echo 2;
			exit();
		}

		$sql = "SELECT status, lobbyid FROM sessions WHERE id=$sessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
		
		if ($result[0] != 0){ //if already in another lobby
			$m_conn->close();
			echo 2;
			exit();
		}
		
		$lobbypassword = $_REQUEST["password"];

		if($lobbypassword != ""){
			$cost = 12;
			$hashedpassword = password_hash($lobbypassword, PASSWORD_BCRYPT, ["cost" => $cost]);
			$stmt = $m_conn->prepare("INSERT INTO lobbies
			(lobbyname, haspassword, lobbypassword, adminid, playercount, lastupdated)
			VALUES (?, 1, ?, $sessionid, 1, $now)");
			if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $m_conn->error; }
			$stmt->bind_param("ss", $lobbyname, $hashedpassword);
			$stmt->execute();
			$stmt->close();
		} else {
			$stmt = $m_conn->prepare("INSERT INTO lobbies
			(lobbyname, haspassword, adminid, playercount, lastupdated)
			VALUES (?, 0, $sessionid, 1, $now)");
			if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $m_conn->error; }
			$stmt->bind_param("s", $lobbyname);
			$stmt->execute();
			$stmt->close();
		}

		$stmt = $m_conn->prepare("SELECT Count(*), id FROM lobbies WHERE lobbyname = ?");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $m_conn->error; }
		$stmt->bind_param("s", $lobbyname);
		$stmt->execute();
		$result = $stmt->get_result() -> fetch_array(MYSQLI_NUM);
		$stmt->close();

		if ($result[0] == 0){
			$m_conn->close();
			echo 1;
			exit();
		}

		$lobbyid = $result[1];
		$sql = "UPDATE sessions SET status = 1, lobbyid = $lobbyid, last_seen = $now WHERE id=$sessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }

		$sql = "DROP DATABASE IF EXISTS lobby" . $lobbyid;
		if ($m_conn->query($sql) === FALSE) { echo "Error deleting database: " . $m_conn->error; }

		$sql = "CREATE DATABASE IF NOT EXISTS  lobby" . $lobbyid;
		if ($m_conn->query($sql) === FALSE) { die("Error creating database: " . $m_conn->error); }
	
		$m_conn->close();
		
		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }
		
		$sqlarray = array();
		$sqlarray[0] = "CREATE TABLE IF NOT EXISTS gamestates (
		  id int UNSIGNED NOT NULL,
		  isactive tinyint(1) NOT NULL,
		  playercount smallint UNSIGNED NOT NULL,
		  handsize smallint UNSIGNED NOT NULL,
		  ischargeturn tinyint(1) NOT NULL,
		  chargerid int UNSIGNED NOT NULL,
		  trumpcard0 smallint UNSIGNED NOT NULL,
		  trumpcard1 smallint UNSIGNED NOT NULL,
		  deckleft smallint UNSIGNED NOT NULL,
		  winnerid int UNSIGNED DEFAULT NULL,
		  lastupdated int UNSIGNED NOT NULL,
		  PRIMARY KEY (id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";

		$sqlarray[1] = "CREATE TABLE IF NOT EXISTS deck (
		  id int UNSIGNED NOT NULL,
		  deck0 smallint UNSIGNED NOT NULL,
		  deck1 smallint UNSIGNED NOT NULL,
		  PRIMARY KEY (id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";

		$sqlarray[2] = "CREATE TABLE IF NOT EXISTS field (
		  id int UNSIGNED NOT NULL,
		  card0 smallint UNSIGNED NOT NULL COMMENT 'suits 1-4',
		  card1 smallint UNSIGNED NOT NULL COMMENT 'card value: 2-13 as normal, 14 ace',
		  state tinyint UNSIGNED NOT NULL COMMENT '0 if alive, 1 if dead, 2 if kills another card',
		  killsid smallint UNSIGNED DEFAULT NULL COMMENT 'if state = 2, is on top of the card with  id=killsid',
		  PRIMARY KEY (id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";
		
		$sqlarray[3] = "CREATE TABLE IF NOT EXISTS hands (
		  id int UNSIGNED NOT NULL,
		  playerid int UNSIGNED NOT NULL,
		  card0 smallint UNSIGNED NOT NULL,
		  card1 smallint UNSIGNED NOT NULL,
		  PRIMARY KEY (id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";
		
		$sqlarray[4] = "CREATE TABLE IF NOT EXISTS players (
		  id int UNSIGNED NOT NULL,
		  playerid tinyint UNSIGNED DEFAULT NULL,
		  nickname tinytext NOT NULL,
		  canchangetrump tinyint(1) UNSIGNED DEFAULT '0',
		  PRIMARY KEY (id) USING BTREE
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";
		
		$sqlarray[5] = "CREATE TABLE IF NOT EXISTS settings (
		  id int UNSIGNED NOT NULL,
		  decksize smallint UNSIGNED NOT NULL DEFAULT '52',
		  suitcount smallint UNSIGNED NOT NULL DEFAULT '4',
		  suitsize smallint UNSIGNED NOT NULL DEFAULT '13',
		  handsize smallint UNSIGNED NOT NULL DEFAULT '6',
		  PRIMARY KEY (id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";
		
		$arr_length = count($sqlarray);
		for($i = 0; $i < $arr_length; $i++){
			if ($m_conn->query($sqlarray[$i]) === FALSE) { die("Error creating tables: " . $m_conn->error); }
		}
		
		$sql = "INSERT INTO settings
		(decksize, suitcount, suitsize, handsize)
		VALUES (52, 4, 13, 6)";
		if($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$sql = "INSERT INTO gamestates
		(id, isactive, playercount, handsize, ischargeturn, chargerid, trumpcard0, trumpcard1, deckleft, winnerid, lastupdated)
		VALUES (1, 0, 0, 0, 0, 0, 0, 0, 0, NULL, $now)";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }

		$stmt = $m_conn->prepare("INSERT INTO players (id, nickname) VALUES ($sessionid, ?)");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $m_conn->error; }
		$stmt->bind_param("s", $nickname);
		$stmt->execute();
		$stmt->close();

		$m_conn->close();
	}

	function JoinLobby($sessioninfo, $servername, $dbusername, $dbpassword){
		$sessionid = $sessioninfo[1];
		$nickname = $sessioninfo[2];
		$now = time();
		$lobbyid = $_REQUEST["id"];
		
		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }
		
		$sql = "SELECT status, lobbyid FROM sessions WHERE id=$sessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
		
		if ($result[0] != 0 && $result[1] != $lobbyid){ //if already in another lobby
			$isadmin = IsAdmin($m_conn, $sessionid, $servername, $dbusername, $dbpassword);
			LeaveLobby($sessionid, $isadmin, $servername, $dbusername, $dbpassword, $result[1]);
		}

		$sql = "SELECT haspassword, lobbypassword FROM lobbies WHERE id=$lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
		
		if ($result[0] != 0){ //lobby has password
			$lobbypassword = $_REQUEST["password"];
			$passwordhash = $result[1];

			if(password_verify($lobbypassword, $passwordhash)){
				$algorithm = PASSWORD_BCRYPT;
				$options = ['cost' => 12];
			
				if (password_needs_rehash($passwordhash, $algorithm, $options)) {
					$rehashedpassword = password_hash($passwordhash, $algorithm, $options);
		
					$stmt = $m_conn->prepare("UPDATE users SET password = ? WHERE id = $userid");
					if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $m_conn->error; }
					$stmt->bind_param("s", $rehashedpassword);
					$stmt->execute();
					$stmt->close();
				}
			} else {
				echo 3;
				$m_conn->close();
				exit();
			}
		}

		$m_conn->close();
		
		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }
		
		$sql = "SELECT Count(*) FROM players WHERE id=$sessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
	
		if ($result[0] != 0){
			echo 3;
			$m_conn->close();
			exit();
		}
		
		$stmt = $m_conn->prepare("INSERT INTO players (id, nickname) VALUES ($sessionid, ?)");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $m_conn->error; }
		$stmt->bind_param("s", $nickname);
		$stmt->execute();
		$stmt->close();

		$m_conn->close();
		
		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }
		
		$sql = "UPDATE sessions SET status = 1, lobbyid = $lobbyid, last_seen = $now WHERE id=$sessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$sql = "UPDATE lobbies SET playercount=playercount+1 WHERE id=$lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$sql = "UPDATE lobbies SET lastupdated = $now WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$m_conn->close();
	}

	function LeaveLobby($sessionid, $isadmin, $servername, $dbusername, $dbpassword, $altid){
		$now = time();
		$lobbyid = $altid;
		if ($altid == -1){
			$lobbyid = $_REQUEST["id"];
		}
		$newadminid = -1;

		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }
		
		$sql = "SELECT Count(*) FROM players";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
		$playercount = $result[0];
		
		$sql = "SELECT Count(*) FROM players WHERE id=$sessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
		
		if ($result[0] == 0){
			echo 4;
			exit();
		} else {
			$sql = "DELETE FROM players WHERE id=$sessionid";
			if ($m_conn->query($sql) === FALSE) { echo "Error deleting record: " . $m_conn->error; }
			
			if($isadmin == 1){
				$sql = "SELECT id FROM players LIMIT 1";
				if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
				$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
				
				$newadminid = $result[0];
			}
		}

		$m_conn->close();

		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }
		
		$sql = "UPDATE sessions SET status = 0, lobbyid = NULL, last_seen = $now WHERE id=$sessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$sql = "UPDATE lobbies SET playercount=$playercount-1 WHERE id=$lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		if ($playercount == 1){
			$sql = "DROP DATABASE IF EXISTS lobby" . $lobbyid;
			if ($m_conn->query($sql) === FALSE) { echo "Error deleting database: " . $m_conn->error; }

			$sql = "DELETE FROM lobbies WHERE id = $lobbyid";
			if ($m_conn->query($sql) === FALSE) { echo "Error deleting record: " . $m_conn->error; }
		}
		
		if ($newadminid != -1){
			$sql = "UPDATE lobbies SET adminid = $newadminid WHERE id = $lobbyid";
			if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		}
		
		$sql = "UPDATE lobbies SET lastupdated = $now WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$m_conn->close();
	}
	
	function DeleteLobby($isadmin){
		if ($isadmin == 0){
			echo 5;
			$m_conn->close();
			exit();
		}
		
		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }

		$lobbyid = $_REQUEST["id"];

		$sql = "DROP DATABASE IF EXISTS lobby" . $lobbyid;
		if ($m_conn->query($sql) === FALSE) { echo "Error deleting database: " . $m_conn->error; }
	
		$sql = "DELETE FROM lobbies WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error deleting record: " . $m_conn->error; }

		$sql = "UPDATE sessions SET status = 0, lobbyid = NULL WHERE lobbyid=$lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }	
		
		$m_conn->close();
	}
	
	function ChangeLobbySettings($isadmin, $servername, $dbusername, $dbpassword) {
		if ($isadmin == 0){
			echo 6;
			exit();
		}
	
		$lobbyid = $_REQUEST["id"];
		$deck_size = $_REQUEST['decksize'];
		$suitcount =  $_REQUEST['suitcount'];
		$suitsize = $_REQUEST['suitsize'];
		$handsize = $_REQUEST['handsize'];
		$now = time();
		
		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		
		$sql = "UPDATE settings SET decksize = $deck_size, suitcount = $suitcount, suitsize = $suitsize, handsize = $handsize";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$m_conn->close();
	
		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }
		
		$sql = "UPDATE lobbies SET lastupdated = $now WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$m_conn->close();
	}

	function StartGame($isadmin, $servername, $dbusername, $dbpassword) {
		if ($isadmin == 0){
			echo 7;
			exit();
		}
		
		$lobbyid = $_REQUEST["id"];		
		$now = time();

		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }
		
		//Gets players in lobby
		$sql = "SELECT id FROM sessions WHERE lobbyid = $lobbyid AND status > 0 ORDER BY last_seen DESC";
		$players = $m_conn->query($sql);
		$playercount = $players->num_rows;
		
		if ($playercount == 0){
			echo 7;
			$m_conn -> close();
			exit();
		}

		//Updates sessions to show players are ingame
		$sql = "UPDATE sessions SET status = 2, last_seen = $now WHERE lobbyid = $lobbyid AND status > 0";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		//Updates lobbies to show update
		$sql = "UPDATE lobbies SET lastupdated = $now WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }
		
		//Gets settings
		$sql = "SELECT decksize, suitcount, suitsize, handsize FROM settings";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
		
		$decksize = $result[0];
		$suitcount =  $result[1];
		$suitsize = $result[2];
		$handsize = $result[3];
		
		//Deletes old field, deck and hands
		$sql = "DELETE FROM field";
		if ($m_conn->query($sql) === FALSE) { echo "Error deleting record: " . $m_conn->error; }

		$sql = "DELETE FROM deck";
		if ($m_conn->query($sql) === FALSE) { echo "Error deleting record: " . $m_conn->error; }
		
		$sql = "DELETE FROM hands";
		if ($m_conn->query($sql) === FALSE) { echo "Error deleting record: " . $m_conn->error; }
		
		//Update player ids
		for ($i = 0; $i < $playercount; $i++) {
			$row = $players->fetch_array(MYSQLI_NUM);
			$id = $row[0];

			$sql = "UPDATE players SET playerid = $i WHERE id = $id";
			if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error . "<br>" . $sql . "<br>"; }
		}
		
		//Generates new sub decks if deck is smaller that it's supposed to be and merges into deck
		$x = $decksize / ($suitcount*$suitsize);
		$deck = array();
		for ($i = 0; $i < $x; $i++){
			$subdeck = array();
			$z = 0;

			for ($x = 0; $x < $suitcount; $x++){
			  for ($y = 0; $y < $suitsize; $y++){
				$subdeck[$z][0] = $x +1;
				$subdeck[$z][1] = $y +2;
				$z++;
			  }
			}

	  		$deck = array_merge_recursive($deck, $subdeck);
		}
		shuffle($deck);

		//Removes cards if deck is smaller that it's supposed to be
		$x = count($deck) - $decksize;
		for ($i = 0; $i < $x; $i++){
			array_pop($deck);
		}
	
		//Sets first card of deck as trumpcard
		$tcard0 = $deck[0][0];
		$tcard1 = $deck[0][1];
	
		$hands = array();
		$tcardchangeid = -1;
	
		//Adds last card of deck to player x's hand
		for ($i = 0; $i < $handsize; $i++) {
			$j = 0;
			while($j < $playercount && count($deck) > 0) {
  				$hands[$j][$i] = array_pop($deck);
				//Checks if card added to deck can be switch for trump card
				if ($hands[$j][$i][0] == $tcard0 && $hands[$j][$i][1] == "2"){ $tcardchangeid = $j;}
				$j++;
			}
		}

		//Inserts deck to database
		$x = count($deck);
		for ($i = 0; $i < $x; $i++) {
			$deck0 = $deck[$i][0];
			$deck1= $deck[$i][1];
			$sql = "INSERT INTO deck (id, deck0, deck1) VALUES ($i, $deck0, $deck1)";
			if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		}
		
		//Inserts hands to database
		for ($i = 0; $i < $playercount; $i++) {
			//Inserts cards into player i hand
			for ($j = 0; $j < count($hands[$i]); $j++) {
				$card0 = $hands[$i][$j][0];
				$card1 = $hands[$i][$j][1];
				$sql = "INSERT INTO hands (playerid, card0, card1) VALUES ($i, $card0, $card1)";
				if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error;}
			}
		}

		//If trump card can be changed updates player
		if ($tcardchangeid != -1){
			$sql = "UPDATE players SET canchangetrump = 1 WHERE playerid = $tcardchangeid";
			if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		}
		
		//Updates gamestates
		$deckleft = count($deck);
		$sql = "UPDATE gamestates
		SET isactive=1, playercount=$playercount, handsize=$handsize, ischargeturn=1, chargerid=0, trumpcard0=$tcard0, trumpcard1=$tcard1, deckleft = $deckleft, winnerid = NULL, lastupdated = $now";

		if ($m_conn->query($sql) === FALSE) { echo "Error updating record : " . $m_conn->error; }

		$m_conn->close();
	}
	
	function StopGame($isadmin, $servername, $dbusername, $dbpassword) {
		if ($isadmin == 0){
			echo 8;
			exit();
		}
	
		$lobbyid = $_REQUEST["id"];
		$now = time();
		
		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }

		$sql = "UPDATE gamestates
		SET isactive=0, playercount=0, handsize=0, ischargeturn=0, chargerid=0, trumpcard0=0, trumpcard1=0, deckleft = 0, winnerid = NULL, lastupdated = $now";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$m_conn->close();
		
		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }

		$sql = "UPDATE sessions SET status = 1 WHERE lobbyid = $lobbyid AND status=2";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$sql = "UPDATE lobbies SET lastupdated = $now WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$m_conn->close();
	}

	function GetSessionInfo($phpsessionid, $servername, $dbusername, $dbpassword){
		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }

		$sql = "SELECT Count(*), id, nickname FROM sessions WHERE php_session_id=$phpsessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
	
		if ($result[0] != 0){
			return $result;
		} else {
			echo -1;
			$m_conn->close();
			exit();
		}
		
		$m_conn->close();
	}

	function IsAdmin($sessionid, $servername, $dbusername, $dbpassword){
		$lobbyid = $_REQUEST["id"];
		
		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }

		$sql = "SELECT Count(*), adminid FROM lobbies WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
	
		$m_conn->close();

		if ($result[0] == 0){
			echo -1;
			exit();
		} else if ($result[1] != $sessionid){
			return 0;
		} else {
			return 1;
		}
	}
?>