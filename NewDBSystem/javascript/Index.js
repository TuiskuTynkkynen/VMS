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
	let data = event.data;
	console.log("Message from server ", data);
	let str = "{" + data + "}";
	info = JSON.parse(str);
	fml();
	GetUsers();
});
let userstatus;
let userlobby;
let SID;
let info;
let statustoggles = [0, 0, 0];
let playertoggle = 0;
let selectedlobby = -1;
let currentlobby;
let logouttimer;

function GetUserInfo() {
	//TODO figure out if this function and GetUserInfo.php or can be combined into AccountAPI
	document.getElementById("container").classList.remove("hidden");
	const xhttp = new XMLHttpRequest();
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
			}

			SID = UserInfo.SID;
			RelogIn(UserInfo.lgexp);
			fml();
			MainMenu();
		}
	}
	xhttp.open("GET", "/NewDBSystem/server/GetUserInfo.php");
	xhttp.send();
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
				RelogIn(900)
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
	document.getElementById("setup").onclick = SetUp;
	document.getElementById("leaderboard").onclick = Leaderboard;

	document.getElementById("playerstoggle").onclick = Players;
	document.getElementById("playerlabel0").onclick = function () { PlayersLabel(0); }
	document.getElementById("playerlabel1").onclick = function () { PlayersLabel(1); }
	document.getElementById("playerlabel2").onclick = function () { PlayersLabel(2); }

	document.getElementById("accountsettings").onclick = function () { window.location.assign("/NewDBSystem/AccountSettings.html"); }

	function Lobby(event) {
		GetLobbies();
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

	function SetUp() {
		window.open("CGSettings.php", "_blanck").focus();
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
	if (info.isactive == "1") {
		if (userstatus == 0) {
			document.getElementById("lobby").innerHTML = "Seuraa peli&#228";
		} else if (userstatus > 0) {
			document.getElementById("lobby").innerHTML = "Uudelleen ohjataan";
			socket.close();
			window.location.replace("CG.html");
		}
	} else {
		if (userstatus == 0) {
			document.getElementById("lobby").innerHTML = "Liity aulaan";
		}
		if (userstatus == 1) {
			document.getElementById("lobby").innerHTML = "Odotetaan...";
		}
	}
}

function GetLobbies() {
	const xhttp = new XMLHttpRequest();
	xhttp.open("POST", "/NewDBSystem/server/LobbyAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=0");

	xhttp.onload = function () {
		let x = this.responseText;
		console.log(x);
		if (x != "0") {
			if (selectedlobby != -1) {
				LobbyOnClick(selectedlobby);
			} else {
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
					document.getElementById("lobby" + i).onclick = function () { LobbyOnClick(lobbies.Lobbies[i].id); }
				}
			}

		}

	}
}

function LobbyOnClick(index) {
	selectedlobby = index;
	document.getElementById("lobbiescontainer").classList.add("hidden");
	GetLobbyInfo();
	document.getElementById("lobbycontainer").classList.remove("hidden");
}

function GetLobbyInfo() {
	const xhttp = new XMLHttpRequest();
	xhttp.open("POST", "/NewDBSystem/server/LobbyAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=1&id=" + selectedlobby);
	console.log(selectedlobby);
	if (userlobby == selectedlobby) {
		document.getElementById("inlobby").classList.remove("hidden");
		document.getElementById("outlobby").classList.add("hidden");
		document.getElementById("leave").onclick = function () {
			LobbyActions(4);
		}
	} else {
		document.getElementById("inlobby").classList.add("hidden");
		document.getElementById("outlobby").classList.remove("hidden");

		document.getElementById("join").onclick = function () { LobbyActions(3); }
	}

	xhttp.onload = function () {
		let x = this.responseText;
		console.log(x);

		let lobbyinfo = JSON.parse(x);
		let playercount = lobbyinfo.Players.length;
		let str = "";
		let isadmin;

		document.getElementById("lobbytittle").innerHTML = lobbyinfo.name;

		for (let i = 0; i < playercount; i++) {
			str += '<div id="player' + i + '" class="lobbyplayer">';
			if (lobbyinfo.Players[i].id == lobbyinfo.adminid) {
				if (lobbyinfo.Players[i].id == SID) {
					isadmin = true;
				} else {
					isadmin = false;
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
	}
}

function LobbyActions(action) {
	const xhttp = new XMLHttpRequest();
	xhttp.open("POST", "/NewDBSystem/server/LobbyAPI.php");
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	switch (action) {
		case 2:
			//TODO .send with action, name and password
			//TODO .onload func
			break;
		case 3:
			xhttp.send("action=" + action + "&id=" + selectedlobby);
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
			//TODO .send with action, decksize, suitcount, suitsize, handsize
			//TODO .onload func
			break;
		case 7:
			xhttp.send("action=" + action + "&id=" + userlobby);
			xhttp.onload = StartOnLoad();
			break;
	}

	return;

	function JoinOnLoad() {
		userlobby = selectedlobby;
		GetUserInfo();
		GetLobbyInfo(userlobby);
	}

	function LeaveOnLoad() {
		selectedlobby = -1;

		document.getElementById("lobbygui").classList.add("hidden");
		document.getElementById("lobbiescontainer").classList.add("hidden");
		document.getElementById("lobbycontainer").classList.add("hidden");

		GetUserInfo();

		document.getElementById("menu").classList.remove("hidden");
	}

	function StartOnLoad() {
		alert("TODO make CG version that works on new db system");
		window.location.replace("/NewDBSystem/VMS.html");
	}
}

function GetUsers() {

	const xhttp = new XMLHttpRequest();
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
	xhttp.open("GET", "/NewDBSystem/server/GetUsers.php");
	xhttp.send();
}

function RelogIn(seconds) {
	clearTimeout(logouttimer);
	//TODO remove debug return
	return;
	logouttimer = setTimeout(() => {
		document.getElementById("notice").classList.remove("hidden");
		document.getElementById("main").classList.add("hidden");
		document.addEventListener("keypress", HideNotice);
	}, seconds*1000);

	function HideNotice() {
		document.getElementById("notice").classList.add("hidden");
		LogIn();
		document.removeEventListener("keypress", HideNotice);
	}
}
	//TODO Check if functions need to be switched to new db standard
	//
	//TODO gui for lobby system
	//
	//TODO make CG work with lobby system
	//
	//TODO redo relogin system
	//TODO make websocket work with new systems (relog sys)
	//
	//TODO (optional) reduce .php server executables to fewer files