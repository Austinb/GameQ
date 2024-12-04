# GameQ Version 3
[![CI](https://github.com/Austinb/GameQ/actions/workflows/Tests.yml/badge.svg)](https://github.com/Austinb/GameQ/actions/workflows/Tests.yml)
[![Code Coverage](https://scrutinizer-ci.com/g/Austinb/GameQ/badges/coverage.png?b=v3)](https://scrutinizer-ci.com/g/Austinb/GameQ/?branch=v3)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Austinb/GameQ/badges/quality-score.png?b=v3&style=flat-square)](https://scrutinizer-ci.com/g/Austinb/GameQ/?branch=v3)
[![License](https://img.shields.io/badge/license-LGPL-blue.svg?style=flat)](https://packagist.org/packages/austinb/gameq)
[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VAU2KADATP5PU)

GameQ is a PHP library that allows you to query multiple types of multiplayer game & voice servers at the same time.

## Requirements
* PHP 5.6.40+ - [Tested](https://github.com/Austinb/GameQ/actions/workflows/Tests.yml) in PHP 5.6, 7.0, 7.1, 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3 & 8.4
* [Bzip2](http://www.php.net/manual/en/book.bzip2.php) - Used for A2S Compressed responses

## Installation
#### [Composer](https://getcomposer.org/)
This method assumes you already have composer [installed](https://getcomposer.org/doc/00-intro.md) and working properly. Add `austinb/gameq` as a requirement to composer.json by using `composer require austinb/gameq:~3.1` or by manually adding the following to the *composer.json* file in the **require** section:

```javascript
"austinb/gameq": "~3.1"
```

Update your packages with `composer update` or install with `composer install`.

#### Standalone Library
Download the [latest version](https://github.com/Austinb/GameQ/releases) of the library and unpack it into your project.  Add the following to your bootstrap file:
```php
require_once('/path/to/src/GameQ/Autoloader.php');
```
The Autoloader.php file provides the same auto loading functionality as the Composer install.

## Useage
```php
$GameQ = new \GameQ\GameQ();
$GameQ->addServer([
    'type' => 'css',
    'host' => '127.0.0.1:27015',
]);
$results = $GameQ->process();
```
Need more? See the [Examples](https://github.com/Austinb/GameQ/wiki/Examples-v3) as well as the [Documentation](https://austinb.github.io/GameQ/api/).

## Contributing 
 
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License
See [LICENSE](LICENSE.lgpl) for more information

## Third Party Provider

* [dev.tkirch.wsc.gameq](https://github.com/tkirchDev/dev.tkirch.wsc.gameq) - Provides the "Austinb GameQ" library at the WSC.

Donations
=========
[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VAU2KADATP5PU)
