<?php

namespace App;

use Error;
use GuzzleHttp\Client;

/**
 * Description of Loader
 *
 * @author https://github.com/alpeg
 * @license MIT
 */
class Loader {

    public Client $client;

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
        $this->client = new Client([
            'base_uri' => 'https://yiff.party/',
            'proxy' => 'socks5://127.0.0.1:11180',
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:68.0) Gecko/20100101 Firefox/68.0',
            ]
        ]);
        $this->storager = new Storager();
    }

    public function storeExclusions() {
        $e = $this->exclusionsJson();
        $j = \json_decode($e);
        $this->storager->storeText('exclusions', $e);
        $this->storager->store('exclusions', $j);
    }

    public function loadExclusions() {
        $exclusions = $this->storager->read('exclusions')['exclusions'];
        foreach ($exclusions as $id) {
            $key = "{$id}/1";
            if ($this->storager->existsText($key))
                continue;
            echo "Getting {$id}\n";
            $splash = $this->loadSplash($id);
            if (!$splash)
                throw new Error('wtf?');
            $this->storager->storeText("{$id}/1", $splash);
            $this->storager->store("{$id}/1", WebsiteParser::parseSplash($splash));
        }
        echo "Done1.\n";
        foreach ($exclusions as $id) {
            $i = 1;
            $key = "{$id}/{$i}";
            if (!$this->storager->exists($key) || !$this->storager->existsText($key))
                continue;
            $page1 = $this->storager->read($key);
            $pages = $page1['meta']['pages'];
            unset($page1);
            for ($i = 2; $i <= $pages; $i++) {
                $key = "{$id}/{$i}";
                if ($this->storager->exists($key) || $this->storager->existsText($key))
                    continue;
                echo "Getting {$id} ({$i} of {$pages})\n";
                $splash = $this->loadSplash($id, $i);
                if (!$splash)
                    throw new Error('wtf?');
                $this->storager->storeText($key, $splash);
                $this->storager->store($key, WebsiteParser::parseSplash($splash));
            }
        }
        echo "Done2.\n";
    }

    public function reparseExclusions() {
        $exclusions = $this->storager->read('exclusions')['exclusions'];
        $c = count($exclusions);
        $ci = 0;
        foreach ($exclusions as $id) {
            $ci++;
            $i = 1;
            $key = "{$id}/{$i}";
            echo "Parsing {$key}\n";
            $parsed = WebsiteParser::parseSplash($this->storager->readText($key), true);
            $pages = $parsed['meta']['pages'];
            for ($i = 2; $i <= $pages; $i++) {
                $key = "{$id}/{$i}";
                echo "Parsing {$key} (creator {$ci} of {$c})\n";
                $parsed = WebsiteParser::parseSplash($this->storager->readText($key), true);
            }
        }
        echo "Parsing done.\n";
    }

    public function loadSplash(int $id, int $page = 1) {
        $pageq = ($page && $page > 1) ? "?p={$page}" : '';
        $rq = $this->client->request('GET', "/patreon/{$id}{$pageq}");
        // $rq = $this->client->request('GET', "http://httpstat.us/200?sleep=10000");
        $body = $rq->getBody()->getContents();
        // file_put_contents("test_storage/_body{$id}.html", $body);
        return $body;
        // return $rq->getStatusCode();
    }

    public function exclusionsJson() {
        $rq = $this->client->request('GET', "/exclusions.json");
        $body = $rq->getBody()->getContents();
        return $body;
    }

    public static function fatalNull($v) {
        if ($v === null) {
            throw new Error("Assertion failed.");
        }
        return $v;
    }

    public function parseSplash($html) {
        return WebsiteParser::parseSplash($html, true);
    }

}
