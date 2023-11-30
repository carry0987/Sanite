<?php
namespace carry0987\Sanite;

use carry0987\Sanite\Exceptions\DatabaseException;
use PDO;

class Sanite
{
    private $connectdb = null;

    public function __construct(string $db_host, string $db_name, string $username, string $password, string $charset = 'utf8mb4', int $db_port = 3306)
    {
        try {
            $this->connectdb = new PDO(self::buildDSN('mysql', $db_host, $db_name, $charset, $db_port), $username, $password);
            $this->connectdb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }
    }

    private static function buildDSN(string $db_type, string $db_host, string $db_name, string $charset, int $db_port)
    {
        $dsn = $db_type.':host='.$db_host.';dbname='.$db_name;
        if (!empty($charset)) {
            $dsn .= ';charset='.$charset;
        }
        if (!empty($db_port)) {
            $dsn .= ';port='.$db_port;
        }

        return $dsn;
    }

    // Get PDO connection
    public function getConnection()
    {
        return $this->connectdb;
    }

    public static function getPDOVersion()
    {
        return self::$version;
    }
}
