<?php

namespace App;

error_reporting(E_ALL | E_STRICT);

require_once __DIR__ . '/vendor/autoload.php';

set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, $severity, $severity, $file, $line);
});
?><!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <pre><?php
            // echo (new Loader())->loadSplash(13582096); // https://yiff.party/patreon/13582096
            // echo (new Loader())->loadSplash(23244396); // https://yiff.party/patreon/23244396
            // echo (new Loader())->loadSplash(7330723); // https://yiff.party/patreon/7330723
            // echo (new Loader())->loadSplash(20259648); // https://yiff.party/patreon/20259648
            // echo htmlspecialchars(json_encode((new Loader())->parseSplash(file_get_contents('test_storage/_body13582096_.html')), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            // echo htmlspecialchars(json_encode((new Loader())->parseSplash(file_get_contents('test_storage/_body23244396_.html')), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            // echo htmlspecialchars(json_encode((new Loader())->parseSplash(file_get_contents('test_storage/_body7330723_.html')), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            // echo htmlspecialchars(json_encode((new Loader())->parseSplash(file_get_contents('test_storage/_body20259648_.html')), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            // (new Loader())->storeExclusions();
            // // print_r( (new Loader())->loadExclusions() );
            // echo htmlspecialchars(json_encode((new Loader())->parseSplash(file_get_contents('test_storage/_body13756532_page6.html')), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            // print_r((new Loader())->reparseExclusions());
            echo htmlspecialchars(json_encode((new Loader())->parseSplash(file_get_contents('test_storage/_body20259648_.html')), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            ?></pre>
    </body>
</html>
