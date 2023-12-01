const beforeUnloadHandler = (event) => {
    //set updaterequired = true -> needs to update within 1 min or session will be deleted
    const xhttp = new XMLHttpRequest();
    xhttp.open("POST", "/NewDBSystem/server/AccountAPI.php");
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("action=6");

    return "d";
};
window.addEventListener("beforeunload", beforeUnloadHandler);

//set updaterequired = false
const xhttp = new XMLHttpRequest();
xhttp.open("POST", "/NewDBSystem/server/SessionAPI.php");
xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
xhttp.send("action=0");

let action = -1;
MainMenu();

function MainMenu() {
    document.getElementById("changenick").onclick = function () { action = 1; LogIn(); }
    document.getElementById("changename").onclick = function () { action = 2; LogIn(); }
    document.getElementById("changepin").onclick = function () { action = 3; LogIn(); }
    document.getElementById("logout").onclick = function () { action = -1; LogOut(); }
    document.getElementById("index").onclick = function () { window.location.replace("/NewDBSystem/Index.html"); }
}

function LogIn() {
    document.getElementById("menu").classList.add("hidden");
    document.getElementById("login").classList.remove("hidden");
    document.getElementById("account").disabled = false;
    document.getElementById("PIN").disabled = false;

    document.getElementById("inputs").addEventListener("keydown", function (event) {
        if (event.key == "Escape") {
            document.getElementById("login").classList.add("hidden");
            document.getElementById("account").disabled = true;
            document.getElementById("PIN").disabled = true;
            document.getElementById("menu").classList.remove("hidden");
            MainMenu();
            return;
        }
    })
    document.getElementById("account").addEventListener("keydown", function (event) {
        if (event.key == "Enter") { document.getElementById("PIN").focus(); }
    });
    document.getElementById("PIN").addEventListener("keydown", function (event) {
        if (event.key == "Enter") { Check(); }
    });

    function Check() {
        let account = document.getElementById("account").value;
        let PIN = document.getElementById("PIN").value;

        const xhttp = new XMLHttpRequest();
        xhttp.open("POST", "/NewDBSystem/server/AccountAPI.php");
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("account=" + account + "&password=" + PIN + "&action=0");

        xhttp.onload = function () {
            x = this.responseText;
            if (x == "0") {
                document.getElementById("login").classList.add("hidden");
                document.getElementById("account").disabled = true;
                document.getElementById("PIN").disabled = true;
                if (action == 1) { ChangeNick(account, PIN); }
                else if (action == 2) { ChangeAccountName(account, PIN); }
                else if (action == 3) { ChangeAccountPIN(account, PIN); }
            } else if (x == "1") {
                document.getElementById("incorrect").innerHTML = "V‰‰r‰ k‰ytt‰j‰nimi tai salasana";
            } else {
                console.log(x);
                alert("Ota j‰rjestelm‰nvalvojaan yhteytt‰");
                document.getElementById("incorrect").innerHTML = "Ennalta arvaamaton virhe, ota j‰rjestelm‰nvalvojaan yhteytt‰";
            }
        }

    }
}

function ChangeNick(acc, pin) {
    document.getElementById("nick").classList.remove("hidden");
    document.getElementById("name").disabled = false;

    document.getElementById("nninputs").addEventListener("keydown", function (event) {
        if (event.key == "Escape") {
            document.getElementById("nick").classList.add("hidden");
            document.getElementById("name").disabled = false;
            document.getElementById("menu").classList.remove("hidden");
            MainMenu();
            return;
        }
    })
    document.getElementById("name").addEventListener("keydown", function (event) {
        if (event.key == "Enter") { Check(); }
    });

    function Check() {
        let nickname = document.getElementById("name").value;

        if (nickname == "") {
            document.getElementById("nnincorrect").innerHTML = "T‰yt‰ kaikki kent‰t";
            return;
        }
        if (nickname.length > 25) {
            document.getElementById("nnincorrect").innerHTML = "Nimen on oltava enint‰‰n 25 merkki‰ pitk‰";
            return;
        }
        document.getElementById("nnincorrect").innerHTML = "";

        const xhttp = new XMLHttpRequest();
        xhttp.open("POST", "/NewDBSystem/server/AccountAPI.php");
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("account=" + acc + "&password=" + pin + "&action=1" + "&name=" + nickname);

        xhttp.onload = function () {
            x = this.responseText;
            console.log(x);
            if (x == "0") {
                document.getElementById("name").disabled = true;
                document.getElementById("nick").classList.add("hidden");
                window.location.replace("/NewDBSystem/Index.html");
            } else {
                alert("Ota j‰rjestelm‰nvalvojaan yhteytt‰");
                document.getElementById("nnincorrect").innerHTML = "Ennalta arvaamaton virhe, ota j‰rjestelm‰nvalvojaan yhteytt‰";
            }
        }
    }
}

function ChangeAccountName(acc, pin) {
    document.getElementById("accname").classList.remove("hidden");
    document.getElementById("newacc").disabled = false;

    document.getElementById("aninputs").addEventListener("keydown", function (event) {
        if (event.key == "Escape") {
            document.getElementById("accname").classList.add("hidden");
            document.getElementById("newacc").disabled = false;
            document.getElementById("menu").classList.remove("hidden");
            MainMenu();
            return;
        }
    })
    document.getElementById("newacc").addEventListener("keydown", function (event) {
        if (event.key == "Enter") { Check(); }
    });

    function Check() {
        let accname = document.getElementById("newacc").value;

        if (accname == "") {
            document.getElementById("anincorrect").innerHTML = "T‰yt‰ kaikki kent‰t";
            return;
        }
        if (accname.length > 25) {
            document.getElementById("anincorrect").innerHTML = "Nimen on oltava enint‰‰n 25 merkki‰ pitk‰";
            return;
        }
        document.getElementById("anincorrect").innerHTML = "";

        const xhttp = new XMLHttpRequest();
        xhttp.open("POST", "/NewDBSystem/server/AccountAPI.php");
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("account=" + acc + "&password=" + pin + "&action=2" + "&accname=" + accname);

        xhttp.onload = function () {
            x = this.responseText;
            console.log(x);
            if (x == "0") {
                document.getElementById("newacc").disabled = true;
                document.getElementById("accname").classList.add("hidden");
                window.location.replace("/NewDBSystem/Index.html");
            } else if (x == "01") {
                document.getElementById("anincorrect").innerHTML = "K‰ytt‰j‰nimi ei ole vapaa";
            } else {
                alert("Ota j‰rjestelm‰nvalvojaan yhteytt‰");
                document.getElementById("anincorrect").innerHTML = "Ennalta arvaamaton virhe, ota j‰rjestelm‰nvalvojaan yhteytt‰";
            }
        }
    }
}

function ChangeAccountPIN(acc, pin) {
    document.getElementById("notice").classList.remove("hidden");

    document.addEventListener("keydown", function ntcevent (event) {
        document.getElementById("notice").classList.add("hidden");
        document.getElementById("accpin").classList.remove("hidden");
        document.getElementById("newPIN").disabled = false;
        document.getElementById("newPIN1").disabled = false;
        document.removeEventListener("keydown", ntcevent);
    });

    document.getElementById("apinputs").addEventListener("keydown", function (event) {
        if (event.key == "Escape") {
            document.getElementById("accpin").classList.add("hidden");
            document.getElementById("newPIN").disabled = false;
            document.getElementById("newPIN1").disabled = false;
            document.getElementById("menu").classList.remove("hidden");
            MainMenu();
            return;
        }
    })
    document.getElementById("newPIN").addEventListener("keydown", function (event) {
        if (event.key == "Enter") { document.getElementById("newPIN1").focus(); }
    });
    document.getElementById("newPIN1").addEventListener("keydown", function (event) {
        if (event.key == "Enter") { Check(); }
    });


    function Check() {
        let accPIN = document.getElementById("newPIN").value;
        let accPIN1 = document.getElementById("newPIN1").value;

        if (accPIN == "" | accPIN1 == "") {
            document.getElementById("apincorrect").innerHTML = "T‰yt‰ kaikki kent‰t";
            return;
        }
        if (accPIN.length < 6) {
            document.getElementById("apincorrect").innerHTML = "Salasanan on oltava v‰hint‰‰n 6 merkki‰ pitk‰";
            return;
        }
        if (accPIN.length > 25) {
            document.getElementById("apincorrect").innerHTML = "Salasanan on oltava enint‰‰n 25 merkki‰ pitk‰";
            return;
        }
        if (accPIN != accPIN1) {
            document.getElementById("apincorrect").innerHTML = "Salasanat eiv‰t t‰sm‰‰";
            return;
        }
        document.getElementById("apincorrect").innerHTML = "";

        const xhttp = new XMLHttpRequest();
        xhttp.open("POST", "/NewDBSystem/server/AccountAPI.php");
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("account=" + acc + "&password=" + pin + "&action=3" + "&accpassword=" + accPIN);

        xhttp.onload = function () {
            x = this.responseText;
            console.log(x);
            if (x == "0") {
                document.getElementById("newPIN").disabled = true;
                document.getElementById("newPIN1").disabled = true;
                document.getElementById("accpin").classList.add("hidden");
                window.location.replace("/NewDBSystem/Index.html");
            } else {
                alert("Ota j‰rjestelm‰nvalvojaan yhteytt‰");
                document.getElementById("apincorrect").innerHTML = "Ennalta arvaamaton virhe, ota j‰rjestelm‰nvalvojaan yhteytt‰";
            }
        }
    }
}

function LogOut() {
    const xhttp = new XMLHttpRequest();
    xhttp.open("POST", "/NewDBSystem/server/AccountAPI.php");
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("action=4");

    xhttp.onload = function () {
        x = this.responseText;
        console.log(x);
        if (x == "0") {
            window.location.replace("/NewDBSystem/Index.html");
        } else {
            alert("Ota j‰rjestelm‰nvalvojaan yhteytt‰");
            document.getElementById("apincorrect").innerHTML = "Ennalta arvaamaton virhe, ota j‰rjestelm‰nvalvojaan yhteytt‰";
        }
    }
}