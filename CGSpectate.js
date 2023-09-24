let UId;
let PId;
let playercount;
let activeplayer = 0;
let field = [];
let chargerid = 0;
let oldchargerid;
let players = [];
let isingame = 1;
let hand = [];
let tcard = [];
let deckstr;
let playerid;
let initialized = 0;

GetPlayers();
Input();
// Create WebSocket connection.
const serverip = location.host;
const socket = new WebSocket("ws://" + serverip + ":8080/utils/VMSsocket.php");

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
	let str = "{" + data + "}";
	info = JSON.parse(str);
	if (info.isactive == "0") {
		Win();
	}
	GetGameInfo();
});


async function Input() {
	document.onkeydown = function Rr(event) {
		//console.log(event);
		let key = event.code;
		switch (key) {
			case 'ArrowRight':
				if (activeplayer < playercount-1) { activeplayer++; }
				else { activeplayer = 0; }
				GetPlayers();
				break;
			case 'ArrowLeft':
				if (activeplayer > 0) { activeplayer--; }
				else { activeplayer = playercount-1; }
				GetPlayers();
				break;
		}
	};


}

function GetPlayers() {
	const xhttp = new XMLHttpRequest();
	xhttp.onload = function () {
		let x = this.responseText;
		let Players = JSON.parse(x);
		UId = Players.Players[activeplayer].UID;
		PId = Players.Players[activeplayer].PID;
		playercount = Players.Players.length;
		GetGameInfo();
	}
	xhttp.open("GET", "utils/GetPlayers.php");
	xhttp.send();
}

function GetGameInfo() {
	const xhttp = new XMLHttpRequest();
	xhttp.onload = function () {
		let x = this.responseText;
		if (x != "1") {
			LoadGameInfo(x);
		}
	}

	xhttp.open("GET", "utils/GetGameInfo.php?UID=" + UId + "&Ingame=" + 0);
	xhttp.send();
}

function LoadGameInfo(txt) {
	parser = new DOMParser();
	xmldocument = parser.parseFromString(txt, "text/xml");

	oldchargerid = chargerid;
	chargerid = xmldocument.getElementsByTagName("chargerid")[0].childNodes[0].nodeValue;
	let oldtcard = tcard
	tcard0 = xmldocument.getElementsByTagName("trumpcard0")[0].childNodes[0].nodeValue;
	tcard1 = xmldocument.getElementsByTagName("trumpcard1")[0].childNodes[0].nodeValue;
	tcard = [[tcard0, tcard1]];
	deckleft = xmldocument.getElementsByTagName("deckleft")[0].childNodes[0].nodeValue;
	if (deckleft > 10) { deckstr = "&thickapprox;" + Math.round(deckleft / 10) * 10; }
	else {
		deckstr = deckleft;
		if (deckleft == 1) {
			document.getElementById("deck").classList = "hidden";
		}
		if (deckleft == 0) {
			document.getElementById("deck").classList = "hidden";
			document.getElementById("trumpcard").classList = "hidden";
			document.getElementById("trumpsuit").classList.remove("hidden");
		}
	}

	playerid = xmldocument.getElementsByTagName("PID")[0].childNodes[0].nodeValue;

	let oldplayers = players;
	players = [];
	x = xmldocument.getElementsByTagName("players")[0].childNodes;
	for (i = 0; i < x.length; i += 2) {
		players.push([x[i].childNodes[0].nodeValue, x[i + 1].childNodes[0].nodeValue]);
	}

	hand = [];
	x = xmldocument.getElementsByTagName("hand")[0].childNodes;

	for (i = 0; i < x.length; i += 2) {
		hand.push([x[i].childNodes[0].nodeValue, x[i + 1].childNodes[0].nodeValue]);
	}

	if (xmldocument.getElementsByTagName("fieldcontainer")[0].hasChildNodes()) {
		x = xmldocument.getElementsByTagName("field")[0].childNodes;
		field = [];
		for (i = 0; i < x.length; i += 4) {
			field.push([x[i].childNodes[0].nodeValue, x[i + 1].childNodes[0].nodeValue, x[i + 2].childNodes[0].nodeValue, x[i + 3].childNodes[0].nodeValue]);
		}
	}
	else { field = []; }
	if (initialized == 0) { InitializeGUI(); }
	GUI();

}

function GUI() {
	let str = '<img id="deckimg2"; src="imgs/card_img.png"> <div>' + deckstr + '</div>';
	document.getElementById('deck').innerHTML = str;

	str = "";
	hand = hand.sort((a, b) => a[1] - b[1]);
	hand = hand.sort((a, b) => a[0] - b[0]);
	for (let i = 0; i < hand.length; i++) {
		let result = ParseCard(hand, i);
		let suit = result[0];
		let value = result[1];
		let color = result[2];
		if (suit == "&#NaN" && value == "&#NaN") { hand.splice(i, 1); continue; }
		str += '<div class=handflex id=hand' + i + '> <div class=flexcontent> <img src="imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div> </div>';
	}
	document.getElementById('handflexcontainer').innerHTML = str;

	document.getElementById('fieldflexcontainer').innerHTML = "";
	
	for (let i = 0; i < field.length; i++) {
		let result = ParseCard(field, i);
		let suit = result[0];
		let value = result[1];
		let color = result[2];
		if (field[i][2] < 2) {
			str = '<div class=fieldflex id=field' + i + '> <div class=flexcontent> <img src="imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div> </div>';
			document.getElementById('fieldflexcontainer').innerHTML += str;
		} else {
			str = ' <div class=killerflexcontent> <img src="imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div>';
			document.getElementById('field' + field[i][3]).innerHTML += str;
		}
	}

	while (1 < document.getElementById("player" + oldchargerid).children[0].childElementCount) {
		document.getElementById("player" + oldchargerid).children[0].children[1].remove();
	}
	document.getElementById("player" + chargerid).children[0].innerHTML += "<p> > </p>";
}

function InitializeGUI() {
	let str = "";

	let result = ParseCard(tcard, 0);
	let suit = result[0];
	let value = result[1];
	let color = result[2];
	str = '<img id="deckimg1"; src="imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p>';
	document.getElementById('trumpcard').innerHTML = str;
	document.getElementById('trumpsuit').innerHTML = '<p style=color:' + color + ' > ' + suit + '</p >';

	str = '<div> <div> <p style="left:25%">Pelaajat:</p> </div> </div>';
	for (let i = 0; i < players.length; i++) {
		str += '<div id="player' + players[i][0] + '"> <div> <p class="nick"> ' + players[i][1] + ' </p> </div> </div>';
	}
	document.getElementById('playerinfo').innerHTML = str;

	document.getElementById("player" + playerid).classList.add("blue");

	result = ParseCard([[tcard[0][0], "2"]], 0);
	suit = result[0];
	value = result[1];
	color = result[2];

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
	document.getElementById("leaderboard").style.color = "#888";

	function MainMenu(event) {
		window.location.replace("Index.html");
		socket.close();
	}
}