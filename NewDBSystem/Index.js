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
let waitstatus;
let SID;
let info;
let status = [0, 0, 0];
let playertoggle = 0;
let logouttimer;

function GetUserInfo() {
	document.getElementById("container").classList.remove("hidden");
	const xhttp = new XMLHttpRequest();
	xhttp.onload = function () {
		let serverresponse = this.responseText;
		console.log(serverresponse);
		let UserInfo = JSON.parse(serverresponse);
		if (UserInfo.status == "-1") {
			SignupLogin();
		} else {
			waitstatus = UserInfo.status;
			SID = UserInfo.SID;
			RelogIn(UserInfo.lgexp);
			fml();
			MainMenu();
		}
	}
	xhttp.open("GET", "server/GetUserInfo.php");
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
		window.location.assign("SignUp.html");
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
		xhttp.open("POST", "server/AccountAPI.php");
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send("account=" + account + "&password=" + password + "&name=" + name + "&status=" + waitstatus+ "&action=1");

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

	document.getElementById("game").onclick = Game;
	document.getElementById("tutorial").onclick = Tutorial;
	document.getElementById("setup").onclick = SetUp;
	document.getElementById("leaderboard").onclick = Leaderboard;

	document.getElementById("playerstoggle").onclick = Players;
	document.getElementById("playerlabel0").onclick = function () { Label(0); }
	document.getElementById("playerlabel1").onclick = function () { Label(1); }
	document.getElementById("playerlabel2").onclick = function () { Label(2); }

	document.getElementById("accountsettings").onclick = function () { window.location.assign("AccountSettings.html"); }

	function Game(event) {
		if (info.isactive == "0" && waitstatus == 0) {
			waitstatus = 1;

			const xhttp = new XMLHttpRequest();
			xhttp.onload = function () {
				document.getElementById("game").innerHTML = "Odotetaan...";
			}

			xhttp.open("POST", "utils/SetStatus.php");
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xhttp.send("UID=" + UID + "&status=" + waitstatus);
		} else if (info.isactive == "0") {
			waitstatus = 0;
			const xhttp = new XMLHttpRequest();
			xhttp.onload = function () {
				document.getElementById("game").innerHTML = "Liity peliin";
			}

			xhttp.open("POST", "utils/SetStatus.php");
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xhttp.send("UID=" + UID + "&status=" + waitstatus);
		} else if (waitstatus == 0) {
			socket.close();
			window.location.assign("CGSpectate.html");
		}
	}

	function Tutorial(event) {
		socket.close();
		window.location.assign("CGTutorial.html");
	}
	function Leaderboard(event) {
		socket.close();
		window.location.assign("CGLeaderboard.html");
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

	function Label(x) {
		if (status[x] == 0) {
			document.getElementById("playertoggle" + x).innerHTML = "&lt&#8210&gt";
			document.getElementById("status" + x).classList.remove("hidden");
			status[x] = 1;
		} else {
			document.getElementById("playertoggle" + x).innerHTML = "&lt+&gt";
			document.getElementById("status" + x).classList.add("hidden");
			status[x] = 0;
		}
	}

}

function fml() {
	if (info.isactive == "1") {
		if (waitstatus == 0) {
			document.getElementById("game").innerHTML = "Seuraa peli&#228";
		} else if (waitstatus > 0) {
			document.getElementById("game").innerHTML = "Uudelleen ohjataan";
			socket.close();
			window.location.replace("CG.html");
		}
	} else {
		if (waitstatus == 0) {
			document.getElementById("game").innerHTML = "Liity peliin";
		}
		if (waitstatus == 1) {
			document.getElementById("game").innerHTML = "Odotetaan...";
		}
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
	xhttp.open("GET", "server/GetUsers.php");
	xhttp.send();
}

function RelogIn(seconds) {
	clearTimeout(logouttimer);
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
	//TODO Check functions after login to switchs to new db standard
	//TODO implement room system
	//TODO make CG work with room system
	//TODO redo relogin system 
	//TODO make websocket work with new systems (relog sys)
	//TODO (optional) reduce .php server executables to fewer files