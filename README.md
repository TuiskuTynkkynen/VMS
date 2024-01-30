``██╗   ██╗███╗   ███╗███████╗``  
``██║   ██║████╗ ████║██╔════╝``  
``██║   ██║██╔████╔██║███████╗``  
``╚██╗ ██╔╝██║╚██╔╝██║╚════██║``  
`` ╚████╔╝ ██║ ╚═╝ ██║███████║``  
``  ╚═══╝  ╚═╝     ╚═╝╚══════╝``  
## VIRTUAL MOSKA SIMULATOR V1.0.0
VMS is a web version of the Eastern Finnish cardgame "Moska".  
Server-side code is written in vanilla PHP and uses SQL for database opetations.
Connection to client is handled via WebSocket.  
Front-end code is written in vanilla JavaScript and HTML/CSS.
  

### Features  
- Simulated version of entire "Turamoska" ruleset  
- Account and leaderboard system for tracking statistics  
- Lobby system for handling several simultaneous games  
- Stylized GUI and tutorial for gameplay and easy account and lobby handling  
  
  
### How To Use
Host the server with web server software cabable of hosting PHP 8.2 servers,
like Apache or Nginx, and a relational database management system, like MySQL.  
Follow the [database instructions](server/config/DBInstructions.txt)
to create the required ´vms´ database
and follow the [config instructions](server/config/ConfigInstructions.txt) 
to create the required "serverconfig.ini" configuration file for server specific variables.  
After you have set up the server open up the [VMS WebSocket](server/VMSWebSocket.php).
You should now be able to connect to the application with a client and play the game.  
Note: Client-side information is presented in Finnish.