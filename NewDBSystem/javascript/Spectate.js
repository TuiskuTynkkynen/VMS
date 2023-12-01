const beforeUnloadHandler = (event) => {
	//set updaterequired = true -> needs to update within 1 min or session will be deleted
	const xhttp = new XMLHttpRequest();
	xhttp.open("POST", "/NewDBSystem/server/AccountAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=6");

	return "d";
};
window.addEventListener("beforeunload", beforeUnloadHandler);

// Create WebSocket connection.
const serverip = location.host;
const socket = new WebSocket("ws://" + serverip + ":8080/NewDBSystem/server/VMSsocket.php");

// Socket error
socket.addEventListener("error", (event) => {
	document.getElementById("error").classList.remove("hidden");

	console.log(event);
});

// Connection opened
socket.addEventListener("open", (event) => {
	socket.send("Hello Server!");
});

// Listen for messages
socket.addEventListener("message", (event) => {
	let data = event.data;
	console.log("Message from server ", data);
	info = JSON.parse(data);

	if (UserSessionId != -1 && info.hasOwnProperty("updatedlobbies") && info.updatedlobbies.includes(Number(LobbyId))) {
		GetGameInfo();
	}
});

let UserSessionId = -1;
let LobbyId;
let SpectateSessionId;
let inactivitytimer;
let PlayerId;
let PlayerCount;
let ActivePlayer = 0;
let Field = [];
let ChargerId = 0;
let OldChargerId;
let Players = [];
let Hand = [];
let TrumpCard = [];
let DeckStr;
let Initialized = 0;

//Ges user info and updaterequired = false 
const xhttp = new XMLHttpRequest
xhttp.open("POST", "/NewDBSystem/server/SessionAPI.php");
xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
xhttp.send("action=0");
xhttp.onload = function () {
	let x = this.responseText;
	console.log(x);
	let UserInfo = JSON.parse(x);

	if (UserInfo.status == "1") {
		UserSessionId = UserInfo.SID;
		LobbyId = UserInfo.lobby;

		GetPlayers();
		InactivityNotice(UserInfo.inactive);
	} else {
		window.location.replace("/NewDBSystem/VMS.html");
	}
}

Input();

function Input() {
	document.onkeydown = function Rr(event) {
		let key = event.code;
		switch (key) {
			case 'ArrowRight':
				if (ActivePlayer < PlayerCount-1) { ActivePlayer++; }
				else { ActivePlayer = 0; }
				GetPlayers();
				break;
			case 'ArrowLeft':
				if (ActivePlayer > 0) { ActivePlayer--; }
				else { ActivePlayer = PlayerCount-1; }
				GetPlayers();
				break;
		}
	};
}

function GetPlayers() {
	const xhttp = new XMLHttpRequest();
	xhttp.open("GET", "/NewDBSystem/server/GetPlayers.php?lobbyid=" + LobbyId);
	xhttp.send();
	xhttp.onload = function () {
		let response = this.responseText;
		console.log(response);

		if (response == "-1") {
			window.location.replace("/NewDBSystem/VMS.html");
		}

		let playerinfo = JSON.parse(response);
		SpectateSessionId = playerinfo.Players[ActivePlayer].SID;
		PlayerId = playerinfo.Players[ActivePlayer].PID;
		PlayerCount = playerinfo.Players.length;
		GetGameInfo();
	}
}

function GetGameInfo() {
	const xhttp = new XMLHttpRequest
	xhttp.open("POST", "/NewDBSystem/server/GameAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=0&SID=" + SpectateSessionId);
	xhttp.onload = function () {
		let response = this.responseText;
		console.log(response);
		let GameInfo = JSON.parse(response);

		if (GameInfo.hasOwnProperty("gameover")) {
			Win(GameInfo.gameover.winstatus);
			return;
		}

		CanCharge = MustKill = Supporting = 0;
		let oldtrumpcard = TrumpCard
		let oldplayers = Players;
		OldChargerId = ChargerId;

		PlayerId = GameInfo.PID;
		IsChargeturn = GameInfo.ischargeturn;
		ChargerId = GameInfo.chargerid;
		TrumpCard = GameInfo.trumpcard;
		DeckStr = GameInfo.deckleft;
		Players = GameInfo.players;
		Hand = GameInfo.hand;
		Field = GameInfo.field;
		Opponent = GameInfo.opponent;

		if (DeckStr > 10) {
			DeckStr = "&thickapprox;" + Math.round(DeckStr / 10) * 10;
		} else if (DeckStr == 1) {
			document.getElementById("deck").classList.add("hidden");
		} else if (DeckStr <= 0) {
			document.getElementById("deck").classList.add("hidden");
			document.getElementById("trumpcard").classList.add("hidden");
			document.getElementById("trumpsuit").classList.remove("hidden");
		}

		if (Initialized == 0 || oldtrumpcard != TrumpCard || oldplayers != Players) {
			Initialized++;
			InitializeGUI();
		}

		GUI();
	}
}

function GUI() {
	let str = '<img id="deckimg2"; src="/imgs/card_img.png"> <div>' + DeckStr + '</div>';
	document.getElementById('deck').innerHTML = str;

	str = "";
	Hand = Hand.sort((a, b) => a[1] - b[1]);
	Hand = Hand.sort((a, b) => a[0] - b[0]);
	for (let i = 0; i < Hand.length; i++) {
		let result = ParseCard(Hand, i);
		let suit = result[0];
		let value = result[1];
		let color = result[2];
		if (suit == "&#NaN" && value == "&#NaN") { Hand.splice(i, 1); continue; }
		str += '<div class=handflex id=hand' + i + '> <div class=flexcontent> <img src="/imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div> </div>';
	}
	document.getElementById('handflexcontainer').innerHTML = str;

	document.getElementById('fieldflexcontainer').innerHTML = "";
	
	for (let i = 0; i < Field.length; i++) {
		let result = ParseCard(Field, i);
		let suit = result[0];
		let value = result[1];
		let color = result[2];
		if (Field[i][2] < 2) {
			str = '<div class=fieldflex id=field' + i + '> <div class=flexcontent> <img src="/imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div> </div>';
			document.getElementById('fieldflexcontainer').innerHTML += str;
		} else {
			str = ' <div class=killerflexcontent> <img src="/imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div>';
			document.getElementById('field' + Field[i][3]).innerHTML += str;
		}
	}

	while (1 < document.getElementById("player" + OldChargerId).children[0].childElementCount) {
		document.getElementById("player" + OldChargerId).children[0].children[1].remove();
	}
	document.getElementById("player" + ChargerId).children[0].innerHTML += "<p> > </p>";
}

function InitializeGUI() {
	let str = "";

	let result = ParseCard([TrumpCard], 0);
	let suit = result[0];
	let value = result[1];
	let color = result[2];
	str = '<img id="deckimg1"; src="/imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p>';
	document.getElementById('trumpcard').innerHTML = str;
	document.getElementById('trumpsuit').innerHTML = '<p style=color:' + color + ' > ' + suit + '</p >';

	str = '<div> <div> <p style="left:25%">Pelaajat:</p> </div> </div>';
	for (let i = 0; i < Players.length; i++) {
		str += '<div id="player' + Players[i][0] + '"> <div> <p class="nick"> ' + Players[i][1] + ' </p> </div> </div>';
	}
	document.getElementById('playerinfo').innerHTML = str;

	document.getElementById("player" + PlayerId).classList.add("blue");
}

function ParseCard(array, num) {
	let suit = parseInt(array[num][0])
	let color = "green";
	if (suit == 1 || suit == 4) { suit = suit + 9823; color = "#666666"; }
	else { suit = suit + 9827; color = "#bb1010"; }

	suit = "&#" + suit;
	let value = parseInt(array[num][1]);

	switch (true) {
		case (value <= 10):
			value = value;
			break;
		case (value == 11):
			value = "J";
			break;
		case (value == 12):
			value = "Q";
			break;
		case (value == 13):
			value = "K";
			break;
		case (value == 14):
			value = "A";
			break;
		default:
			x = 97 - 15 + value;
			value = "&#" + x;
			break;
	}
	return [suit, value, color];
}

function Win(winstatus) {
	document.getElementById("win").classList.remove("hidden");
	document.getElementById("mainmenu").onclick = MainMenu;
	document.getElementById("leaderboard").onclick = Leaderboard;

	function MainMenu(event) {
		window.location.replace("/NewDBSystem/Index.html");
		socket.close();
	}
	function Leaderboard(event) {
		window.location.replace("/NewDBSystem/Leaderboard.html");
		socket.close();
	}
}

function InactivityNotice(seconds) {
	clearTimeout(inactivitytimer);
	inactivitytimer = setTimeout(() => {
		document.getElementById("notice").classList.remove("hidden");
		document.getElementById("main").classList.add("hidden");
		document.getElementById("lobbygui").classList.add("hidden");
		document.getElementById("main").classList.add("hidden");
		document.addEventListener("keypress", HideNotice);
	}, seconds * 1000);

	function HideNotice() {
		document.getElementById("notice").classList.add("hidden");
		window.location.reload();
	}
}