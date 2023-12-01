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
	GetUserInfo();
});

// Listen for messages
socket.addEventListener("message", (event) => {
	const data = event.data;
	console.log("Message from server ", data);
	const info = JSON.parse(data);

	if (islobbiesshown == 1) {
		UpdateLobbies();
	}

	if (info.hasOwnProperty("updatedlobbies") && info.updatedlobbies.includes(Number(selectedlobby))) {
		if (islobbyshown == 1) {
			UpdateLobby();
		} else if (selectedlobby >= 0){
			ShowLobby(selectedlobby);
		}
	}

	GetUserStatus();
	GetUsers();
});

let userstatus = -1;
let userlobby = -1;
let SID;
let statustoggles = [0, 0, 0];
let playertoggle = 0;
let selectedlobby = -1;
let islobbiesshown, islobbyshown = 0;
let isadmin;
let islobbyactive = 0;
let inactivitytimer;

function GetUserInfo() {
	document.getElementById("container").classList.remove("hidden");
	const xhttp = new XMLHttpRequest();
	xhttp.open("POST", "/NewDBSystem/server/SessionAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=0");
	xhttp.onload = function () {
		let serverresponse = this.responseText;
		console.log(serverresponse);
		let UserInfo = JSON.parse(serverresponse);
		if (UserInfo.status == "-1") {
			SignupLogin();
		} else {
			userstatus = UserInfo.status;
			userlobby = UserInfo.lobby;

			if (userstatus != 0) {
				selectedlobby = userlobby;
				ShowLobby(selectedlobby);
			}

			SID = UserInfo.SID;
			isadmin = (UserInfo.isadmin == 1) ? true : false;

			InactivityNotice(UserInfo.inactive);
			fml();
			MainMenu();
		}
	}
}

function GetUserStatus() {
	const xhttp = new XMLHttpRequest(); xhttp.open("POST", "/NewDBSystem/server/SessionAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=2");
	xhttp.onload = function () {
		if (this.responseText == "2") {
			window.location.replace("/NewDBSystem/VMS.html");
		} else if (this.responseText == "0") {
			userstatus = 0;
		} else if (this.responseText == "1") {
			userstatus = 1;
		}
		fml();
	}
}

function SignupLogin() {
	document.getElementById("sulicontainer").classList.remove("hidden");
	document.getElementById("lin").onclick = function () {
		document.getElementById("sulicontainer").classList.add("hidden");
		LogIn();
	}
	document.getElementById("sup").onclick = SignUp;

	function SignUp() {
		window.location.assign("/NewDBSystem/SignUp.html");
	}
}

function LogIn() {
	document.getElementById("login").classList.remove("hidden");
	document.getElementById("account").disabled = false;
	document.getElementById("PIN").disabled = false;
	document.getElementById("inputtxt").disabled = false;

	document.getElementById("inputs").addEventListener("keydown", function (event) {
		if (event.key == "Escape") {
			document.getElementById("login").classList.add("hidden");
			document.getElementById("account").disabled = true;
			document.getElementById("PIN").disabled = true;
			document.getElementById("inputtxt").disabled = true;
			if (UserInfo.status == "-1") {
				SignupLogin();
			}
		}
	})
	document.getElementById("account").addEventListener("keypress", function (event) {
		if (event.key == "Enter") { document.getElementById("PIN").focus(); }
	});
	document.getElementById("PIN").addEventListener("keypress", function (event) {
		if (event.key == "Enter") { document.getElementById("inputtxt").focus(); }
	});
	document.getElementById("inputtxt").addEventListener("keypress", function (event) {
		if (event.key == "Enter") { Check(); }
	});

	function Check() {
		let account = document.getElementById("account").value;
		let password = document.getElementById("PIN").value;
		let name = document.getElementById("inputtxt").value;

		const xhttp = new XMLHttpRequest();
		xhttp.open("POST", "/NewDBSystem/server/AccountAPI.php");
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("account=" + account + "&password=" + password + "&name=" + name + "&status=" + userstatus+ "&action=1");

		xhttp.onload = function () {
			let serverresponse = this.responseText;
			if (serverresponse == "0") {
				document.getElementById("login").classList.add("hidden");
				document.getElementById("account").disabled = true;
				document.getElementById("PIN").disabled = true;
				document.getElementById("inputtxt").disabled = true;
				InactivityNotice(29*60)
				GetUserInfo();
			} else if (serverresponse == "1") {
				document.getElementById("incorrect").innerHTML = "Väärä käyttäjänimi tai salasana";
			} else {
				console.log(serverresponse);
				alert("Ota järjestelmänvalvojaan yhteyttä");
				document.getElementById("incorrect").innerHTML = "Ennalta arvaamaton virhe, ota järjestelmänvalvojaan yhteyttä";
			}
		}
		
	}
}

function MainMenu() {
	document.getElementById("main").classList.remove("hidden");
	
	document.getElementById("lobby").onclick = Lobby;
	document.getElementById("tutorial").onclick = Tutorial;
	document.getElementById("leaderboard").onclick = Leaderboard;

	document.getElementById("playerstoggle").onclick = Players;
	document.getElementById("playerlabel0").onclick = function () { PlayersLabel(0); }
	document.getElementById("playerlabel1").onclick = function () { PlayersLabel(1); }
	document.getElementById("playerlabel2").onclick = function () { PlayersLabel(2); }

	document.getElementById("accountsettings").onclick = function () { window.location.assign("/NewDBSystem/AccountSettings.html"); }

	function Lobby(event) {
		ShowLobbies();

		document.getElementById("lobbygui").classList.remove("hidden");

		if (userstatus == 0) {
			selectedlobby = -1;
			document.getElementById("lobbiescontainer").classList.remove("hidden");
		}
	}

	function Tutorial(event) {
		socket.close();
		window.location.assign("/NewDBSystem/Tutorial.html");
	}
	function Leaderboard(event) {
		socket.close();
		window.location.assign("/NewDBSystem/Leaderboard.html");
	}

	function Players() {
		if (playertoggle == 0) {
			document.getElementById("playercontainer").classList.remove("hidden");
			playertoggle = 1;
			GetUsers();
		} else {
			document.getElementById("playercontainer").classList.add("hidden");
			playertoggle = 0;
		}
	}

	function PlayersLabel(x) {
		if (statustoggles[x] == 0) {
			document.getElementById("playertoggle" + x).innerHTML = "&lt&#8210&gt";
			document.getElementById("status" + x).classList.remove("hidden");
			statustoggles[x] = 1;
		} else {
			document.getElementById("playertoggle" + x).innerHTML = "&lt+&gt";
			document.getElementById("status" + x).classList.add("hidden");
			statustoggles[x] = 0;
		}
	}

}

function fml() {
	if (userstatus == 0) {
		document.getElementById("lobby").innerHTML = "Liity aulaan";
	} else if (userstatus == 1) {
		if (isadmin) {
			document.getElementById("lobby").innerHTML = "Aloita peli";
		} else {
			document.getElementById("lobby").innerHTML = "Odotetaan...";
		}

		if (islobbyactive == "1") {
			document.getElementById("spectate").style.color = "";
			document.getElementById("spectate").onclick = function () {
				window.location.assign("/NewDBSystem/Spectate.html");
			}
			return;
		}
	} else if (userstatus == 2) {
		window.location.replace("/NewDBSystem/VMS.html");
	}

	document.getElementById("spectate").style.color = "#555";
	document.getElementById("spectate").onclick = "";

}

function ShowLobbies() {
	if (selectedlobby != -1) {
		ShowLobby(selectedlobby);
		return;
	}

	islobbiesshown = 1;
	islobbyshown = 0;

	UpdateLobbies();

	document.getElementById("create").onclick = function () { LobbyActions(2); }
	document.getElementById("lobbiesreturn").onclick = function () {
		islobbiesshown = islobbyshown = 0;
		document.getElementById("lobbygui").classList.add("hidden");
		document.getElementById("lobbiescontainer").classList.add("hidden");
		document.getElementById("lobbycontainer").classList.add("hidden");

	}
}
function UpdateLobbies() {
	const xhttp = new XMLHttpRequest();
	xhttp.open("POST", "/NewDBSystem/server/LobbyAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=0");

	xhttp.onload = function () {
		let x = this.responseText;
		console.log(x);
		if (x != "0") {
			let lobbies = JSON.parse(x);
			let lobbycount = lobbies.Lobbies.length;
			let str = "";
			for (let i = 0; i < lobbycount; i++) {
				str += '<div id="lobby' + i + '" class="lobbiesbutton">';
				if (lobbies.Lobbies[i].haspassword == "1") {
					str += '<img src="/imgs/padlock_img">';
				}
				str += '<p>' + lobbies.Lobbies[i].name + '</p></div> ';
			}
			document.getElementById('lobbies').innerHTML = str;
			str = "";
			for (let i = 0; i < lobbycount; i++) {
				document.getElementById("lobby" + i).onclick = function () { ShowLobby(lobbies.Lobbies[i].id); }
			}
		}
	}
}


function ShowLobby(index) {
	islobbiesshown = 0;
	islobbyshown = 1;
	selectedlobby = index;
	UpdateLobby();
	
	if (userlobby == selectedlobby) {
		document.getElementById("inlobby").classList.remove("hidden");
		document.getElementById("outlobby").classList.add("hidden");
		document.getElementById("leave").onclick = function () {
			LobbyActions(4);
		}
		document.getElementById("inlobbyreturn").onclick = function () {
			islobbiesshown = islobbyshown = 0;
			document.getElementById("lobbygui").classList.add("hidden");
			document.getElementById("lobbycontainer").classList.add("hidden");
		}
	} else {
		document.getElementById("inlobby").classList.add("hidden");
		document.getElementById("outlobby").classList.remove("hidden");
		document.getElementById("outlobbyreturn").onclick = function () {
			islobbyshown = 0;
			document.getElementById("lobbycontainer").classList.add("hidden");
			islobbiesshown = 1;
			document.getElementById("lobbiescontainer").classList.remove("hidden");
		}
	}

	document.getElementById("lobbiescontainer").classList.add("hidden");
	document.getElementById("lobbycontainer").classList.remove("hidden");
}

function UpdateLobby() {
	if (selectedlobby < 0) {
		return;
	}
	const xhttp = new XMLHttpRequest();
	xhttp.open("POST", "/NewDBSystem/server/LobbyAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=1&id=" + selectedlobby);

	xhttp.onload = function () {
		let x = this.responseText;
		console.log(x);

		let lobbyinfo = JSON.parse(x);
		let playercount = lobbyinfo.Players.length;
		let str = "";
		isadmin = false;

		document.getElementById("lobbytittle").innerHTML = lobbyinfo.name;

		if (userlobby != selectedlobby) {
			if (lobbyinfo.haspassword == 0) {
				document.getElementById("join").onclick = function () { LobbyActions(3); }
			} else {
				document.getElementById("join").onclick = function () { LobbyActions(9); }
			}
		} else {
			islobbyactive = lobbyinfo.isactive;
		}

		for (let i = 0; i < playercount; i++) {
			str += '<div id="player' + i + '" class="lobbyplayer">';
			if (lobbyinfo.Players[i].id == lobbyinfo.adminid) {
				if (lobbyinfo.Players[i].id == SID) {
					isadmin = true;
				}
				str += '<img src="/imgs/crown_img">';
				str += '<p style="color:#f2c511">';

			} else if (lobbyinfo.Players[i].id == SID){
				str += '<p style="color:#8034eb">';
			} else {
				str += '<p style="color:#fff">';
			}
			str += lobbyinfo.Players[i].nickname + '</p></div> ';
		}

		document.getElementById('lobbyplayers').innerHTML = str;
		str = "";

		if (isadmin) {
			document.getElementById("delete").classList.remove("disabled");
			document.getElementById("settings").classList.remove("disabled");
			document.getElementById("startgame").classList.remove("disabled");
			document.getElementById("delete").onclick = function () { LobbyActions(5); }
			document.getElementById("settings").onclick = function () { LobbyActions(6); }
			document.getElementById("startgame").onclick = function () { LobbyActions(7); }
		} else {
			document.getElementById("delete").classList.add("disabled");
			document.getElementById("settings").classList.add("disabled");
			document.getElementById("startgame").classList.add("disabled");
		}

		fml();
	}
}

function LobbyActions(action) {
	let xhttp;
	if (action != 2 && action != 6 && action != 9) {
		xhttp = new XMLHttpRequest();
		xhttp.open("POST", "/NewDBSystem/server/LobbyAPI.php");
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	}

	switch (action) {
		case 2:
			CreateLobby();
			break;
		case 3:
			xhttp.send("action=" + action + "&id=" + selectedlobby + "&password=");
			xhttp.onload = JoinOnLoad();
			break;
		case 4:
			xhttp.send("action=" + action + "&id=" + userlobby);
			xhttp.onload = LeaveOnLoad();
			break;
		case 5:
			xhttp.send("action=" + action + "&id=" + userlobby);
			xhttp.onload = LeaveOnLoad();
			break;
		case 6:
			LobbySettings();
			break;
		case 7:
			xhttp.send("action=" + action + "&id=" + userlobby);
			xhttp.onload = StartOnLoad();
			break;
		case 9:
			JoinPassword()
			break;
	}

	return;

	function CreateLobby() {
		document.getElementById("lobbiescontainer").classList.add("hidden");
		document.getElementById("lobbyinputs").classList.remove("hidden");
		document.getElementById("createlobby").classList.remove("hidden");

		document.getElementById("passwordtoggle").onclick = function () {
			let disabled = document.getElementById("lobbypassword").disabled ? false : true;
			document.getElementById("lobbypassword").disabled = disabled;
		}

		document.getElementById("return").onclick = function () {
			islobbiesshown = 1;
			document.getElementById("lobbiescontainer").classList.remove("hidden");
			document.getElementById("lobbyinputs").classList.add("hidden");
			document.getElementById("createlobby").classList.add("hidden");
		}

		document.getElementById("submit").onclick = function () {
			let lobbyname = document.getElementById("lobbyname").value;
			if (lobbyname == "") {
				document.getElementById("clincorrect").innerHTML = "Täytä kaikki kentät";
				return;
			}
			let lobbypassword = "";
			if (document.getElementById("lobbypassword").disabled == false) {
				lobbypassword = document.getElementById("lobbypassword").value;
				if (lobbypassword == "") {
					document.getElementById("clincorrect").innerHTML = "Täytä kaikki kentät";
					return;
				}
			}

			xhttp = new XMLHttpRequest();
			xhttp.open("POST", "/NewDBSystem/server/LobbyAPI.php");
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xhttp.send("action=" + action + "&name=" + lobbyname + "&password=" + lobbypassword);

			xhttp.onload = function () {
				let response = this.responseText;
				if (response == 2) {
					document.getElementById("clincorrect").innerHTML = "Nimi on jo käytössä";
				} else if (response[0] == 0) {
					response = response.substring(1);
					userlobby = selectedlobby = response;
					islobbiesshown = 0;
					islobbyshown = 1;
					GetUserInfo();

					document.getElementById("lobbycontainer").classList.remove("hidden");
					document.getElementById("lobbyinputs").classList.add("hidden");
					document.getElementById("createlobby").classList.add("hidden");

					
					ShowLobby(userlobby);

					document.getElementById("clincorrect").innerHTML = "";
					document.getElementById("lobbypassword").value = "";
					document.getElementById("lobbyname").value = "";
				} else {
					console.log(response);
					document.getElementById("clincorrect").innerHTML = "Ennalta odottamaton virhe";
				}
			}
		}

	}

	function JoinOnLoad() {
		userlobby = selectedlobby;
		GetUserInfo();
		ShowLobby(userlobby);
	}

	function LeaveOnLoad() {
		islobbyshown = islobbiesshown = 0;
		selectedlobby = userlobby = -1;

		document.getElementById("lobbygui").classList.add("hidden");
		document.getElementById("lobbiescontainer").classList.add("hidden");
		document.getElementById("lobbycontainer").classList.add("hidden");

		GetUserInfo();

		document.getElementById("menu").classList.remove("hidden");
	}

	function StartOnLoad() {
		window.location.replace("/NewDBSystem/VMS.html");
	}

	function LobbySettings() {
		document.getElementById("lobbycontainer").classList.add("hidden");
		document.getElementById("lobbyinputs").classList.remove("hidden");
		document.getElementById("lobbysettings").classList.remove("hidden");

		document.getElementById("return").onclick = function () {
			document.getElementById("lobbycontainer").classList.remove("hidden");
			document.getElementById("lobbyinputs").classList.add("hidden");
			document.getElementById("lobbysettings").classList.add("hidden");
		}

		document.getElementById("submit").onclick = function () {
			let decksize = document.getElementById("decksize").value;
			let suitcount = document.getElementById("suitcount").value;
			let suitsize = document.getElementById("suitsize").value;
			let handsize = document.getElementById("handsize").value;

			if (decksize == "" || suitcount == "" || suitsize == "" || handsize == "") {
				document.getElementById("lsincorrect").innerHTML = "Täytä kaikki kentät";
				return;
			}

			xhttp = new XMLHttpRequest();
			xhttp.open("POST", "/NewDBSystem/server/LobbyAPI.php");
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xhttp.send("action=6" + "&id=" + userlobby + "&decksize=" + decksize + "&suitcount=" + suitcount + "&suitsize=" + suitsize + "&handsize=" + handsize);

			xhttp.onload = function () {
				let response = this.responseText;
				if (response == 0) {
					document.getElementById("lobbycontainer").classList.remove("hidden");
					document.getElementById("lobbyinputs").classList.add("hidden");
					document.getElementById("lobbysettings").classList.add("hidden");

					document.getElementById("lsincorrect").innerHTML = "";
				} else {
					console.log(response);
					document.getElementById("lsincorrect").innerHTML = "Ennalta odottamaton virhe";
					return;
				}
			}
		}

	}

	function JoinPassword() {
		document.getElementById("lobbycontainer").classList.add("hidden");
		document.getElementById("lobbyinputs").classList.remove("hidden");
		document.getElementById("joinpassword").classList.remove("hidden");

		document.getElementById("return").onclick = function () {
			document.getElementById("lobbycontainer").classList.remove("hidden");
			document.getElementById("lobbyinputs").classList.add("hidden");
			document.getElementById("lobbypassword").classList.add("hidden");
		}

		document.getElementById("submit").onclick = function () {
			let password = document.getElementById("password").value;
			if (password == "") {
					document.getElementById("jpincorrect").innerHTML = "Täytä kaikki kentät";
					return;
			}

			const xhttp = new XMLHttpRequest();
			xhttp.open("POST", "/NewDBSystem/server/LobbyAPI.php");
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xhttp.send("action=3" + "&id=" + selectedlobby + "&password=" + password);

			xhttp.onload = function () {
				let response = this.responseText;
				if (response == 3) {
					document.getElementById("jpincorrect").innerHTML = "Väärä salasana";
					return;
				} else if (response == 0) {
					document.getElementById("lobbycontainer").classList.remove("hidden");
					document.getElementById("lobbyinputs").classList.add("hidden");
					document.getElementById("lobbypassword").classList.add("hidden");

					document.getElementById("jpincorrect").innerHTML = ""; 
					document.getElementById("password").value = "";
					JoinOnLoad();
				} else {
					console.log(response);
					document.getElementById("jpincorrect").innerHTML = "Ennalta odottamaton virhe";
					return;
				}
			}
		}

	}
}

function GetUsers() {
	const xhttp = new XMLHttpRequest();
	xhttp.open("POST", "/NewDBSystem/server/SessionAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=1");
	xhttp.onload = function () {
		let x = this.responseText;
		let Users = JSON.parse(x);
		let statuses = [0, 0, 0];
		let str = ["", "", ""];
		for (let i = 0; i < Users.Users.length; i++) {
			str[Users.Users[i].status] += '<p class="nick"> ' + Users.Users[i].nick + ' </p>';
			statuses[Users.Users[i].status]++;
		}
		document.getElementById('status0').innerHTML = str[0];
		document.getElementById('status1').innerHTML = str[1];
		document.getElementById('status2').innerHTML = str[2];

		document.getElementById('playercount0').innerHTML = "(" + statuses[0] + ")";
		document.getElementById('playercount1').innerHTML = "(" + statuses[1] + ")";
		document.getElementById('playercount2').innerHTML = "(" + statuses[2] + ")";

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
	}, seconds*1000);

	function HideNotice() {
		document.getElementById("notice").classList.add("hidden");
		window.location.reload();
	}
}
	//TODO make CG work with lobby system
	//
	//TODO (optional) reduce .php server executables to fewer files