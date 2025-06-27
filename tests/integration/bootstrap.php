<?php
/**
 * Copyright 2025 Alexey Kopytko <alexey@kopytko.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// Custom bootstrap script for integration testing
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

// The Composer autoloader is already loaded
// Bootstrap scripts don't need to return anything (like PHPUnit)
