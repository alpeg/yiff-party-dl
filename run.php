#!/usr/bin/env php
<?php

namespace App;

error_reporting(E_ALL | E_STRICT);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, $severity, $severity, $file, $line);
});
require_once __DIR__ . '/vendor/autoload.php';
ConsoleRunner::run();
