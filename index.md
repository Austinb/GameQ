### Requirements
* PHP 5.4.14+ - [Tested](https://travis-ci.org/Austinb/GameQ) in PHP 5.4, 5.5, 5.6 & [HHVM](http://hhvm.com/)
* [Bzip2](http://www.php.net/manual/en/book.bzip2.php) - Used for A2S Compressed responses

### Installation

#### [Composer](https://getcomposer.org/)
This method assumes you already have composer [installed](https://getcomposer.org/doc/00-intro.md) and working properly. Add `austinb/gameq` as a requirement to composer.json by using `composer require austinb/gameq:3.*@stable` or by manually adding the following to the *composer.json* file in the **require** section:

```javascript
"austinb/gameq": "3.*@stable"
```

Update your packages with `composer update` or install with `composer install`.

#### Standalone Library
Download the [latest version](https://github.com/Austinb/GameQ/releases) of the library and unpack it into your project.  Add the following to your bootstrap file:
```php
require_once('/path/to/src/GameQ/Autoloader.php');
```
The Autoloader.php file provides the same auto loading functionality as the Composer install. 

### Example
```php
$GameQ = new \GameQ\GameQ();
$GameQ->addServer([
    'type' => 'css',
    'host' => '127.0.0.1:27015',
]);
$results = $GameQ->process();
```
Need more?  See visit the **[GameQ Wiki](https://github.com/Austinb/GameQ/wiki/Version-3)**.