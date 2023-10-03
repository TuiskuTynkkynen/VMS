<?php

	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "cardgame";

	$conn = new mysqli($servername, $username, $password, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }
	
	$Mode = $_REQUEST["Mode"];
	if ($Mode == "0"){ Leaderboard($conn); }
	else if ($Mode == "1"){ Aggregate($conn); }
	else if ($Mode == "2"){ Top3($conn); }
	else {	
		$sql = "SELECT Count(id) FROM Leaderboard";
		$result = $conn->query($sql);
		if ($result === NULL) {echo "Error: " . $conn->error; }
		echo($result->fetch_array(MYSQLI_NUM)[0]);
	}

	$conn->close();

	function Leaderboard($conn){
		$Sort = $_REQUEST["Sort"];
		$Order = $_REQUEST["Order"];
		$Limit = $_REQUEST["Limit"];
		$Offset = $_REQUEST["Offset"];
		//http://192.168.8.162/utils/LeaderboardAPI.php?Mode=0&Sort=games&Order=DESC&Limit=10&Offset=0
		
		$sql = "SELECT name, games, wins, losses, (wins / games) AS winrate, (losses / games) AS lossrate
		FROM leaderboard
		ORDER BY $Sort $Order
		LIMIT $Limit
		OFFSET $Offset";
		$result = $conn->query($sql);
		if ($result === NULL) {echo "Error: " . $conn->error; }

		$str = '{"Users":[';
		for ($i = 0; $i < $result->num_rows; $i++){
			$row = $result->fetch_array(MYSQLI_NUM);
			$str .= '{"name":"' . $row[0] . '", "games":"' . $row[1] . '", "wins":"' . $row[2] . '", "losses":"' . $row[3] . '", "winrate":"' . $row[4] . '", "lossrate":"' . $row[5] . '"}';
			if ($i != $result->num_rows-1) {$str .= ','; }
		}
		$str .= ']}';

		echo $str;
	}

	function Aggregate($conn){
		
		$sql = "SELECT AVG(games), AVG(wins), AVG(losses), AVG(wins / games), AVG(losses / games), SUM(wins) FROM Leaderboard WHERE games > 0";
		if ($conn->query($sql) === NULL) {echo "Error: " . $conn->error; }
		$row = $conn->query($sql)->fetch_array(MYSQLI_NUM);
		$str = '{"games":"' . $row[0] . '", "wins":"' . $row[1] . '", "losses":"' . $row[2] . '", "winrate":"' . $row[3] . '", "lossrate":"' . $row[4] . '",  "count":"' . $row[5] . '"}';
		echo $str;
	}

	function Top3($conn){
		function Res($conn, $sql, $statname){
			$res = "";
			$result = $conn->query($sql);
			if ($result === NULL) {echo "Error: " . $conn->error; }
			for ($i = 0; $i < $result->num_rows; $i++){
				$row = $result->fetch_array(MYSQLI_NUM);
				$res .= '{"name":"' . $row[0] . '", "' . $statname . '":"' . $row[1] . '"}';
				if ($i != $result->num_rows-1) {$res .= ','; }
			}
			return $res;
		}

		$sql[0] = "SELECT name, wins FROM Leaderboard ORDER BY wins DESC LIMIT 3";
		$sql[1] = "SELECT name, (wins / games) AS winrate FROM Leaderboard ORDER BY winrate DESC LIMIT 3";
		$sql[2] = "SELECT name, (losses / games) AS lossrate FROM Leaderboard ORDER BY lossrate DESC LIMIT 3";
		
		$str = '{"topwins":[' . Res($conn, $sql[0], "wins") .'], "topwinrate":[' . Res($conn, $sql[1], "winrate") .'], "toplossrate":[' . Res($conn, $sql[2], "lossrate") . ']}';
		
		echo $str;
	}

?>