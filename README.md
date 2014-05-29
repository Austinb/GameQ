Notice - May 28, 2014
======
Version 2 is now closed to new features.  I will add new games as time allows.  

Please test v3 as much as possible and provide any feedback.  Note that v3 is not production ready.  

Information
===========
GameQ is a PHP program that allows you to query multiple types of multiplayer game servers at the same time.

GameQ v2 is based off of the original GameQ PHP program from http://gameq.sourceforge.net/.  That project was no longer being supported.

Requirements
============
*  PHP 5.2 (Recommended 5.3, 5.4)

Extras you might need:
* Bzip2 - Used for A2S compressed responses (http://www.php.net/manual/en/book.bzip2.php)
* Zlib - Used for AA3 (before version 3.2) compressed responses (http://www.php.net/manual/en/book.zlib.php)
	
Example
=======
Usage & Examples: https://github.com/Austinb/GameQ/wiki/Usage-&-examples-v2

Quick and Dirty:

    $gq = new GameQ();
    $gq->addServer(array(
    	'id' => 'my_server',
    	'type' => 'css', // Counter-Strike: Source
    	'host' => '127.0.0.1:27015',
    ));
    
    $results = $gq->requestData(); // Returns an array of results
    
    print_r($results);

Want more? Check out the wiki page or /examples for more.

ChangeLog
=========
See https://github.com/Austinb/GameQ/commits/v2 for an incremental list of changes

License
=======
See LICENSE for more information

Donations
=========
If you like this project and use it a lot please feel free to donate here: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VAU2KADATP5PU.
