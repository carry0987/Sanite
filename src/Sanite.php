<?php
namespace carry0987\Sanite;

use carry0987\Sanite\Exceptions\DatabaseException;
use PDO;

class Sanite
{
    // Database driver constants
    public const DRIVER_MYSQL = 'mysql';
    public const DRIVER_PGSQL = 'pgsql';

    private ?PDO $connectdb = null;
    private static ?string $version = null;
    private string $driver;

    public function __construct(array|PDO $dbConfig)
    {
        try {
            if ($dbConfig instanceof PDO) {
                $this->connectdb = $dbConfig;
                $this->driver = $this->connectdb->getAttribute(PDO::ATTR_DRIVER_NAME);
            } else {
                // Get config
                [$driver, $host, $database, $username, $password, $charset, $db_port] = self::setConfig($dbConfig);
                $this->driver = $driver;
                $this->connectdb = new PDO(self::buildDSN($driver, $host, $database, $charset, $db_port), $username, $password);

                // Set charset for PostgreSQL
                if ($driver === 'pgsql' && !empty($charset)) {
                    $quotedCharset = $this->connectdb->quote($charset);
                    if ($quotedCharset === false) {
                        throw new DatabaseException('Unable to quote PostgreSQL client encoding');
                    }
                    $this->connectdb->exec("SET client_encoding TO {$quotedCharset}");
                }
            }

            // Set attributes
            $this->connectdb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connectdb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connectdb->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // Get version
            self::$version = $this->connectdb->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e);
        }
    }

    private static function setConfig(array $dbConfig): array
    {
        $driver = $dbConfig['driver'] ?? 'mysql';
        if (!in_array($driver, [self::DRIVER_MYSQL, self::DRIVER_PGSQL], true)) {
            throw new DatabaseException("Unsupported database driver: {$driver}");
        }

        $host = $dbConfig['host'] ?? '127.0.0.1';
        $database = $dbConfig['database'] ?? '';
        $username = $dbConfig['username'] ?? '';
        $password = $dbConfig['password'] ?? '';

        // Set default port and charset based on driver
        $defaultPort = match ($driver) {
            'pgsql' => 5432,
            default => 3306,
        };
        $defaultCharset = match ($driver) {
            'pgsql' => 'utf8',
            default => 'utf8mb4',
        };

        $charset = $dbConfig['charset'] ?? $defaultCharset;
        $port = $dbConfig['port'] ?? $defaultPort;

        return [$driver, $host, $database, $username, $password, $charset, $port];
    }

    private static function buildDSN(string $driver, string $db_host, string $db_name, string $charset, int $db_port): string
    {
        return match ($driver) {
            'pgsql' => self::buildPostgreSQLDSN($db_host, $db_name, $db_port),
            default => self::buildMySQLDSN($driver, $db_host, $db_name, $charset, $db_port),
        };
    }

    private static function buildMySQLDSN(string $driver, string $db_host, string $db_name, string $charset, int $db_port): string
    {
        $dsn = $driver.':host='.$db_host.';dbname='.$db_name;
        if (!empty($charset)) {
            $dsn .= ';charset='.$charset;
        }
        if (!empty($db_port)) {
            $dsn .= ';port='.$db_port;
        }

        return $dsn;
    }

    private static function buildPostgreSQLDSN(string $db_host, string $db_name, int $db_port): string
    {
        $dsn = 'pgsql:host='.$db_host.';dbname='.$db_name;
        if (!empty($db_port)) {
            $dsn .= ';port='.$db_port;
        }

        return $dsn;
    }

    // Get PDO connection
    public function getConnection(): PDO
    {
        if (empty($this->connectdb)) {
            throw new DatabaseException('Database connection is empty');
        }

        return $this->connectdb;
    }

    /**
     * Get the current database driver name
     * 
     * @return string One of DRIVER_* constants
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Check if the current driver is PostgreSQL
     * 
     * @return bool
     */
    public function isPostgreSQL(): bool
    {
        return $this->driver === self::DRIVER_PGSQL;
    }

    /**
     * Check if the current driver is MySQL/MariaDB
     * 
     * @return bool
     */
    public function isMySQL(): bool
    {
        return $this->driver === self::DRIVER_MYSQL;
    }

    public static function getPDOVersion(): string
    {
        return self::$version ?? 'Unknown';
    }
}
