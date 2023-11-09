let sort = "games";
let order = "DESC";
let limit = 10;
let currentpage = 0;
let numberofpages;
let stats = 0;
Main();

function Main() {
    GetAverages();
    GetResults();
    GetTop3();

    document.getElementById("logo").onclick = function () { window.location.assign("Index.html"); }

    document.getElementById("statstoggle").onclick = function () {
        const anim = (stats == 0) ? { duration: 500, fill: "forwards" } : { duration: 500, direction: "reverse", fill: "forwards" };
        document.getElementById("stats").animate([{ height: "0" }, { height: "1.5rem" }], anim);
        stats = (stats == 0) ? 1 : 0;
    }

    document.getElementById("rows").onchange = function () { limit = document.getElementById("rows").value; GetResults(); GetAverages(); }
    document.getElementById("first").onclick = function () {
        if (currentpage > 0) {
            currentpage = 0;
            GetResults();
            TableFooter();
        }
    }
    document.getElementById("previous").onclick = function () {
        if (currentpage > 0) {
            currentpage--;
            GetResults();
            TableFooter();
        }
    }
    document.getElementById("next").onclick = function () {
        if (currentpage < numberofpages) {
            currentpage++;
            GetResults();
            TableFooter();
        }
    }
    document.getElementById("last").onclick = function () {
        if (currentpage < numberofpages) {
            currentpage = numberofpages;
            GetResults();
            TableFooter();
        }
    }

    document.getElementById("name").onclick = function () { SortBy("name") }
    document.getElementById("games").onclick = function () { SortBy("games") }
    document.getElementById("wins").onclick = function () { SortBy("wins") }
    document.getElementById("losses").onclick = function () { SortBy("losses") }
    document.getElementById("winrate").onclick = function () { SortBy("winrate") }
    document.getElementById("lossrate").onclick = function () { SortBy("lossrate") }

    function SortBy(str) {
        currentpage = 0;
        if (sort != str) {
            document.getElementById("order" + sort).innerHTML = "";
            sort = str;
            order = (str == "name") ? "ASC" : "DESC";
        } else {
            order = (order == "DESC") ? "ASC" : "DESC";
        }
        let arrow = (order == "DESC") ? "&#9650;" : "&#9660;";
        document.getElementById("order" + str).innerHTML = arrow;
        GetResults();
        TableFooter();
    }
}

function GetResults() {
    let offset = currentpage * limit;
    let xhttp = new XMLHttpRequest();
    xhttp.open("GET", "server/LeaderboardAPI.php?Mode=0&Sort=" + sort + "&Order=" + order + "&Limit=" + limit + "&Offset=" + offset);
    xhttp.send();
    xhttp.onload = function () {
        let x = this.responseText;
        console.log(x);
        let data = JSON.parse(x);
        let str = "";
        for (let i = 0; i < data.Users.length; i++) {
            let id = (currentpage * limit) + 1 + i;
            str += '<tr id="lb">';
            str += "<td> " + id + "</td>";
            str += "<td> " + data.Users[i].name + "</td>";
            str += "<td> " + data.Users[i].games + "</td>";
            str += "<td> " + data.Users[i].wins + "</td>";
            str += "<td> " + data.Users[i].losses + "</td>";
            str += "<td> " + Math.round(100 * data.Users[i].winrate) + "</td>";
            str += "<td> " + Math.round(100 * data.Users[i].lossrate) + "</td>";
            str += "</tr>";
        }
        document.getElementById("lb").innerHTML = str;

    }
}

function GetAverages() {
    let xhttp = new XMLHttpRequest();
    xhttp.open("GET", "server/LeaderboardAPI.php?Mode=1");
    xhttp.send();
    xhttp.onload = function () {
        let x = this.responseText;
        console.log(x);
        let data = JSON.parse(x);
        let str = "<p>Pelejä yhteensä: " + Math.round(100 * data.count) / 100 + "&#8193; | &#8193;  Keskiverto&#8193; Pelit: " + Math.round(100 * data.games) / 100 + "&#8193; Voitot: " + Math.round(100 * data.wins) / 100 + "&#8193; Häviöt: " + Math.round(100 * data.losses) / 100 + "&#8193; Voitto%: " + Math.round(100 * data.winrate) + "&#8193; Häviö%: " + Math.round(100 * data.lossrate) + "</p>";
        document.getElementById("stats").innerHTML = str;
        numberofpages = Math.ceil(data.count / limit) - 1;
        TableFooter();
    }
}

function TableFooter() {
    let str = (currentpage + 1) + "/" + (numberofpages + 1);
    document.getElementById("pagenum").innerHTML = str;
}

function GetTop3() {
    let xhttp = new XMLHttpRequest();
    xhttp.open("GET", "server/LeaderboardAPI.php?Mode=2");
    xhttp.send();
    xhttp.onload = function () {
        let x = this.responseText;
        console.log(x);
        let data = JSON.parse(x);
        let str = "<div class=topcontent><div></div><h3>Eniten voittoja</h3><div></div></div>";
        for (let i = 0; i < data.topwins.length; i++) {
            str += "<div class=topcontent><p id=topinfo>&#" + (8544 + i) + "</p><p id=topname>" + data.topwins[i].name + "</p><p id=topinfo>" + data.topwins[i].wins + "</p></div>";
        }
        document.getElementById("topwins").innerHTML = str;

        str = "<div class=topcontent><div></div><h3>Paras voitto%</h3><div></div></div>";
        for (let i = 0; i < data.topwinrate.length; i++) {
            str += "<div class=topcontent><p id=topinfo>&#" + (8544 + i) + "</p><p id=topname>" + data.topwinrate[i].name + "</p><p id=topinfo>" + Math.round(100 * data.topwinrate[i].winrate) + "%</p></div>";
        }
        document.getElementById("topwinrate").innerHTML = str;

        str = "<div class=topcontent><div></div><h3>Huonoin häviö%</h3><div></div></div>";
        for (let i = 0; i < data.toplossrate.length; i++) {
            str += "<div class=topcontent><p id=topinfo>&#" + (8544 + i) + "</p><p id=topname>" + data.toplossrate[i].name + "</p><p id=topinfo>" + Math.round(100 * data.toplossrate[i].lossrate) + "%</p></div>";
        }
        document.getElementById("toplossrate").innerHTML = str;
    }
}