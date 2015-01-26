<?php
/*
 * Simple auto loader to allow GameQ to function the same as if it were loaded via Composer
 *
 * To use this just include this file in your script and the GameQ namespace will be made available
 *
 * i.e. require_once('/path/to/src/GameQ/Autoloader.php');
 */

/**
 * A PSR-4 autoloader for non-composer installs.
 * See: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 */
spl_autoload_register(function ($class) {

    // project-specific namespace prefix
    $prefix = 'GameQ\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . DIRECTORY_SEPARATOR;

    // does the class use the namespace prefix?
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
