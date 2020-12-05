<?php

namespace App;

/**
 * Description of Database
 *
 * @author https://github.com/alpeg
 * @license MIT
 */
class Database {

    private static Database $inst;

    public static function i(): Database {
        return (self::$inst) ?? (self::$inst = new self());
    }

    public \PDO $pdo;

    public function executePrepared($sql, $params = null, $fetchNum = null) {
        $sql = str_replace('`%', '`' . Config::i()->get('mysql.table_prefix'), $sql);
        $fetchMode = is_integer($fetchNum) ? $fetchNum : ($fetchNum ? \PDO::FETCH_NUM : \PDO::FETCH_ASSOC);
        $q = $this->pdo->prepare($sql);
        if ($params == null) {
            $params = [];
        } elseif (!is_array($params)) {
            $params = [$params];
        }
        $q->execute($params);
        while (($row = $q->fetch($fetchMode)) !== false) {
            yield $row;
        }
    }

    public function prepare($sql): \PDOStatement {
        $sql = str_replace('`%', '`' . Config::i()->get('mysql.table_prefix'), $sql);
        return $this->pdo->prepare($sql);
    }

    public function executeUnprepared($sql) {
        $sql = str_replace('`%', '`' . Config::i()->get('mysql.table_prefix'), $sql);
        return $this->pdo->exec($sql);
    }

    function __construct() {
        $this->pdo = new \PDO(
                'mysql:host=' . Config::i()->get('mysql.host')
                . (Config::i()->get('mysql.port') ? (';port=' . Config::i()->get('mysql.port')) : '')
                . ';dbname=' . Config::i()->get('mysql.db'),
                Config::i()->get('mysql.user'),
                Config::i()->get('mysql.password'), [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false,
                // \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec('SET NAMES ' . Config::i()->get('mysql.charset') . ' COLLATE ' . Config::i()->get('mysql.collate'));
    }

}
