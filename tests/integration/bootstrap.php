<?php

/**
 * Custom bootstrap script for integration testing
 */

declare(strict_types=1);

// Custom error handling for the test environment
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Set strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set('UTC');

// Define a constant that proves the bootstrap was loaded
define('BOOTSTRAP_LOADED', true);
define('BOOTSTRAP_TIME', date('Y-m-d H:i:s'));

// Log that bootstrap was executed
file_put_contents(__DIR__ . '/bootstrap.log', "Bootstrap executed at " . BOOTSTRAP_TIME . "\n");

// Load and return the Composer autoloader
$loader = require __DIR__ . '/../../vendor/autoload.php';

// Add our test app namespace
$loader->addPsr4('TestApp\\', __DIR__ . '/src/');

return $loader;