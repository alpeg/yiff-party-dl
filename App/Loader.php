<?php

use App\HttpClient;
use App\Storager;
use App\WebsiteParser;

namespace App;

/**
 * Description of Loader
 *
 * @author https://github.com/alpeg
 * @license MIT
 */
class Loader {

    public HttpClient $http;

    /*
     * try {
     * 	   // ...
     * } catch (\GuzzleHttp\Exception\ServerException $e) {
     *     echo "ServerException: " . $e->getMessage();
     * } catch (\GuzzleHttp\Exception\ClientException $e) {
     *     echo "ClientException: " . $e->getMessage();
     * } catch (\GuzzleHttp\Exception\ConnectException $e) {
     *     echo "ConnectException: " . $e->getMessage();
     * }
     */
    public Storager $storager;

    function __construct($storageFolder = null) {
        $this->http = new HttpClient('https://yiff.party/');
        $this->storager = new Storager();
    }

    /**
     * 
     * @deprecated
     */
    public function downloadAndStoreExclusions() {
        $e = $this->downloadExclusionsJson();
        $j = \json_decode($e);
        //$this->storager->storeText('exclusions', $e);
        //$this->storager->store('exclusions', $j);
    }

    public function downloadAndParseExclusions() {
        $exclusions = $this->storager->read('exclusions');
        if (!$exclusions) {
            fwrite(STDERR, "You must download excluded ID list first.\n");
            exit(1);
        }
        $exclusions = $exclusions['exclusions'];
        $exclusionsCount = count($exclusions);
        $exclusionsI = 0;
        foreach ($exclusions as $id) {
            $exclusionsI++;
            $key = "{$id}/1";
            if ($this->storager->exists($key))
                continue;
            echo "Getting {$id} ({$exclusionsI} of {$exclusionsCount})\n";
            $splash = $this->loadSplash($id);
            if (!$splash)
                throw new Exception('wtf?');
            $this->storager->storeText("{$id}/1", $splash);
            $this->storager->store("{$id}/1", WebsiteParser::parseSplash($splash));
        }
        echo "Done1.\n";
        $exclusionsI = 0;
        foreach ($exclusions as $id) {
            $exclusionsI++;
            $i = 1;
            $key = "{$id}/{$i}";
            if (!$this->storager->exists($key) || !$this->storager->existsText($key))
                continue;
            $page1 = $this->storager->read($key);
            $pages = $page1['meta']['pages'];
            unset($page1);
            for ($i = 2; $i <= $pages; $i++) {
                $key = "{$id}/{$i}";
                if ($this->storager->exists($key))
                    continue;
                echo "Getting {$id} (page {$i} of {$pages}) (creator {$exclusionsI} of {$exclusionsCount})\n";
                $splash = $this->loadSplash($id, $i);
                if (!$splash)
                    throw new Exception('wtf?');
                $this->storager->storeText($key, $splash);
                $this->storager->store($key, WebsiteParser::parseSplash($splash));
            }
        }
        echo "Done2.\n";
    }

    public function reparseExclusions($debug = true) {
        $exclusions = $this->storager->read('exclusions')['exclusions'];
        $c = count($exclusions);
        $ci = 0;
        foreach ($exclusions as $id) {
            $ci++;
            // if ($ci < 151)continue;
            $i = 1;
            $key = "{$id}/{$i}";
            echo "===\n";
            echo "Parsing {$key} (creator {$ci} of {$c})\n";
            $parsed = WebsiteParser::parseSplash($this->storager->readText($key), $debug);
            $this->storager->store($key, $parsed);
            $pages = $parsed['meta']['pages'];
            for ($i = 2; $i <= $pages; $i++) {
                $key = "{$id}/{$i}";
                echo "Parsing {$key} (creator {$ci} of {$c})\n";
                $parsed = WebsiteParser::parseSplash($this->storager->readText($key), $debug);
                $this->storager->store($key, $parsed);
            }
        }
        echo "Parsing done.\n";
    }

    public function loadSplash(int $id, int $page = 1) {
        $pageq = ($page && $page > 1) ? "?p={$page}" : '';
        $rq = $this->http->client->request('GET', "/patreon/{$id}{$pageq}");
        // $rq = $this->http->client->request('GET', "http://httpstat.us/200?sleep=10000");
        $body = $rq->getBody()->getContents();
        // file_put_contents("test_storage/_body{$id}.html", $body);
        return $body;
        // return $rq->getStatusCode();
    }

    /**
     * 
     * @deprecated
     */
    public function downloadExclusionsJson() {
        throw new Exception("Exclusions are not available anymore.");
        //return $this->http->client->request('GET', "/exclusions.json")->getBody()->getContents();
    }

    public static function fatalNull($v) {
        if ($v === null) {
            throw new Exception("Assertion failed.");
        }
        return $v;
    }

    /**
     * 
     * @deprecated
     */
    public function parseSplash($html) {
        return WebsiteParser::parseSplash($html, true);
    }

    public function calcStorageForAll() {
        $exclusions = $this->storager->read('exclusions')['exclusions'];
        $exclusionsCount = count($exclusions);
        $exclusionsI = 0;
        foreach ($exclusions as $id) {
            $exclusionsI++;
            $i = 1;
            $key = "{$id}/{$i}";
            if (!$this->storager->exists($key)) {
                echo "Creator {$id}: No data";
                continue;
            }
            $page1 = $this->storager->read($key);
            $pages = $page1['meta']['pages'];
            $ss = round($page1['sharedfiles_storage_required'] / (1024 * 1024));
            $sp = 0;
            unset($page1);
            for ($i = 1; $i <= $pages; $i++) {
                $key = "{$id}/{$i}";
                if (!$this->storager->exists($key)) {
                    echo "Creator {$id}: No more data";
                    continue;
                }
                $POST = $this->storager->read($key);
                $sp += $POST['posts_storage_required'];
                // $ss = round($POST['sharedfiles_storage_required'] / (1024 * 1024));
                // echo "Creator {$id} ({$exclusionsI}/{$exclusionsCount}) (page {$i} of {$pages}) ({$sp} posts, {$ss} shareds)\n";
            }
            $sp = round($sp) / (1024 * 1024);
            echo "Creator {$id} ({$exclusionsI}/{$exclusionsCount}): {$pages} pages | {$ss} MiB Shared files | {$sp} MiB Shared files\n";
        }
        echo "Done2.\n";
    }

}
