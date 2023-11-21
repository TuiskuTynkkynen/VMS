<?php
	session_start();
	
	$config = parse_ini_file("config/serverconfig.ini");
	
	$servername = $config['databaseservername'];
	$dbusername = $config['databaseusername'];
	$dbpassword = $config['databasepassword'];

	$dbname = "vms";
	$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }
	
	//session id needs to be turned into double quoted string to work with MySQL
	$phpsessionid = '"' . session_id() . '"';

	$action = $_REQUEST["action"];
	
	switch ($action) {
		case "0":
			GetLobbies($conn);
			$conn->close();
			break;
		case "1":
			GetLobbyInfo($conn);
			$conn->close();
			break;
		case "2":
			CreateLobby($conn, GetSessionInfo($conn, $phpsessionid), $servername, $dbusername, $dbpassword);
			break;
		case "3":
			JoinLobby(GetSessionInfo($conn, $phpsessionid), $servername, $dbusername, $dbpassword);
			$conn->close();
			break;
		case "4":
			$info = GetSessionInfo($conn, $phpsessionid);
			$isadmin = IsAdmin($conn, $info[1], $servername, $dbusername, $dbpassword);
			LeaveLobby($info[1], $isadmin, $servername, $dbusername, $dbpassword, -1);
			$conn->close();
			break;
		case "5":
			$info = GetSessionInfo($conn, $phpsessionid);
			$isadmin = IsAdmin($conn, $info[1], $servername, $dbusername, $dbpassword);
			DeleteLobby($conn, $isadmin);
			$conn->close();
			break;
		case "6":
			$info = GetSessionInfo($conn, $phpsessionid);
			$isadmin = IsAdmin($conn, $info[1], $servername, $dbusername, $dbpassword);
			ChangeLobbySettings($isadmin, $servername, $dbusername, $dbpassword);
			$conn->close();
			break;
	}
	exit();
	
	function CreateLobby($conn, $sessioninfo, $servername, $dbusername, $dbpassword){
		$sessionid = $sessioninfo[1];
		$nickname = $sessioninfo[2];
		$now = time();
		$lobbyname = $_REQUEST["name"];
		
		$stmt = $conn->prepare("SELECT Count(*) FROM lobbies WHERE lobbyname = ?");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("s", $lobbyname);
		$stmt->execute();
		$result = $stmt->get_result() -> fetch_array(MYSQLI_NUM);
		$stmt->close();
		
		if ($result[0] != 0){ //if a lobby with name alrady exists
			$conn->close();
			echo 2;
			exit();
		}

		$sql = "SELECT status, lobbyid FROM sessions WHERE id=$sessionid";
		if ($conn->query($sql) === FALSE) { echo "Error: " . $conn->error; }
		$result = $conn->query($sql) -> fetch_array(MYSQLI_NUM);
		
		if ($result[0] != 0){ //if already in another lobby
			$conn->close();
			echo 2;
			exit();
		}
		
		$lobbypassword = $_REQUEST["password"];

		if($lobbypassword != ""){
			$cost = 12;
			$hashedpassword = password_hash($lobbypassword, PASSWORD_BCRYPT, ["cost" => $cost]);
			$stmt = $conn->prepare("INSERT INTO lobbies
			(lobbyname, haspassword, lobbypassword, adminid, playercount, lastupdated)
			VALUES (?, 1, ?, $sessionid, 1, $now)");
			if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
			$stmt->bind_param("ss", $lobbyname, $hashedpassword);
			$stmt->execute();
			$stmt->close();
		} else {
			$stmt = $conn->prepare("INSERT INTO lobbies
			(lobbyname, haspassword, adminid, playercount, lastupdated)
			VALUES (?, 0, $sessionid, 1, $now)");
			if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
			$stmt->bind_param("s", $lobbyname);
			$stmt->execute();
			$stmt->close();
		}

		$stmt = $conn->prepare("SELECT Count(*), id FROM lobbies WHERE lobbyname = ?");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("s", $lobbyname);
		$stmt->execute();
		$result = $stmt->get_result() -> fetch_array(MYSQLI_NUM);
		$stmt->close();

		if ($result[0] == 0){
			$conn->close();
			echo 1;
			exit();
		}

		$lobbyid = $result[1];
		$sql = "UPDATE sessions SET status = 1, lobbyid = $lobbyid, last_seen = $now WHERE id=$sessionid";
		if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }

		$sql = "DROP DATABASE IF EXISTS lobby" . $lobbyid;
		if ($conn->query($sql) === FALSE) { echo "Error deleting database: " . $conn->error; }

		$sql = "CREATE DATABASE IF NOT EXISTS  lobby" . $lobbyid;
		if ($conn->query($sql) === FALSE) { die("Error creating database: " . $conn->error); }
	
		$conn->close();
		
		$dbname = "lobby" . $lobbyid;
		$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($conn->connect_error) {die("New connection failed: " . $conn->connect_error); }
		
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
			if ($conn->query($sqlarray[$i]) === FALSE) { die("Error creating tables: " . $conn->error); }
		}
		
		$sql = "INSERT INTO settings
		(decksize, suitcount, suitsize, handsize)
		VALUES (52, 4, 13, 6)";
		if($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }
		
		$sql = "INSERT INTO gamestates
		(id, isactive, playercount, handsize, ischargeturn, chargerid, trumpcard0, trumpcard1, deckleft, winnerid, lastupdated)
		VALUES (1, 0, 0, 0, 0, 0, 0, 0, 0, NULL, $now)";
		if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }

		$stmt = $conn->prepare("INSERT INTO players (id, nickname) VALUES ($sessionid, ?)");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("s", $nickname);
		$stmt->execute();
		$stmt->close();

		$conn->close();
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

		$m_conn->close();
	}
	
	function DeleteLobby($conn, $isadmin){
		$lobbyid = $_REQUEST["id"];
	
		if ($isadmin == 0){
			echo 5;
			$conn->close();
			exit();
		}

		$sql = "DROP DATABASE IF EXISTS lobby" . $lobbyid;
		if ($conn->query($sql) === FALSE) { echo "Error deleting database: " . $conn->error; }
	
		$sql = "DELETE FROM lobbies WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error deleting record: " . $m_conn->error; }

		$sql = "UPDATE sessions SET status = 0, lobbyid = NULL WHERE lobbyid=$lobbyid";
		if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }		
	}
	
	function ChangeLobbySettings($isadmin, $servername, $dbusername, $dbpassword) {
		$lobbyid = $_REQUEST["id"];
		
		$deck_size = $_REQUEST['decksize'];
		$suitcount =  $_REQUEST['suitcount'];
		$suitsize = $_REQUEST['suitsize'];
		$handsize = $_REQUEST['handsize'];
	
		if ($isadmin == 0){
			echo 6;
			exit();
		}
		
		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }
		
		$sql = "UPDATE settings SET decksize = $deck_size, suitcount = $suitcount, suitsize = $suitsize, handsize = $handsize";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$m_conn->close();
	}

	function GetSessionInfo($conn, $phpsessionid){
		$sql = "SELECT Count(*), id, nickname FROM sessions WHERE php_session_id=$phpsessionid";
		if ($conn->query($sql) === FALSE) { echo "Error: " . $conn->error; }
		$result = $conn->query($sql) -> fetch_array(MYSQLI_NUM);
	
		if ($result[0] != 0){
			return $result;
		} else {
			echo -1;
			$conn->close();
			exit();
		}
	}

	function IsAdmin($conn, $sessionid, $servername, $dbusername, $dbpassword){
		$lobbyid = $_REQUEST["id"];

		$sql = "SELECT Count(*), adminid FROM lobbies WHERE id = $lobbyid";
		if ($conn->query($sql) === FALSE) { echo "Error: " . $conn->error; }
		$result = $conn->query($sql) -> fetch_array(MYSQLI_NUM);
	
		if ($result[0] == 0){
			echo -1;
			$conn->close();
			exit();
		} else if ($result[1] != $sessionid){
			return 0;
		} else {
			return 1;
		}
	}
?>