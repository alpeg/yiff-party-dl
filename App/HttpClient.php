<?php

namespace App;

use GuzzleHttp\Client;

/**
 * Description of HttpClient
 *
 * @author https://github.com/alpeg
 * @license MIT
 */
class HttpClient {

    public Client $client;

    function __construct($base = null) {
        $options = [];
        $base && $options['base_uri'] = $base;
        Config::i()->get('proxy') && ($options['proxy'] = Config::i()->get('proxy'));
        $options['timeout'] = Config::i()->get('timeout') ?? 40;
        $options['headers'] = [];
        Config::i()->get('user_agent') && ($options['headers']['User-Agent'] = Config::i()->get('user_agent'));
        $this->client = new Client($options);
    }

}
