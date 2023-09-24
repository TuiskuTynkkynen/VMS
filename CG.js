let UId;
let CanChangeTrump;
let activeCard = 0;
let chosenCards = [];
let field = [];
let chargerid = 0;
let oldchargerid;
let players = [];
let isingame = 1;
let hand = [];
let tcard = [];
let deckstr;
let cancharge;
let mustkill;
let arr;
let cankill = [];
let playerid;
let initialized = 0;
let opponent;

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
	if (isingame != "0") {
		if (info.isactive == "0") {
			Win();
		} else {
			GetGameInfo();
		}
	}
});

//Gets user info
const xhttp = new XMLHttpRequest();
xhttp.onload = function () {
	let x = this.responseText;
	console.log(x);
	let UserInfo = JSON.parse(x);
	if (UserInfo.status == "2") {
		UId = UserInfo.UID;
		CanChangeTrump = UserInfo.canchangetrump;
		GetGameInfo();
		console.log(activeCard);
		if (CanChangeTrump == "1") {
			ChangeTrump();
			CanChangeTrump = 0;
		} else { Sumtin(); }
	} else {
		Win();
	}
}
xhttp.open("GET", "utils/GetUserInfo.php");
xhttp.send();


async function Input(funcName) {

	document.onkeydown = function Rr(event) {
		//console.log(event);
		result = event;
		if (isingame == 1) { window[funcName](); }
		return;
	};
}

function GetGameInfo() {
	const xhttp = new XMLHttpRequest();
	xhttp.onload = function () {
		let x = this.responseText;
		document.getElementById("2").innerHTML = x;
		LoadGameInfo(x);
	}

	xhttp.open("GET", "utils/GetGameInfo.php?UID=" + UId + "&Ingame=" + isingame);
	xhttp.send();
}

function LoadGameInfo(txt) {
	parser = new DOMParser();
	xmldocument = parser.parseFromString(txt, "text/xml");

	playercount = xmldocument.getElementsByTagName("playercount")[0].childNodes[0].nodeValue;
	ischargeturn = xmldocument.getElementsByTagName("ischargeturn")[0].childNodes[0].nodeValue;
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

	fffDebug = playercount + ischargeturn + chargerid + tcard0 + tcard1 + deckleft + playerid;
	if (xmldocument.getElementsByTagName("ingamecontainer")[0].hasChildNodes()) {
		isingame = xmldocument.getElementsByTagName("ingame")[0].childNodes[0].nodeValue;
		winstatus = xmldocument.getElementsByTagName("winner")[0].childNodes[0].nodeValue;
		Win(winstatus); return;
	}

	let oldplayers = players;
	players = [];
	x = xmldocument.getElementsByTagName("players")[0].childNodes;
	for (i = 0; i < x.length; i += 2) {
		players.push([x[i].childNodes[0].nodeValue, x[i + 1].childNodes[0].nodeValue]);
	}

	opponent = xmldocument.getElementsByTagName("opponent")[0].childNodes[0].nodeValue;
	
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
		document.getElementById("2").innerHTML = hand.toString() + "/" + field.toString() + "/" + fffDebug + "/" + isingame;
	}
	else { field = []; document.getElementById("2").innerHTML = hand.toString() + "/" + fffDebug + "/" + isingame; }

	cancharge = 0;
	mustkill = 0;
	supporting = 0;

	if (ischargeturn == 1 && chargerid == playerid) { console.log("you, charge"); cancharge = 1; }
	else if (chargerid == playerid) { console.log("you, kill"); mustkill = 1; }
	else { console.log("you, support"); supporting = 1; }

	if (oldtcard != tcard | oldplayers != players) { initialized++; InitializeGUI(); }
	if (cankill.length == 0) { GUI(); }
}

function GUI() {
	let str = '<img id="deckimg2"; src="imgs/card_img.png"> <div>' + deckstr + '</div>';
	document.getElementById('deck').innerHTML = str;

	if (mustkill == 0 || activeCard == -1) { GUIFlex(chosenCards, "chosen"); }
	else { GUIFlex("", "chosen"); }

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

	if (cankill.length == 0 && activeCard > -1) {
		try {
			document.getElementById('hand' + activeCard).classList.add("active");
		} catch (error) { console.log(error); }
	}

	str = "";
	if (mustkill == 0 && (supporting == 0 | field.length > 0)) {
		document.getElementById('fieldcontainer').classList.add("offset");
		for (let i = 0; i < opponent; i++) {
			str += '<div class=opponentflex> <div class=flexcontent> <img src="imgs/card_img.png"> </div> </div>';
		}
	} else {
		document.getElementById('fieldcontainer').classList.remove("offset");
	}
	document.getElementById('opponentflexcontainer').innerHTML = str;

	document.getElementById('fieldflexcontainer').innerHTML = "";
	if (mustkill == 1) {
		str = '<div class=fieldflex id=field_button> <img src="imgs/field_img.png" id="field_img" style=width:90%;height:auto> </div>';
		document.getElementById('fieldflexcontainer').innerHTML = str;
	}
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
	str = '<div class=fieldflex flex id=field' + field.length + ' style=width:1> <div style=height:37%> </div></div>'
	document.getElementById('fieldflexcontainer').innerHTML += str;

	while (1 < document.getElementById("player" + oldchargerid).children[0].childElementCount) {
		document.getElementById("player" + oldchargerid).children[0].children[1].remove();
	}
	document.getElementById("player" + chargerid).children[0].innerHTML += "<p> > </p>";
}
function GUIFlex(array, name) {

	let i;
	let str = "";

	if (array.length > 0 && mustkill == 0) {
		str += '<div class=' + name + 'flex id=' + name + 'charge style=margin:1rem> <img src="imgs/charge_img.png" id="charge_img" style=height:30%> </div>';
	}

	for (i = 0; i < array.length; i++) {
		let result = ParseCard(array, i);
		let suit = result[0];
		let value = result[1];
		let color = result[2];
		str += '<div class=' + name + 'flex id=' + name + i + '> <div class=flexcontent> <img src="imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div> </div>';

		hand.some(RemovePairs);
	}
	document.getElementById(name + 'flexcontainer').innerHTML = str;

	function RemovePairs(value, index) {
		if (parseInt(value[0]) == parseInt(array[i][0])) {
			if (parseInt(value[1]) == parseInt(array[i][1])) {
				hand.splice(index, 1);
			}
		}
	}
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

	document.getElementById("player" + playerid).classList.add("yellow");

	result = ParseCard([[tcard[0][0], "2"]], 0);
	suit = result[0];
	value = result[1];
	color = result[2];

	str = '<p style=color:' + color + ' >' + value + '<br>' + suit + '</p>';
	document.getElementById('newtrump').innerHTML += str;
	result = ParseCard(tcard, 0);
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
function Sumtin() {
	Input('Sumtin');
	let key = result.code;
	let oldActiveCard = activeCard;

	if (cankill.length == 0) { array = hand; }
	else { array = cankill; }

	switch (key) {
		case 'ArrowDown':
			activeCard = 0;
			console.log(activeCard);
			break;
		case 'ArrowUp':
			if (chosenCards.length > 0 || mustkill == 1) { activeCard = -1; }
			else { activeCard = 0; }
			console.log(activeCard);
			break;
		case 'ArrowRight':
			if (activeCard < array.length - 1) {
				activeCard++;
			}
			else { activeCard = -1; }
			console.log(activeCard);
			break;
		case 'ArrowLeft':
			if (activeCard > -1) {
				activeCard--;
			}
			else { activeCard = array.length - 1; }
			console.log(activeCard);
			break;
		case 'Space':
			//GetGameInfo();
			//GUI();
			//TODO remove this debug thing
			break;
		case 'Enter':
			if (mustkill == 1) { console.log("you killing"); KillTest(); }
			else { console.log("you charging"); ChargeTest(); }
			break;
		case 'Escape':
			let x = chosenCards.length;
			for (let i = 0; i < x; i++) {
				hand.push(chosenCards.splice(0, 1).flat());
			}
			cankill = [];
			hand.sort(); console.log(hand); console.log(activeCard);
			GUI();
			break;
	}

	if (mustkill == 1) {
		if (activeCard == -1) {
			document.getElementById('field_img').classList.add("active1");
		} else if (oldActiveCard == -1) {
			document.getElementById('field_img').classList.remove("active1");
		}
	} else {
		if (activeCard == -1 && chosenCards.length > 0) {
			document.getElementById('charge_img').classList.add("active1");
		} else if (oldActiveCard == -1 && chosenCards.length > 0) {
			document.getElementById('charge_img').classList.remove("active1");
		}
	}
	if (mustkill == 1 && cankill.length > 0) {
		console.log("ff");
		GUI();
		let result = ParseCard(chosenCards, 0);
		let suit = result[0];
		let value = result[1];
		let color = result[2];
		let str = ' <div class=killingflexcontent> <img src="imgs/card_img.png"> <p style=color:' + color + ' >' + value + '<br>' + suit + '</p> </div>';
		try {
			document.getElementById('field' + cankill[activeCard]).innerHTML += str;
		} catch (error) {/*console.error(error); */ }
	} else {
		try {
			document.getElementById('hand' + activeCard).classList.add("active");
		} catch (error) {/*console.error(error); */ }

		if (oldActiveCard !== activeCard) {
			try {
				document.getElementById('hand' + oldActiveCard).classList.remove("active");
			} catch (error) {/* console.error(error); */ }
		}
	}
}

function ChargeTest() {

	if (chosenCards.length > 0) {

		arr = chosenCards;
		if (activeCard == -1) {
			if (supporting == 1) {	//!!!!!!!!!!!!!!! might not work
				arr = chosenCards;
				if (field.length > 0 && field.some(HasPairs)) {
					console.log("field matches");
					Charge(chosenCards, supporting);
				} else {
					console.log("no match");
					AddAnimation('chosenflexcontainer', 'chosen', 'feedback');
				}
				return;
			}
			if (chosenCards.length > 1 && !chosenCards.every(HasPairs)) { //!!!!!!!!!!!!!!! .every might not work
				AddAnimation('chosenflexcontainer', 'chosen', 'feedback');
				return;
			}
			Charge(chosenCards, supporting);
			return;
		}


		let temp = [];
		temp[0] = hand.splice(activeCard, 1).flat();
		activeCard = hand.length;

		let x = 0;
		arr = chosenCards;
		if (temp.some(HasPairs)) {
			chosenCards.push(temp.splice(0, 1).flat()); x++;
			GUI();
			return;
		}

		arr = chosenCards;
		if (!chosenCards.some(HasPairs)) {
			hand.push(temp.splice(0, temp.length).flat());
			GUI();
			return;
		}

		arr = hand;
		if (supporting == 0 && temp.some(HasPairs)) {
			chosenCards.push(temp.splice(0, temp.length).flat()); x++;
			GUI();
			return;
		}

		if (x == 0) {
			console.log("that wasnt a pair");
			hand.push(temp.splice(0, temp.length).flat());
			console.log(chosenCards); console.log(hand);
			GUI();
		}
	} else { chosenCards.push(hand.splice(activeCard, 1).flat()); GUI(); }


}
function KillTest() {
	if (activeCard == -1) { Kill(chosenCards, activeCard); return; }//picks cards from field to hand 

	if (cankill.length > 0) {
		if (cankill[activeCard] < field.length) {
			Kill(chosenCards, cankill[activeCard]);
		} else { Charge(chosenCards, 1); }
		return;
	}

	console.log(hand);
	chosenCards = [hand.splice(activeCard, 1).flat()];
	console.log(hand);
	cankill = [];
	activeCard = -1;
	field.forEach(IsKillable);

	arr = chosenCards;
	if (field.some(HasPairs)) { cankill.push(field.length); }

	console.log(cankill); console.log(chosenCards); console.log(activeCard); console.log("Can kill:");
	for (i = 0; i < cankill.length; i++) { console.log(field[cankill[i]]); }

	if (cankill.length == 0) { console.log("hand:"); hand.push(chosenCards.splice(0, 1).flat()); console.log(hand); activeCard = 0; }

	GUI();
	function IsKillable(value, index, array) {
		console.log("testing field for killables");
		if (value[2] != 0) { return; }
		if (parseInt(chosenCards[0][0]) == tcard0) {
			if (parseInt(value[0]) != tcard0) { cankill.push(index); }
			else if (parseInt(value[1]) <= parseInt(chosenCards[0][1])) { cankill.push(index); }
		}
		else if (parseInt(value[0]) == parseInt(chosenCards[0][0]) && parseInt(value[1]) < parseInt(chosenCards[0][1])) { cankill.push(index); }
	}
}


function Charge(cards, isSupporting) {
	console.log(cancharge); console.log(isSupporting);
	console.log(cards.toString());

	const xhttp = new XMLHttpRequest();
	xhttp.onload = function () {
		str = this.responseText;
		console.log(str);
		if (str == "1" | str == "2") {
			AddAnimation('chosenflexcontainer', 'chosen', 'feedback');
			return;
		}
		document.getElementById("2").innerHTML = str;
		chosenCards.splice(0, chosenCards.length);
		console.log("you charged");
		if (mustkill == 0) {
			Draw();
		} else { cankill = []; GetGameInfo(); GUI(); }
	}
	xhttp.open("GET", "utils/Charge.php?Cards=" + cards + "&Support=" + isSupporting + "&UID=" + UId + "&ChargeTurn=" + ischargeturn + "&CID=" + chargerid);
	xhttp.send();
}

function Kill(card, killedId) {
	console.log(hand);
	console.log(card.toString());
	console.log("you killed id " + killedId);

	const xhttp = new XMLHttpRequest();
	xhttp.onload = function () {
		x = this.responseText;
		console.log(x);
		document.getElementById("3").innerHTML = x;
		chosenCards.splice(0, chosenCards.length);
		cankill = [];
		if (x != "0") { Draw(); }
		else {
			GetGameInfo();
			GUI();
		}
	}
	xhttp.open("GET", "utils/Kill.php?Card=" + card + "&KillsId=" + killedId + "&UID=" + UId);
	xhttp.send();
}

function Draw() {
	console.log("Drawing cards");

	const xhttp = new XMLHttpRequest();
	xhttp.onload = function () {
		GetGameInfo();
		GUI();
	}
	xhttp.open("GET", "utils/Draw.php?UID=" + UId);
	xhttp.send();
}


function ChangeTrump() {
	document.getElementById("trump").classList.remove("hidden");

	Input('ChangeTrump');
	let key = result.code;

	const xhttp = new XMLHttpRequest();
	xhttp.open("GET", "utils/ChangeTrump.php?UID=" + UId);
	xhttp.send();

	if (typeof key != 'undefined') {
		xhttp.onload = function () {
			document.getElementById("trump").classList.add("hidden");
			initialized = 0;
			Sumtin();
		}
	}
}

function Win(winstatus) {
	document.getElementById("win").classList.remove("hidden");
	if (winstatus == "0") {
		document.getElementById("win").innerHTML += "<div id=winmsg>Voitto</div>";
		document.getElementById("spectate").onclick = Spectate;
	} else if (winstatus == "1") {
		document.getElementById("win").innerHTML += "<div id=winmsg>P" + "&#228&#228" + "sit pois pelist" + "&#228" + "</div>";
		document.getElementById("spectate").onclick = Spectate;
	} else if (winstatus == "2") {
		document.getElementById("win").innerHTML += "<div id=winmsg>Tappio</div>";
		document.getElementById("spectate").style.color = "#888";
	} else {
		document.getElementById("win").innerHTML += "<div id=winmsg>Peli on p&#228&#228ttynyt</div>";
		document.getElementById("spectate").style.color = "#888";
	}

	document.getElementById("mainmenu").onclick = MainMenu;
	document.getElementById("leaderboard").style.color = "#888";

	function Spectate(event) {
		window.location.assign("CGSpectate.html");
		socket.close();
	}

	function MainMenu(event) {
		window.location.replace("Index.html");
		socket.close();
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
