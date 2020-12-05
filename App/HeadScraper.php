<?php

namespace App;

use App\HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;

/**
 * Description of HeadScraper
 *
 * @author https://github.com/alpeg
 * @license MIT
 */
class HeadScraper {

    public HttpClient $http;

    function __construct() {
        $this->http = new HttpClient('https://data.yiff.party/');
    }

    public static function headerToOne($h) {
        $h = $h ?? null;
        if (!is_array($h) || count($h) < 1)
            return null;
        return $h[0];
    }

    public function f() {
        Database::i()->executeUnprepared('CREATE TABLE IF NOT EXISTS `%sizes` (
                `pk` INT(11) NOT NULL,
                `size` BIGINT(20) NULL DEFAULT NULL,
                `mod` INT(11) NULL DEFAULT NULL,
                `etag` VARCHAR(255) NULL DEFAULT NULL COLLATE \'' . Config::i()->get('mysql.collate') . '\',
                `http_code` SMALLINT(6) NULL DEFAULT NULL,
                PRIMARY KEY (`pk`) USING BTREE
        )
        COLLATE=\'' . Config::i()->get('mysql.collate') . '\'
        ENGINE=InnoDB');
        $qUrls = Database::i()->executePrepared('SELECT `%urls`.`pk`,`%urls`.`url`'
                . ' FROM `%urls` LEFT JOIN `%sizes` ON `%urls`.`pk` = `%sizes`.`pk`'
                . ' WHERE `%sizes`.`pk` IS NULL'
                . ' AND `%urls`.`url` LIKE \'https://data.yiff.party/%\''
                . ' ORDER BY `%urls`.`pk` ASC');
        $cl = $this->http->client;
        $dataArrIndex = 0;
        $dataArr = [];

        $insert = Database::i()->prepare('INSERT INTO `%sizes` (`pk`, `size`, `mod`, `etag`, `http_code`) VALUES (?,?,?,?,?)');

        $db2row = function()use($qUrls, $cl, &$dataArr, $dataArrIndex) {
            foreach ($qUrls as $row) {
                yield function()use($row, $cl, &$dataArr, $dataArrIndex) {
                    // echo "Spawn: pk{$row['pk']}, index {$dataArrIndex}\n";
                    $dataArr[$dataArrIndex] = $row;
                    // echo $row['url'] . "\n";
                    return $cl->headAsync($row['url']);
                };
                $dataArrIndex++;
            }
        };
        unset($qUrls);
        // $this->http->client->requestAsync('HEAD', $uri, $options);

        $pool = new Pool($this->http->client, $db2row(), [
            'concurrency' => 20,
            'fulfilled' => function (Response $response, $index)use(&$dataArr, $insert) {
                // echo "fulfilled: pk{$dataArr[$index]['pk']}, index {$index}\n";
                $hType = HeadScraper::headerToOne($response->getHeader('Content-Type'));
                $hLen = HeadScraper::headerToOne($response->getHeader('Content-Length'));
                $hMtime = HeadScraper::headerToOne($response->getHeader('Last-Modified'));
                $hEtag = HeadScraper::headerToOne($response->getHeader('ETag'));
                // $hNonExist = HeadScraper::headerToOne($response->getHeader('nonexistentheader')); // []
                // `pk`, `size`, `mod`, `etag`, `http_code`
                $insert->execute([
                    $dataArr[$index]['pk'],
                    intval($hLen),
                    intval(strtotime($hMtime)),
                    trim($hEtag, '"'),
                    $response->getStatusCode()
                ]);
                // echo json_encode([$dataArr[$index], $hType, $hLen, $hMtime, $hEtag, $hNonExist], JSON_PRETTY_PRINT) . "\n";
                unset($dataArr[$index]);
            },
            'rejected' => function (RequestException $reason, $index)use(&$dataArr, $insert) {
                $proc404 = false;
                try {
                    $status = $reason->getResponse()->getStatusCode();
                    if ($status == 404) {
                        $proc404 = true;
                        // `pk`, `size`, `mod`, `etag`, `http_code`
                        $insert->execute([
                            $dataArr[$index]['pk'],
                            null,
                            null,
                            null,
                            $status
                        ]);
                        // echo "404: " . json_encode([$status]) . " --status\n";
                    } else {
                        
                    }
                } catch (\Throwable $t) {
                    echo "WTF-" . $t->getMessage() . "\n";
                }
                if (!$proc404) {
                    echo "Rejected: pk{$dataArr[$index]['pk']}, index {$index}\n";
                    echo $reason->getMessage() . "\n";
                    echo $dataArr[$index]['url'] . "\n";
                }
                unset($dataArr[$index]);
            },
        ]);
        $pool->promise()->wait();
    }

}
