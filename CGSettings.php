<html>
<head>
    <title>VMS Settings</title>

    <style>
        body{
		    color: #fff;
		    background-color: #191919;
		    margin: 1rem;
        }
        form{
            margin: 0;
        }
    </style>

</head>

<?php
    $ip_server = $_SERVER['SERVER_ADDR'];
	$ip_client = $_SERVER['REMOTE_ADDR'];

	if ($ip_client != $ip_server){
        exit("Insufficient credentials");
	}  
?>

<body>

    <form id="form" action="CGInit.php" method="post">
    <label for="deck_size">Deck size:</label><br>
    <input type="number" name="deck_size" id="deck_size" min="1" required="true" value=52><br><br>
    <label for="suit_count">Number of suits:</label><br>
    <input type="number" name="suit_count" id="suit_count" min="1" required="true" value=4><br><br>
    <label for="suit_size">Size of suits:</label><br>
    <input type="number" name="suit_size" id="suit_size" min="1" required="true" value=13><br><br>
    <label for="hand_size">Size of hands:</label><br>
    <input type="number" name="hand_size" id="hand_size" min="1" required="true" value=6><br><br>
    </form>
    <button id="submit">Start game</button> 
    <button id="stop">Stop game</button>

    <script>
        document.getElementById("submit").onclick = function(event) {
            const xhttp = new XMLHttpRequest();
			xhttp.onload = function() {
				let x = this.responseText;
				let Users = JSON.parse(x);
				let statuses = [0, 0, 0];
				for (let i = 0; i < Users.Users.length; i++){
					statuses[Users.Users[i].status]++;
				}
                let str;
                if (statuses[2] == 0){
                    str = statuses[1] + ' players are waiting, do you want to start game?';
                } else {
                    str = 'game is active, ' + statuses[1] + ' players are waiting and ' + statuses[2] + ' players are still in game, do you still want to start game?';
                }

                if (confirm(str)){
                    document.getElementById("form").submit();
                }

			}
			xhttp.open("GET", "utils/GetUsers.php");
			xhttp.send();
        }
        document.getElementById("stop").onclick = function(event) {
            let str = "Do you want to deactivate game?";
            if (confirm(str)){
                const xhttp = new XMLHttpRequest();
	            xhttp.onload = function () {
                    alert("Game has been set to inactive");
	            }
	            xhttp.open("GET", "utils/Deactivate.php");
	            xhttp.send();
            }
        }
    </script>

</body>
</html>
