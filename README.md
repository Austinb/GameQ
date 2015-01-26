Very ALPHA - NOT FOR PRODUCTION

Information
===========
GameQ is a PHP program that allows you to query multiple types of multiplayer game & voice servers at the same time.

GameQ v3 is based off GameQ v2 but updated to use new features of PHP 5.4+ as well as address speed and other issues in v2.

Requirements
============
* PHP 5.4.14+
* Bzip2 - Used for A2S compressed responses (http://www.php.net/manual/en/book.bzip2.php)

Installation
=======
Add `austinb/gameq` as a requirement to composer.json by using `composer require austinb/gameq:3.*@dev` or by 
manually adding the following to the composer.json file:

```javascript
{
    "require": {
        "austinb/gameq": "3.*@dev"
    }
}
```

Update your packages with `composer update` or install with `composer install`.

Or if you are not using composer download the latest version, unpack into your project and add the following to your 
autoloader.php to make the GameQ namespace available:

```php
require_once('/path/to/src/GameQ/Autoloader.php');
```

Usage
=======
Coming soon

ChangeLog
=========
See https://github.com/Austinb/GameQ/commits/v3 for an incremental list of changes

License
=======
See LICENSE.lgpl for more information

Donations
=========
If you like this project and use it a lot please feel free to donate here: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VAU2KADATP5PU.
