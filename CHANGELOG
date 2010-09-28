Readme
======
example.php contains a basic example.
list.php shows a list of supported games.

For further information, visit http://gameq.sourceforge.net/.

Feedback is very much appreciated.


Installation
============
Just extract the library to where you want to use it.
Some servers using the source protocol (counterstrike, left4dead, team fortress 2 etc)
may have compression enabled. For this bzip2 must be installed 
(see http://php.net/bzip2.setup for details). The script will work if this is not
enabled, but not all data from those servers will be available.


Changelog
=========

Game 1.12 - February 10, 2010
- Protocols updated:
  + [quake3] Fixed bug with header check
  + [ventrilo] Removed debug statement
  + [ts3] Some fix

Gameq 1.11a - December 30, 2009
- Protocols updated:
  + [ts2] Works again now
  + [ts3] Queryclients now gets filtered

Gameq 1.11 - December 22, 2009
- Fixed small bug in Normalise filter
- Fixed Stripcolor filter to work again
- Games added:
  + [ts3] Teamspeak 3
- Protocols updated:
  + [ts2] Now throws exceptions
  

Gameq 1.10 - November 4, 2009
- Games added:
  + [left4dead2] Left 4 Dead 2
- Protocols updated:
  + [samp] fixed simple todo
  + [source] changed challenge retrieving method (i hope this works for all source games)

Gameq 1.10 - October 24, 2009
- Games added:
  + [openttd] OpenTDD (openttd.org), look at the file udp. from openttd source code for more information
- Protocols updated:
  + [aa3] License + Copyright
  + [ventrilo] License + Copyright

Gameq 1.09 - October 20, 2009
- INI files fixed
- Fixed bug with bindto on linux systems
- Games added:
  + [ventrilo] Ventrilo

Gameq 1.08 Beta - October 6, 2009
- Games added:
  + [aa3] Americas Army 3 (based on http://www.greycube.com/help/lgsl_other/americas_army_3_query.txt)

Gameq 1.07a - August 13, 2009
- Compatibility to PHP 5.3

Gameq 1.07 -
----------
- Games added:
  + [killingfloor] Killingfloor
- Protocols updated:
  + [samp] Removed debug statement, should work now
  + [source] Made a distinction between source and goldsource servers,
    see http://developer.valvesoftware.com/wiki/Talk:Server_Queries#A2S_SERVERQUERY_GETCHALLENGE_not_working_since_last_HLDS_update,
    updates for left4dead
  + [ts2] Fixed clients, channels not being cleared after first query.
    Thanks Bram!
  + [ut2] Fixed player parsing

Gameq 1.06 - March 7, 2009
----------
- Protocols updated:
  + [gamespy3] Fixed packet joining, which caused strange player data


Gameq 1.05 - March 5, 2009
----------
- Protocols updated:
  + [source] Fixed rule parsing, added support for compressed results

Gameq 1.04 - March 4, 2009
----------
- Added tcp support, thanks to Marco Pannekens
- Fixed issue with sockets closing after challenge,
  causing problems with crysis
- Games added:
  + [cod5] Call of Duty 5: World at War
  + [crysiswars] Crysis Wars
  + [left4dead] Left 4 Dead
- Protocols added:
  + [ts2] Teamspeak 2 protocol, thanks to Marco Pannekens
  + [samp] San Andreas: Multiplayer
- Protocols updated:
  + [gamespy3] Fixed problems with cut off packets
  + [quake3] Updated for compatibility with nexuiz ctf

Gameq 1.03 - November 1, 2008
----------
- Added [hlold] protocol, points to the old halflife protocol
- [source] now returns players data for halflife 1 servers


Gameq 1.02 - October 25, 2008
----------
- Added compatibility for changed halflife 1, you can use [source] to
  query them

Gameq 1.01 - Semptember 15, 2008
----------
- Games added:
  + [assaultcube] Assault Cube
  + [ffow] Frontline: Fuel of War
  + [savage2] Savage 2: A Tortured Soul
  + [ns] Natural Selection
  + [teeworlds] Teeworlds, suggested by Dirk (http://cstat.y7.ath.cx).
- Games updated:
  + [crysis] Uses the gamespy3 protocol since last update
  + [sauerbraten] Thanks to patch by Dirk (http://cstat.y7.ath.cx).
- Protocols updated:
  + [farcry] Added player listing
  + [source] Updated for new packet headers
- Options:
  + Renamed: "sockets" has been renamed to "sock_count"
  + Added: "sock_start", the first port to be opened locally
    If you want the script to use ports 10000 - 10020, you'll have to use
    $gq->setOption('sock_start', 100000);
    $gq->setOption('sock_count', 20);

- Simplified error handling, now uses trigger_error()
- Filters can now have default arguments
- Fixed normalise filter for players
- Added sortplayers filter
- Added list.php
- Updated example.php, should be much clearer now
- Added a todo section to this document


GameQ 1.0 - April 01, 2008
---------
- Games added:
  + [baldur] Baldur's Gate 1
- Protocols updated:
  + [doom3] Split off quakewars protocol
  + [quakewars] Updated protocol for splatterladder / 1.4
- Games updated:
  + [bf2] Updated query string
  + [cs] Changed to source protocol. Use [halflife] for old
    games
  + [source] Updated query string
  + [ut3] Added default port


Alpha 2.2 - November 19, 2007
---------
- Games added:
  + [cod3] Call of Duty 3, thanks to Allstats
  + [cod4] Call of Duty 4, thanks to Allstats
  + [coduo] Call of Duty: United offensive
  + [crysis] Crysis
  + [mohbreak] Medal of Honor: Breakthrough
  + [mohspear] Medal of Honor: Spearhead
  + [rfactor] rFactor
  + [tf2] Team Fortress 2
  + [ut3] Unreal Tournament 3, unknown default port
- Games updated:
  + [aa] America's army now uses gamespy2 protocol
  + [quakewars] Updated for version 1.2
- Protocols updated:
  + [source] Better handling for erroneous responses
  + [gamespy] Improved
  + [gamespy2] Fixed bug for empty player list

- Added GameQ_Buffer::goto
- Modified GameQ_Config::getGame to return game type
- Added some default return values (gq_<name>)
- Partially rewrote the normalise filter
- Added some script examples


Alpha 2.1 - August 18, 2007
-------
- Added a normalising filter
- Added ghost recon: advanced warfighter 2 to list [graw2]
- Added ghost recon: advanced warfighter to list [graw]
- Added vietcong 2 to list [vietcong2]
- Added mta: san andreas to list [mtasa]
- Added hexenworld to list [hexenworld]
- Added generic entry for source [source]
- Added halo 2 entry, untested [halo2]
- Changed fear to use gamespy2 protocol [fear]
- Added a limit on sockets to be used by the script, preventing errors when
  querying large amounts of servers.
  The limit can be set using $gameq->setOption('sockets', <number>);
- Added a GameQ::clearServers() method
- Fixed doom3/quakewars players


Alpha 2 - July 29, 2007
-------
- Added battlefield 2142 support [bf2142]
- Added stalker support [stalker]
- Added alien arena to list [alienarena]
- Added armed assault to list [armedassault]
- Added red orchestra to list [redorchestra]
- Added cross racing championship to list [crossracing]
- Added kiss psycho circus to list [kiss]
- Updated kingpin data [kingpin]
- Fixed player bug for doom3 protocol
- Fixed player bug for gamespy protocol
- Added a filter to strip color tags
- Modified main GameQ and Communicate objects to send challenge-
  response packets over same socket. This caused problems with the new 
  gamespy protocol
- Added some sanity checks to main class


Alpha 1.2 - July 17, 2007
-------
- Added hexen 2 support [hexen2]
- Added silverback engine support [silverback]
- Added partial tribes support [tribes]
- Added partial tribes 2 support [tribes2]
- Added dark messiah to list [messiah]
- Added tremulous to list [tremulous]
- Added savage to list [savage]
- Added ragdoll kung fu to list [ragdoll]
- Added neverwinter nights 2 to list [neverwinter2]
- Added Red Orchestra to list [redorchestra]
- Added this file


Alpha 1.1 - July 05, 2007
-------
- Added Cube engine support [cube]
- Added Sauerbraten / Cube2 engine support [sauerbraten]
- Added limited Ghost Recon support [ghostrecon]
- Added Warsow support [warsow]
- Added Counter-Strike list [cs]
- Added Dod: Source to list [dodsource]
- Modified quake3 protocol, now manually counts players
- Changed filters to accept arguments


Alpha 1 - June 29, 2007
-------
- Initial commit


TODO
====
- [source] Add support for compressed responses
- Create more extensive documentation
- Add more games :)
