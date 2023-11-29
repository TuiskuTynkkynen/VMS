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

	if (SessionId != -1 && info.hasOwnProperty("updatedlobbies") && info.updatedlobbies.includes(Number(LobbyId))) {
		GetGameInfo();
	}
});

let SessionId = -1;
let LobbyId;
let PlayerId;
let Initialized = 0;
let Players = [];
let Field = [];
let Hand = [];
let TrumpCard = [];
let DeckStr;
let IsIngame = 1;
let ChargerId = 0;
let OldChargerId;
let IsChargeturn;
let Opponent;
let InputKey = undefined;
let ActiveCard = 0;
let ChosenCards = [];
let CanKill = [];
let CanCharge;
let MustKill;
let Supporting;
let inactivitytimer;
let arr;

//Ges user info and updaterequired = false 
const xhttp = new XMLHttpRequest();
xhttp.open("GET", "/NewDBSystem/server/GetUserInfo.php");
xhttp.send();
xhttp.onload = function () {
	let x = this.responseText;
	console.log(x);
	let UserInfo = JSON.parse(x);
	if (UserInfo.status == "2") {
		SessionId = UserInfo.SID;
		LobbyId = UserInfo.lobby;

		GetGameInfo();
		InactivityNotice(UserInfo.inactive);

		console.log(ActiveCard);

		if (UserInfo.canchangetrump == "1") {
			ChangeTrump();
		} else { GameLoop(); }
	} else {
		Win();
	}
}

function Input(funcName) {
	document.onkeydown = function (event) {
		//console.log(event);
		InputKey = event.code;
		if (IsIngame == 1) { window[funcName](); }
		return;
	};
}

function GetGameInfo() {
	const xhttp = new XMLHttpRequest
	xhttp.open("POST", "/NewDBSystem/server/GameAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=0&SID=" + SessionId);
	xhttp.onload = function () {
		let response = this.responseText;
		console.log(response);
		let GameInfo = JSON.parse(response);

		if (GameInfo.hasOwnProperty("gameover")) {
			IsIngame = GameInfo.gameover.ingame;
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

		if (IsChargeturn == 1 && ChargerId == PlayerId) { console.log("Charge Turn"); CanCharge = 1; }
		else if (ChargerId == PlayerId) { console.log("Kill Turn"); MustKill = 1; }
		else { console.log("Support Turn"); Supporting = 1; }

		if (DeckStr > 10) {
			DeckStr = "&thickapprox;" + Math.round(DeckStr / 10) * 10;
		} else if (DeckStr == 1) {
			document.getElementById("deck").classList.add("hidden");
		} else if (DeckStr <= 0) {
			document.getElementById("deck").classList.add("hidden");
			document.getElementById("trumpcard").classList.add("hidden");
			document.getElementById("trumpsuit").classList.remove("hidden");
		}

		if (oldtrumpcard != TrumpCard | oldplayers != Players) { Initialized++; InitializeGUI(); }
		GUI();
	}
}

function GUI() {
	let str = '<img id="deckimg2"; src="/imgs/card_img.png"> <div>' + DeckStr + '</div>';
	document.getElementById('deck').innerHTML = str;

	if (MustKill == 0 || ActiveCard == -1) { GUIFlex(ChosenCards, "chosen"); }
	else { GUIFlex("", "chosen"); }

	str = "";
	Hand = Hand.sort((a, b) => a[1] - b[1]);
	Hand = Hand.sort((a, b) => a[0] - b[0]);
	for (let i = 0; i < Hand.length; i++) {
		let result = ParseCard(Hand, i);
		let suit = result[0];
		let value = result[1];
		let color = result[2];
		if (suit == "&#NaN" && value == "&#NaN") { Hand.splice(i, 1); continue; }
		str += '<div class=handflex id=Hand' + i + '> <div class=flexcontent> <img src="/imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div> </div>';
	}
	document.getElementById('handflexcontainer').innerHTML = str;

	if (CanKill.length == 0 && ActiveCard > -1) {
		try {
			document.getElementById('Hand' + ActiveCard).classList.add("active");
		} catch (error) { console.log(error); }
	}

	str = "";
	if (MustKill == 0 && (Supporting == 0 | Field.length > 0)) {
		document.getElementById('fieldcontainer').classList.add("offset");
		for (let i = 0; i < Opponent; i++) {
			str += '<div class=opponentflex> <div class=flexcontent> <img src="/imgs/card_img.png"> </div> </div>';
		}
	} else {
		document.getElementById('fieldcontainer').classList.remove("offset");
	}
	document.getElementById('opponentflexcontainer').innerHTML = str;

	document.getElementById('fieldflexcontainer').innerHTML = "";
	if (MustKill == 1) {
		str = '<div class=fieldflex id=field_button> <img src="/imgs/field_img.png" id="field_img" style=width:90%;height:auto> </div>';
		document.getElementById('fieldflexcontainer').innerHTML = str;
	}

	for (let i = 0; i < Field.length; i++) {
		let result = ParseCard(Field, i);
		let suit = result[0];
		let value = result[1];
		let color = result[2];
		if (Field[i][2] < 2) {
			str = '<div class=fieldflex id=Field' + i + '> <div class=flexcontent> <img src="/imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div> </div>';
			document.getElementById('fieldflexcontainer').innerHTML += str;
		} else {
			str = ' <div class=killerflexcontent> <img src="/imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div>';
			document.getElementById('Field' + Field[i][3]).innerHTML += str;
		}
	}

	if (MustKill == 1 && CanKill.length > 0) {
		let result = ParseCard(ChosenCards, 0);
		let suit = result[0];
		let value = result[1];
		let color = result[2];
		let str = ' <div class=killingflexcontent> <img src="/imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div>';
		try {
			document.getElementById('Field' + CanKill[ActiveCard]).innerHTML += str;
		} catch (error) {/*console.error(error); */ }
	}
	str = '<div class=fieldflex flex id=Field' + Field.length + ' style=width:1> <div style=height:37%> </div></div>'
	document.getElementById('fieldflexcontainer').innerHTML += str;

	while (1 < document.getElementById("player" + OldChargerId).children[0].childElementCount) {
		document.getElementById("player" + OldChargerId).children[0].children[1].remove();
	}
	document.getElementById("player" + ChargerId).children[0].innerHTML += "<p> > </p>";
}
function GUIFlex(array, name) {

	let i;
	let str = "";

	if (array.length > 0 && MustKill == 0) {
		str += '<div class=' + name + 'flex id=' + name + 'charge style=margin:1rem> <img src="/imgs/charge_img.png" id="charge_img" style=height:30%> </div>';
	}

	for (i = 0; i < array.length; i++) {
		let result = ParseCard(array, i);
		let suit = result[0];
		let value = result[1];
		let color = result[2];
		str += '<div class=' + name + 'flex id=' + name + i + '> <div class=flexcontent> <img src="/imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div> </div>';

		Hand.some(RemovePairs);
	}
	document.getElementById(name + 'flexcontainer').innerHTML = str;

	function RemovePairs(value, index) {
		if (parseInt(value[0]) == parseInt(array[i][0])) {
			if (parseInt(value[1]) == parseInt(array[i][1])) {
				Hand.splice(index, 1);
			}
		}
	}
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

	str = '<div> <div> <p style="width:100%; text-align:center">Pelaajat:</p> </div> </div>';
	for (let i = 0; i < Players.length; i++) {
		str += '<div id="player' + Players[i][0] + '"> <div> <p class="nick"> ' + Players[i][1] + ' </p> </div> </div>';
	}
	document.getElementById('playerinfo').innerHTML = str;

	document.getElementById("player" + PlayerId).classList.add("yellow");

	result = ParseCard([[TrumpCard[0][0], "2"]], 0);
	suit = result[0];
	value = result[1];
	color = result[2];

	str = '<p style=color:' + color + ' >' + value + '<br>' + suit + '</p>';
	document.getElementById('newtrump').innerHTML += str;
	result = ParseCard(TrumpCard, 0);
	suit = result[0];
	value = result[1];
	color = result[2];

	str = '<p style=color:' + color + ' >' + value + '<br>' + suit + '</p>';
	document.getElementById('oldtrump').innerHTML += str;

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


//TODO actually name this and remember func name used in other places
function GameLoop() {
	Input('GameLoop');
	let oldActiveCard = ActiveCard;

	if (CanKill.length == 0) { array = Hand; }
	else { array = CanKill; }

	switch (InputKey) {
		case 'ArrowDown':
			ActiveCard = 0;
			console.log(ActiveCard);
			break;
		case 'ArrowUp':
			if (ChosenCards.length > 0 || MustKill == 1) { ActiveCard = -1; }
			else { ActiveCard = 0; }
			console.log(ActiveCard);
			break;
		case 'ArrowRight':
			if (ActiveCard < array.length - 1) {
				ActiveCard++;
			}
			else { ActiveCard = -1; }
			console.log(ActiveCard);
			break;
		case 'ArrowLeft':
			if (ActiveCard > -1) {
				ActiveCard--;
			}
			else { ActiveCard = array.length - 1; }
			console.log(ActiveCard);
			break;
		case 'Enter':
			if (MustKill == 1) { console.log("you killing"); KillTest(); }
			else { console.log("you charging"); ChargeTest(); }
			break;
		case 'Escape':
			let x = ChosenCards.length;
			for (let i = 0; i < x; i++) {
				Hand.push(ChosenCards.splice(0, 1).flat());
			}
			CanKill = [];
			Hand.sort(); console.log(Hand); console.log(ActiveCard);
			GUI();
			break;
	}

	if (MustKill == 1) {
		if (ActiveCard == -1) {
			document.getElementById('field_img').classList.add("active1");
		} else if (oldActiveCard == -1) {
			document.getElementById('field_img').classList.remove("active1");
		}
	} else {
		if (ActiveCard == -1 && ChosenCards.length > 0) {
			document.getElementById('charge_img').classList.add("active1");
		} else if (oldActiveCard == -1 && ChosenCards.length > 0) {
			document.getElementById('charge_img').classList.remove("active1");
		}
	}
	if (MustKill == 1 && CanKill.length > 0) {
		GUI();
		let result = ParseCard(ChosenCards, 0);
		let suit = result[0];
		let value = result[1];
		let color = result[2];
		let str = ' <div class=killingflexcontent> <img src="/imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div>';
		try {
			document.getElementById('Field' + CanKill[ActiveCard]).innerHTML += str;
		} catch (error) {/*console.error(error); */ }
	} else {
		try {
			document.getElementById('Hand' + ActiveCard).classList.add("active");
		} catch (error) {/*console.error(error); */ }

		if (oldActiveCard !== ActiveCard) {
			try {
				document.getElementById('Hand' + oldActiveCard).classList.remove("active");
			} catch (error) {/* console.error(error); */ }
		}
	}
}

function ChargeTest() {

	if (ChosenCards.length > 0) {

		arr = ChosenCards;
		if (ActiveCard == -1) {
			if (Supporting == 1) {	//!!!!!!!!!!!!!!! might not work
				arr = ChosenCards;
				if (Field.length > 0 && Field.some(HasPairs)) {
					console.log("Field matches");
					Charge(ChosenCards, Supporting);
				} else {
					console.log("no match");
					AddAnimation('chosenflexcontainer', 'chosen', 'feedback');
				}
				return;
			}
			if (ChosenCards.length > 1 && !ChosenCards.every(HasPairs)) { //!!!!!!!!!!!!!!! .every might not work
				AddAnimation('chosenflexcontainer', 'chosen', 'feedback');
				return;
			}
			Charge(ChosenCards, Supporting);
			return;
		}


		let temp = [];
		temp[0] = Hand.splice(ActiveCard, 1).flat();
		ActiveCard = Hand.length;

		let x = 0;
		arr = ChosenCards;
		if (temp.some(HasPairs)) {
			ChosenCards.push(temp.splice(0, 1).flat()); x++;
			GUI();
			return;
		}

		arr = ChosenCards;
		if (!ChosenCards.some(HasPairs)) {
			Hand.push(temp.splice(0, temp.length).flat());
			GUI();
			return;
		}

		arr = Hand;
		if (Supporting == 0 && temp.some(HasPairs)) {
			ChosenCards.push(temp.splice(0, temp.length).flat()); x++;
			GUI();
			return;
		}

		if (x == 0) {
			console.log("that wasnt a pair");
			Hand.push(temp.splice(0, temp.length).flat());
			console.log(ChosenCards); console.log(Hand);
			GUI();
		}
	} else { ChosenCards.push(Hand.splice(ActiveCard, 1).flat()); GUI(); }


}
function KillTest() {
	if (ActiveCard == -1) { Kill(ChosenCards, ActiveCard); return; }//picks cards from Field to Hand 

	if (CanKill.length > 0) {
		if (CanKill[ActiveCard] < Field.length) {
			Kill(ChosenCards, CanKill[ActiveCard]);
		} else { Charge(ChosenCards, 1); }
		return;
	}

	console.log(Hand);
	ChosenCards = [Hand.splice(ActiveCard, 1).flat()];
	console.log(Hand);
	CanKill = [];
	ActiveCard = -1;
	Field.forEach(IsKillable);

	arr = ChosenCards;
	if (Field.some(HasPairs)) { CanKill.push(Field.length); }

	console.log(CanKill); console.log(ChosenCards); console.log(ActiveCard); console.log("Can kill:");
	for (i = 0; i < CanKill.length; i++) { console.log(Field[CanKill[i]]); }

	if (CanKill.length == 0) { console.log("Hand:"); Hand.push(ChosenCards.splice(0, 1).flat()); console.log(Hand); ActiveCard = 0; }

	GUI();
	function IsKillable(value, index, array) {
		console.log("testing Field for killables");
		if (value[2] != 0) { return; }
		if (parseInt(ChosenCards[0][0]) == TrumpCard[0]) {
			if (parseInt(value[0]) != TrumpCard[0]) { CanKill.push(index); }
			else if (parseInt(value[1]) <= parseInt(ChosenCards[0][1])) { CanKill.push(index); }
		}
		else if (parseInt(value[0]) == parseInt(ChosenCards[0][0]) && parseInt(value[1]) < parseInt(ChosenCards[0][1])) { CanKill.push(index); }
	}
}

function Charge(cards, isSupporting) {
	console.log(CanCharge); console.log(isSupporting);
	console.log(JSON.stringify(cards));

	let cardsJSON = '{"cards":' + JSON.stringify(cards) + '}'

	const xhttp = new XMLHttpRequest();
	xhttp.open("POST", "/NewDBSystem/server/GameAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=1&SID=" + SessionId + "&Cards=" + cardsJSON + "&Support=" + isSupporting + "&ChargeTurn=" + IsChargeturn + "&CID=" + ChargerId + "&owncharge=" + MustKill);

	xhttp.onload = function () {
		let response = this.responseText;
		console.log(response);

		if (response == "1" | response == "2") {
			AddAnimation('chosenflexcontainer', 'chosen', 'feedback');
			return;
		}

		ChosenCards.splice(0, ChosenCards.length);
		console.log("you charged");
		CanKill = [];
		GetGameInfo(); GUI();
	}
}

function Kill(card, killedId) {
	console.log(Hand);
	console.log(card.toString());
	console.log("you killed id " + killedId);

	let cardsJSON = '{"card":' + JSON.stringify(card) + '}'

	const xhttp = new XMLHttpRequest();
	xhttp.open("POST", "/NewDBSystem/server/GameAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	if (killedId > -1) {
		xhttp.send("action=2&SID=" + SessionId + "&Card=" + cardsJSON + "&KillsId=" + killedId);
	} else {
		xhttp.send("action=3&SID=" + SessionId);
	}

	xhttp.onload = function () {
		let response = this.responseText;
		console.log(response);
		ChosenCards.splice(0, ChosenCards.length);
		CanKill = [];
		GetGameInfo();
		GUI();
	}
}

function ChangeTrump() {
	document.getElementById("trump").classList.remove("hidden");

	Input('ChangeTrump');

	const xhttp = new XMLHttpRequest();
	xhttp.open("GET", "/NewDBSystem/server/ChangeTrump.php?UID=" + UId);
	xhttp.send();

	if (typeof InputKey != 'undefined') {
		xhttp.onload = function () {
			document.getElementById("trump").classList.add("hidden");
			Initialized = 0;
			GameLoop();
		}
	}
}

function Win(winstatus) {
	document.getElementById("win").classList.remove("hidden");
	if (winstatus == 1) {
		document.getElementById("win").innerHTML += "<div id=winmsg>Voitto</div>";
		document.getElementById("spectate").onclick = Spectate;
	} else if (winstatus == 0) {
		document.getElementById("win").innerHTML += "<div id=winmsg>P" + "&#228&#228" + "sit pois pelist" + "&#228" + "</div>";
		document.getElementById("spectate").onclick = Spectate;
	} else if (winstatus == -1) {
		document.getElementById("win").innerHTML += "<div id=winmsg>Tappio</div>";
		document.getElementById("spectate").style.color = "#888";
	} else {
		document.getElementById("win").innerHTML += "<div id=winmsg>Peli on p&#228&#228ttynyt</div>";
		document.getElementById("spectate").style.color = "#888";
	}

	document.getElementById("mainmenu").onclick = MainMenu;
	document.getElementById("leaderboard").onclick = Leaderboard;

	function MainMenu(event) {
		window.location.replace("/NewDBSystem/Index.html");
		socket.close();
	}

	function Spectate(event) {
		window.location.assign("/NewDBSystem/Spectate.html");
		socket.close();
	}
	function Leaderboard(event) {
		window.location.assign("/NewDBSystem/Leaderboard.html");
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

function HasPairs(value, index, array) {
	if (array == arr) {
		for (let i = index + 1; i < arr.length; i++) {
			if (value[1] == arr[i][1]) { return true; }
		}
		for (let i = index - 1; i >= 0; i--) {
			if (value[1] == arr[i][1]) { return true; }
		}
	} else {
		for (let i = 0; i < arr.length; i++) {
			if (value[1] == arr[i][1]) { return true; }
		}
	}
}

function AddAnimation(container, item, animation) {
	for (let i = 0; i < document.getElementById(container).childElementCount - 1; i++) {
		document.getElementById(item + i).classList.add(animation);
		setTimeout(() => {
			document.getElementById(item + i).classList.remove(animation);
			GetGameInfo();
		}, "500");
	}
}

//TODO figure out if need to add more state testing on server requests(ie kill, charge, ect ect) that client has correct state
//TODO more animations (especially a red border and shake or something when you cant perform an action)
//TODO make sure winning losing ect and spectate works correctly
