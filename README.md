# GameQ

> **This version of GameQ is no longer actively maintained.**  
> For ongoing development, current PHP support, protocol updates, fixes, and releases, please use the maintained successor: [**SoftCreatRMedia/GameQ**](https://github.com/SoftCreatRMedia/GameQ).

GameQ is a PHP library for querying multiple types of multiplayer game and voice servers.

The project originated on SourceForge and was later continued in this repository as GameQ Version 3. The latest stable release of this package is `v3.1.0`, and no further stable releases are currently being published from this repository.

## Maintained successor

[SoftCreatRMedia/GameQ](https://github.com/SoftCreatRMedia/GameQ) is the actively maintained continuation of the GameQ codebase.

It preserves the `GameQ\` namespace and the overall purpose and architecture of the original library while providing:

- support for PHP 8.1 and newer;
- ongoing game and server-query protocol development;
- fixes for current games and server implementations;
- security and protocol-hardening improvements;
- modern PHPUnit, PHPStan, PHP_CodeSniffer, and compatibility checks;
- maintained releases and documentation.

Resources:

- Repository: [https://github.com/SoftCreatRMedia/GameQ](https://github.com/SoftCreatRMedia/GameQ)
- Packagist: [https://packagist.org/packages/softcreatr/gameq](https://packagist.org/packages/softcreatr/gameq)
- Documentation: [https://github.com/SoftCreatRMedia/GameQ/wiki](https://github.com/SoftCreatRMedia/GameQ/wiki)
- Supported servers and protocols: [https://github.com/SoftCreatRMedia/GameQ/wiki/Supported-Servers](https://github.com/SoftCreatRMedia/GameQ/wiki/Supported-Servers)
- Migration from 4.x to 5.x: [https://github.com/SoftCreatRMedia/GameQ/wiki/Upgrading-from-4.x](https://github.com/SoftCreatRMedia/GameQ/wiki/Upgrading-from-4.x)

Install the maintained version with Composer:

```bash
composer require softcreatr/gameq:^5.2
```

### Migration note

`softcreatr/gameq` continues the original GameQ codebase, but current 5.x releases include deliberate API modernization and require PHP 8.1 or newer.

Projects currently using `austinb/gameq` should therefore explicitly replace their Composer dependency and review the maintained project's documentation when migrating rather than assuming that every 3.x integration is automatically interchangeable with 5.x.

## Historical documentation

Documentation for this original 3.x version remains available for existing installations:

- [Examples](https://github.com/Austinb/GameQ/wiki/Examples-v3)
- [API documentation](https://austinb.github.io/GameQ/api/)

Existing applications that must remain on GameQ 3.x can continue using the existing release, but new development and future maintenance should target the maintained successor.

## Attribution and license

The maintained successor preserves attribution to the original GameQ authors and continues to use the GNU Lesser General Public License, version 3 or later.

- Original project: [https://github.com/Austinb/GameQ](https://github.com/Austinb/GameQ)
- Maintained successor: [https://github.com/SoftCreatRMedia/GameQ](https://github.com/SoftCreatRMedia/GameQ)
- License in this repository: [LICENSE.lgpl](LICENSE.lgpl)
- License in the maintained successor: [LICENSE.lgpl](https://github.com/SoftCreatRMedia/GameQ/blob/main/LICENSE.lgpl)
