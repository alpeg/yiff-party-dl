<?php

namespace App;

/**
 * Description of Storager
 *
 * @author https://github.com/alpeg
 * @license MIT
 */
class Storager {

    public $folder = __DIR__ . '/../default_storage';

    public function ensureDir($fn) {
        $d = preg_replace('#[\\\\/][^\\\\/]*\\z#m', '', $fn);
        if (!is_dir($d))
            \mkdir($d, 0777, true);
        return $fn;
    }

    public function read($f) {
        $fn = $this->folder . '/' . $f . '.json';
        $this->ensureDir($fn);
        if (!file_exists($fn))
            return null;
        return \json_decode(\file_get_contents($fn), true);
    }

    public function exists($f) {
        $fn = $this->folder . '/' . $f . '.json';
        return file_exists($fn);
    }

    public function existsText($f) {
        $fn = $this->folder . '/' . $f . '.txt';
        return file_exists($fn);
    }

    public function store($f, $o) {
        $fn = $this->folder . '/' . $f . '.json';
        $this->ensureDir($fn);
        // file_put_contents($fn, json_encode($o, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($fn, json_encode($o, JSON_UNESCAPED_UNICODE));
    }

    public function readText($f) {
        $fn = $this->folder . '/' . $f . '.txt';
        $this->ensureDir($fn);
        if (!file_exists($fn))
            return null;
        return \file_get_contents($fn);
    }

    public function storeText($f, $o) {
        $fn = $this->folder . '/' . $f . '.txt';
        $this->ensureDir($fn);
        file_put_contents($fn, $o);
    }

    function __construct() {
        if (!is_dir($this->folder)) {
            mkdir($this->folder);
        }
    }

}
