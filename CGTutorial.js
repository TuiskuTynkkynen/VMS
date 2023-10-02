let activeCard = -1;
let target = 3;
let chosenCards = [];
let field = [];
let chargerid = 0;
let oldchargerid = 0;
let players = [["0", "Pelaaja1"], ["1", "Pelaaja2"]];
let isingame = 1;
let hand = [[1, 7], [2, 2], [2, 5], [3, 2], [3, 9], [3, 11]];
let tcard = [[1, 2]];
let deckstr = "&thickapprox;30";
let notice = 0;
let mustkill = 0;
let supporting = 0;
let opponent = 6;

//TODO figure out which of these are needed
let cancharge;
let arr;
let cankill = [];
let playerid = 0;
let initialized = 0;

InitializeGUI();
GUI();
Input('Sumtin');

async function Input(funcName) {

	document.onkeydown = function Rr(event) {
		//console.log(event);
		result = event;
		if (isingame == 1) { window[funcName](); }
		return;
	};
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
	oldchargerid = chargerid;
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

	str = '<div> <div> <p style="width:100%; text-align:center">Pelaajat:</p> </div> </div>';
	for (let i = 0; i < players.length; i++) {
		str += '<div id="player' + players[i][0] + '"> <div> <p class="nick"> ' + players[i][1] + ' </p> </div> </div>';
	}
	document.getElementById('playerinfo').innerHTML = str;

	document.getElementById("player" + playerid).classList.add("yellow");

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

function Notice() {
	notice++;
	switch (notice) {
		case 1:
			document.getElementById("ntctittle").innerHTML = "Tutoriaali:";
			document.getElementById("ntcmain").innerHTML = "Kädessäsi olevat kortit näkyvät tässä";
			document.getElementById("handflexcontainer").classList.add("highlight");
			document.getElementById("notice").style.bottom = "25%";
			break;
		case 2:
			document.getElementById("ntcmain").innerHTML = "Valttikortti näkyy tässä";
			document.getElementById("handflexcontainer").classList.remove("highlight");
			document.getElementById("trumpcard").classList.add("highlight");
			document.getElementById("notice").style.bottom = "62.5%";
			document.getElementById("notice").style.left = "15%";
			break;
		case 3:
			document.getElementById("ntcmain").innerHTML = "Pakassa jäljellä olevien korttien määrä näkyy tässä. Lukumäärä ilmoitetaan likiarvona 10 korttiin saakka";
			document.getElementById("trumpcard").classList.remove("highlight");
			document.getElementById("deckimg2").classList.add("highlight");
			break;
		case 4:
			document.getElementById("ntcmain").innerHTML = "Vastustajallasi olevien korttien määrä näkyy tässä. Kentälle ei voi ajaa niiden määrää enempää kortteja";
			document.getElementById("deckimg2").classList.remove("highlight");
			document.getElementById("opponentflexcontainer").classList.add("highlight");
			document.getElementById("notice").style.bottom = "50%";
			document.getElementById("notice").style.left = "";
			break;
		case 5:
			document.getElementById("ntcmain").innerHTML = "Pelaajien järjestys näkyy tässä: sinä olet merkattuna keltaisella";
			document.getElementById("opponentflexcontainer").classList.remove("highlight");
			document.getElementById("playerinfo").classList.add("highlight");
			document.getElementById("notice").style.bottom = "62.5%";
			document.getElementById("notice").style.left = "53%";
			break;
		case 6:
			document.getElementById("ntcmain").innerHTML = "Vuoroa merkkaava &ldquo;>&rdquo; symboli on sinun kohdallasi, joten on sinun vuoro ajaa";
			break;
		case 7:
			document.getElementById("ntcmain").innerHTML = "Voit vaihataa aktiivista korttia oikealla ja vasemalla nuolinäppäimellä";
			document.getElementById("ntcaux").innerHTML = "Valitse ruutu 2";
			document.getElementById("playerinfo").classList.remove("highlight");
			document.getElementById("notice").style.bottom = "25%";
			document.getElementById("notice").style.left = "";
			return false;
			break;
		case 8:
			if (activeCard == target) {
				document.getElementById("ntcmain").innerHTML = "Voit vahvistaa valinnan painamalla enter näppäintä";
				document.getElementById("ntcaux").innerHTML = "Vahvista valinta";
			} else { notice--; }
			return false;
			break;
		case 9:
			if (chosenCards.length > 0) {
				document.getElementById("ntcmain").innerHTML = "Valitsemasi kortit näkyvät tässä";
				document.getElementById("ntcaux").innerHTML = "Paina mitä tahansa näppäintä jatkaaksesi";
				document.getElementById("chosenflexcontainer").style.width = "25%";
				document.getElementById("chosenflexcontainer").style.transform = "translate(20%)";
				document.getElementById("chosenflexcontainer").classList.add("highlight");
				document.getElementById("notice").style.left = "30%";
			} else { notice--; }
			return false;
			break;
		case 10:
			target = 1;
			document.getElementById("ntcmain").innerHTML = "Voit valita usean kortin, jos ne ovat pareja";
			document.getElementById("ntcaux").innerHTML = "Valitse hertta 2 ja vahvista valinta";
			document.getElementById("chosenflexcontainer").classList.remove("highlight");
			document.getElementById("chosenflexcontainer").style.width = "";
			document.getElementById("chosenflexcontainer").style.transform = "";
			document.getElementById("notice").style.left = "";
			return false;
			break;
		case 11:
			if (chosenCards.length > 1) {
				target = activeCard;
				document.getElementById("ntcmain").innerHTML = "Voit siirtyä käden, sekä valittujen korttien välillä ylös ja alas nuolinäppäimillä";
				document.getElementById("ntcaux").innerHTML = "Valitse &ldquo;AJA&rdquo; nappula";
			} else { notice--; }
			return false;
			break;
		case 12:
			if (activeCard == -1) {
				target = -1;
				document.getElementById("ntcmain").innerHTML = "Voit ajaa painamalla enter näppäintä, kun &ldquo;AJA&rdquo; nappula on valittuna";
				document.getElementById("ntcaux").innerHTML = "Aja";
			} else { notice--; }
			return false;
			break;
		case 13:
			if (chosenCards.length == 0) {
				hand.push([4, 5], [4, 12]);
				GUI();
				document.getElementById("ntcmain").innerHTML = "Ajamisen jälkeen nostat uudet kortit automaattisesti";
				document.getElementById("ntcaux").innerHTML = "Paina mitä tahansa näppäintä jatkaaksesi";
			} else { notice--; }
			return false;
			break;
		case 14:
			if (chosenCards.length == 0) {
				document.getElementById("ntcmain").innerHTML = "Vuoroa merkkaava &ldquo;>&rdquo; symboli on vastustajasi kohdalla, joten on heidän vuoro tappaa";
				document.getElementById("playerinfo").classList.add("highlight");
				document.getElementById("notice").style.bottom = "62.5%";
				document.getElementById("notice").style.left = "53%";
			} else { notice--; }
			break;
		case 15:
			document.getElementById("ntcmain").innerHTML = "Vastustajallesi ajetut kortit näkyvät tässä";
			document.getElementById("playerinfo").classList.remove("highlight");
			document.getElementById("fieldflexcontainer").classList.add("highlight");
			document.getElementById("notice").style.bottom = "";
			document.getElementById("notice").style.left = "";
			break;
		case 16:
			field.push([2, 9, 2, 1]);
			GUI();
			target = 2;
			document.getElementById("ntcmain").innerHTML = "Vastustajasi tappoi hertta ysillä, joten voit ajaa ruutu ysin rintaan";
			document.getElementById("ntcaux").innerHTML = "Valitse ruutu yhdeksän ja aja se";
			document.getElementById("fieldflexcontainer").classList.remove("highlight");
			document.getElementById("notice").style.bottom = "25%";
			return false;
			break;
		case 17:
			target = (chosenCards.length > 0) ? -1 : target;
			if (field.length == 4) {
				document.getElementById("ntcmain").innerHTML = "Et voi ajaa muita kortteja rintaan, joten sinun on odotettava vastustajaasi";
				document.getElementById("ntcaux").innerHTML = "Paina mitä tahansa näppäintä jatkaaksesi";
			} else { notice--; }
			return false;
			break;
		case 18:
			field.push([3, 6, 2, 0], [3, 13, 2, 3]);
			GUI();
			break;
		case 19:
			field = [];
			GUI();
			document.getElementById("ntcmain").innerHTML = "Vastustajasi tappoi kentällä olevat kortit";
			document.getElementById("notice").style.bottom = "";
			break;
		case 20:
			document.getElementById("ntcmain").innerHTML = "Vuoroa merkkaava &ldquo;>&rdquo; symboli on vastustajasi kohdalla, joten on heidän vuoro ajaa";
			document.getElementById("playerinfo").classList.add("highlight");
			document.getElementById("notice").style.bottom = "62.5%";
			document.getElementById("notice").style.left = "53%";
			break;
		case 21:
			field.push([2, 3, 0, null]);
			mustkill = 1;
			GUI();
			target = 1;
			document.getElementById("ntcmain").innerHTML = "Vastustaja ajoi sinulle hertta kolmosen";
			document.getElementById("ntcaux").innerHTML = "Valitse hertta vitonen";
			document.getElementById("playerinfo").classList.remove("highlight");
			document.getElementById("notice").style.bottom = "50%";
			document.getElementById("notice").style.left = "";
			return false;
			break;
		case 22:
			if (chosenCards.length > 0) {
				target = 0;
				document.getElementById("ntcmain").innerHTML = "Voit siirtää valittua korttia oikealla ja vasemmalla nuolinäppäimellä";
				document.getElementById("ntcaux").innerHTML = "Siirrä kortti hertta kolmosen päälle ja tapa se enter näppäimellä";
			} else { notice--; }
			return false;
			break;
		case 23:
			if (field.length > 1) {
				target = 2;
				document.getElementById("ntcmain").innerHTML = "Voit ajaa risti vitosen itsellesi rintaan";
				document.getElementById("ntcaux").innerHTML = "Valitse risti viisi ja aja se rintaan";
			} else { notice--; }
			return false;
			break;
		case 24:
			target = (chosenCards.length > 0) ? 0 : target;
			if (field.length > 2) {
				target = 2;
				document.getElementById("ntcmain").innerHTML = "Voit tappaa risti vitosen risti akalla";
				document.getElementById("ntcaux").innerHTML = "Tapa risti viisi";
			} else { notice--; }
			return false;
			break;
		case 25:
			target = (chosenCards.length > 0) ? 0 : target;
			if (field.length > 3) {
				field.push([3, 12, 0, null]);
				GUI();
				document.getElementById("ntcmain").innerHTML = "Vastustajasi ajoi kortin, jonka voit tappaa vain valtilla";
				document.getElementById("ntcaux").innerHTML = "Tapa ruutu akka";
			} else { notice--; }
			return false;
			break;
		case 26:
			target = (chosenCards.length > 0) ? 0 : target;
			if (field.length > 5) {
				target = -1;
				document.getElementById("ntcmain").innerHTML = "Käsissä ei ole rinnattavia kortteja, joten voit tappaa kentän";
				document.getElementById("ntcaux").innerHTML = "Valitse &ldquo;NOSTA/TAPA&rdquo; nappula ja paina enter";
			} else { notice--; }
			return false;
			break;
		case 27:
			if (field.length == 0) {
				mustkill = 0;
				hand = [[1, 3], [1, 8], [2, 13], [3, 11], [4, 7], [4, 9]];
				GUI();
				document.getElementById("ntcmain").innerHTML = "Tappamisen jälkeen nostat uudet kortit automaattisesti";
				document.getElementById("ntcaux").innerHTML = "Paina mitä tahansa näppäintä jatkaaksesi";
				document.getElementById("notice").style.bottom = "25%";
			} else { notice--; }
			return false;
			break;
		case 28:
			document.getElementById("ntcmain").innerHTML = "Tutoriaali loppuu tähän, ja seuraavaksi sinut uudelleen ohjataan aloitus sivulle";
			document.getElementById("ntcaux").innerHTML = "Paina mitä tahansa näppäintä jatkaaksesi";
			document.getElementById("notice").style.bottom = "";
			document.getElementById("container").innerHTML = '<img src="imgs/bg_image.png" ; id="bg_img">';
			break;
		case 29:
			window.location.assign("Index.html");
			break;
		//TODO continue making these
		default:
			return false;
	}
	return true;
}

function Sumtin() {
	
	if (Notice()) { return; }

	let key = result.code;
	let oldActiveCard = activeCard;

	if (cankill.length == 0) { array = hand; }
	else { array = cankill; }

	switch (key) {
		case 'ArrowDown':
			if (activeCard == target) { break; }
			activeCard = 0;
			console.log(activeCard);
			break;
		case 'ArrowUp':
			if (chosenCards.length > 0 || mustkill == 1) { activeCard = -1; }
			else { activeCard = 0; }
			console.log(activeCard);
			break;
		case 'ArrowRight':
			if (activeCard == target) { break; }
			if (activeCard < array.length - 1) {
				activeCard++;
			}
			else { activeCard = -1; }
			console.log(activeCard);
			break;
		case 'ArrowLeft':
			if (activeCard == target) { break; }
			if (activeCard > -1) {
				activeCard--;
			}
			else { activeCard = array.length - 1; }
			console.log(activeCard);
			break;
		case 'Enter':
			if (activeCard != target) { break; }
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
	Notice();

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
					Charge();
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
			Charge();
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
		if (parseInt(chosenCards[0][0]) == tcard[0][0]) {
			if (parseInt(value[0]) != tcard[0][0]) { cankill.push(index); }
			else if (parseInt(value[1]) <= parseInt(chosenCards[0][1])) { cankill.push(index); }
		}
		else if (parseInt(value[0]) == parseInt(chosenCards[0][0]) && parseInt(value[1]) < parseInt(chosenCards[0][1])) { cankill.push(index); }
	}
}


function Charge() {
	console.log("charging");
	for (let i = 0; i < chosenCards.length; i++) {
		let card = [chosenCards[i][0], chosenCards[i][1], 0, null];
		field.push(card);
	}
	chosenCards = [];
	cankill = [];
	chargerid = (supporting == 0) ? 1 : chargerid;
	GUI();
}

function Kill(card, killedId) {
	console.log("you killed id " + killedId);
	if (killedId == -1) {
		chargerid = (chargerid == 0) ? 1 : 0;
		field = [];
		GUI();
		return;
	}
	field.push([card[0][0], card[0][1], 2, killedId]);
	field[killedId][2] = 1;
	chosenCards = [];
	cankill = [];
	GUI();
}

function Draw() {
	console.log("Drawing cards");
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
	document.getElementById("leaderboard").onclick = Leaderboard;

	function MainMenu(event) {
		window.location.replace("Index.html");
		socket.close();
	}

	function Spectate(event) {
		window.location.assign("CGSpectate.html");
		socket.close();
	}
	function Leaderboard(event) {
		window.location.assign("CGLeaderboard.html");
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
