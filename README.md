# GameQ Version 3
[![Build Status](https://travis-ci.org/Austinb/GameQ.svg?branch=v3&style=flat-square)](https://travis-ci.org/Austinb/GameQ)
[![Code Coverage](https://scrutinizer-ci.com/g/Austinb/GameQ/badges/coverage.png?b=v3)](https://scrutinizer-ci.com/g/Austinb/GameQ/?branch=v3)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Austinb/GameQ/badges/quality-score.png?b=v3&style=flat-square)](https://scrutinizer-ci.com/g/Austinb/GameQ/?branch=v3)
[![License](https://img.shields.io/badge/license-LGPL-blue.svg?style=flat)](https://packagist.org/packages/austinb/gameq)
[![Dependency Status](https://www.versioneye.com/php/austinb:gameq/badge?style=flat)](https://www.versioneye.com/php/austinb:gameq)
[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VAU2KADATP5PU)

[![Testing status](http://php-eye.com/badge/austinb/gameq/tested.svg?branch=v3.x-dev&style=flat)](http://php-eye.com/package/austinb/gameq)

GameQ is a PHP library that allows you to query multiple types of multiplayer game & voice servers at the same time.

## Requirements
* PHP 5.4.14+ - [Tested](https://travis-ci.org/Austinb/GameQ) in PHP 5.4, 5.5, 5.6, 7.0, 7.1 & [HHVM](http://hhvm.com/)
* [Bzip2](http://www.php.net/manual/en/book.bzip2.php) - Used for A2S Compressed responses

## Installation
#### [Composer](https://getcomposer.org/)
This method assumes you already have composer [installed](https://getcomposer.org/doc/00-intro.md) and working properly. Add `austinb/gameq` as a requirement to composer.json by using `composer require austinb/gameq:~3.0` or by manually adding the following to the *composer.json* file in the **require** section:

```javascript
"austinb/gameq": "~3.0"
```

Update your packages with `composer update` or install with `composer install`.

#### Standalone Library
Download the [latest version](https://github.com/Austinb/GameQ/releases) of the library and unpack it into your project.  Add the following to your bootstrap file:
```php
require_once('/path/to/src/GameQ/Autoloader.php');
```
The Autoloader.php file provides the same auto loading functionality as the Composer install.

## Example
```php
$GameQ = new \GameQ\GameQ();
$GameQ->addServer([
    'type' => 'css',
    'host' => '127.0.0.1:27015',
]);
$results = $GameQ->process();
```
Need more?  See [Examples](https://github.com/Austinb/GameQ/wiki/Examples-v3).

## Contributing 
 
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License
See [LICENSE](LICENSE.lgpl) for more information

Donations
=========
[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VAU2KADATP5PU)
