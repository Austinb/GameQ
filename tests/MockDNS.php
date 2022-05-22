<?php

namespace GameQ\Tests;

/**
 * MockDNS class using monkey patching. Inspired by symfony/phpunit-bridge
 *
 * @see https://github.com/symfony/phpunit-bridge/blob/5.3/DnsMock.php
 */
class MockDNS
{
    private static $hosts = [];

    public static function mockHosts(array $hosts)
    {
        self::$hosts = $hosts;
    }

    public static function gethostbyname($hostname)
    {
        // Redirect to original function if no overwrites has been defined
        if (! self::$hosts) {
            return \gethostbyname($hostname);
        }

        // Check if a overwrite has been defined for this host
        if (isset(self::$hosts[$hostname])) {
            foreach (self::$hosts[$hostname] as $ip) {
                return $ip;
            }
        }

        // Default behaivour, return the same on error
        return $hostname;
    }

    public static function register($class)
    {
        // Store own namespace
        $self = static::class;

        // Inject overwrite that call static on self (if they do not already exist)
        foreach ([substr($class, 0, strrpos($class, '\\'))] as $ns) {
            // Check if the function is not already defined, skip if so
            if (\function_exists($ns.'\gethostbyname')) {
                continue;
            }


            $code = <<<EOPHP
namespace $ns;
function gethostbyname(\$hostname)
{
    return \\$self::gethostbyname(\$hostname);
}
EOPHP;

            // Eval the script below, will define the function in the namespace effectively overwriting it
            // https://www.php.net/manual/de/language.namespaces.fallback.php#116275
            eval($code); // phpcs:ignore
        }
    }
}
