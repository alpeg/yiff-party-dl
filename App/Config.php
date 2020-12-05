<?php

namespace App;

use Symfony\Component\Yaml\Yaml;

/**
 * Description of Config
 *
 * @author https://github.com/alpeg
 * @license MIT
 */
class Config {

    private static Config $inst;

    public static function i(): Config {
        return (self::$inst) ?? (self::$inst = new self());
    }

    private $data;

    function __construct() {
        try {
            $defaultConfig = Yaml::parseFile(__DIR__ . '/../config.default.yaml');
        } catch (\Symfony\Component\Yaml\Exception\ParseException $ex) {
            fwrite(STDERR, "Do not touch \"config.default.yaml\" please.\n");
            fwrite(STDERR, "    " . wordwrap($ex->getMessage(), 70, "\n    ", true) . "\n");
            exit(1);
        }
        if (file_exists(__DIR__ . '/../config.yaml')) {
            try {
                $userConfig = Yaml::parseFile(__DIR__ . '/../config.yaml');
            } catch (\Symfony\Component\Yaml\Exception\ParseException $ex) {
                fwrite(STDERR, "Cannot parse config.yaml: " . $ex->getMessage() . "\n");
                exit(1);
            }
            $this->data = ($userConfig ?? []) + $defaultConfig;
        } else {
            $this->data = $defaultConfig;
        }
        unset($defaultConfig);
    }

    public function dump() {
        // print_r($this->data);
        echo json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function get($key) {
        if (!array_key_exists($key, $this->data))
            return null;
        return $this->data[$key];
    }

}
