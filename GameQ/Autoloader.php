<?php
/*
 * Simple auto loader to allow GameQ to function the same as if it were loaded via Composer
 *
 * To use this just include this file in your script and the GameQ namespace will be made available
 *
 * i.e. require_once('/path/to/GameQ/Autoloader.php');
 */
set_include_path(get_include_path() . PATH_SEPARATOR .  realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

// We include all .php files
spl_autoload_extensions(".php");

// Register the autoloader
spl_autoload_register();
