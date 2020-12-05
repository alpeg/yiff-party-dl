<?php

namespace App;

use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;
use GetOpt\Command;
use GetOpt\GetOpt;
use GetOpt\Option;

/**
 * Description of ConsoleRunner
 *
 * @author https://github.com/alpeg
 * @license MIT
 */
class ConsoleRunner {

    public static function run() {
        $getOpt = new GetOpt();
        $getOpt->addOptions([
                    Option::create(null, 'version', GetOpt::NO_ARGUMENT)
                    ->setDescription('Show version information and quit'),
                    Option::create('h', 'help', GetOpt::NO_ARGUMENT)
                    ->setDescription('Show this help and quit'),
        ]);
        $getOpt->addCommand(Command::create('ex:id:fetch', function () {
                    (new Loader())->downloadAndStoreExclusions();
                })->setDescription('Fetch excluded creators identifiers (from /exclusions.json) into local storage'));
        $getOpt->addCommand(Command::create('ex:pages:fetch', function () {
                    (new Loader())->downloadAndParseExclusions();
                })->setDescription('Fetch and parse pages of excluded creators into storage'));
        $getOpt->addCommand(Command::create('ex:pages:parse', function () {
                    (new Loader())->reparseExclusions(false);
                })->setDescription('Parse locally stored pages of excluded creators again'));
        $getOpt->addCommand(Command::create('ex:pages:parse-debug', function () {
                    (new Loader())->reparseExclusions(true);
                })->setDescription('Debug parser by parsing locally stored pages of excluded creators (for developers)'));
        $getOpt->addCommand(Command::create('ex:pages:csv', function () {
                    ExportUrlsCsv::exportUrls((new Loader())->storager);
                })->setDescription('Export csv with all URLs of locally stored excluded creators pages'));
        $getOpt->addCommand(Command::create('ex:pages:calc-storage', function () {
                    (new Loader())->calcStorageForAll();
                })->setDescription('Calculate storage required to store attachments and shared files (only calculates files which have file size information, which does not include post and inline images).'));
        $getOpt->addCommand(Command::create('config:dump', function () {
                    Config::i()->dump();
                })->setDescription('Dump configuration file options'));
        $getOpt->addCommand(Command::create('db:headscraper', function () {
                    (new HeadScraper())->f();
                })->setDescription('Dump configuration file options'));

        try {
            try {
                $getOpt->process();
            } catch (Missing $exception) {
                // catch missing exceptions if help is requested
                if (!$getOpt->getOption('help')) {
                    throw $exception;
                }
            }
        } catch (ArgumentException $exception) {
            file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL);
            echo PHP_EOL . $getOpt->getHelpText();
            exit(1);
        }
        // show version and quit
        if ($getOpt->getOption('version')) {
            echo sprintf('%s: %s' . PHP_EOL, 'yiff-party-dl', 'git');
            exit(0);
        }
        // show help and quit
        $command = $getOpt->getCommand();
        if (!$command || $getOpt->getOption('help')) {
            echo $getOpt->getHelpText();
            exit(0);
        }
        call_user_func($command->getHandler(), $getOpt);
    }

}
