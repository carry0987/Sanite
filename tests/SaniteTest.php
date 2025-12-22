<?php
namespace carry0987\Sanite\Tests;

use carry0987\Sanite\Sanite;
use carry0987\Sanite\Exceptions\DatabaseException;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SaniteTest extends TestCase
{
    /**
     * Test MySQL default configuration
     */
    public function testMySQLDefaultConfig()
    {
        $config = [
            'host' => 'localhost',
            'database' => 'testdb',
            'username' => 'user',
            'password' => 'pass',
        ];

        $result = $this->invokeSetConfig($config);

        [$driver, $host, $database, $username, $password, $charset, $port] = $result;

        $this->assertSame('mysql', $driver);
        $this->assertSame('localhost', $host);
        $this->assertSame('testdb', $database);
        $this->assertSame('user', $username);
        $this->assertSame('pass', $password);
        $this->assertSame('utf8mb4', $charset);
        $this->assertSame(3306, $port);
    }

    /**
     * Test PostgreSQL default configuration
     */
    public function testPostgreSQLDefaultConfig()
    {
        $config = [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'database' => 'testdb',
            'username' => 'postgres',
            'password' => 'pass',
        ];

        $result = $this->invokeSetConfig($config);

        [$driver, $host, $database, $username, $password, $charset, $port] = $result;

        $this->assertSame('pgsql', $driver);
        $this->assertSame('localhost', $host);
        $this->assertSame('testdb', $database);
        $this->assertSame('postgres', $username);
        $this->assertSame('pass', $password);
        $this->assertSame('utf8', $charset);
        $this->assertSame(5432, $port);
    }

    /**
     * Test custom port and charset configuration
     */
    public function testCustomPortAndCharset()
    {
        $config = [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'database' => 'testdb',
            'username' => 'postgres',
            'password' => 'pass',
            'port' => 5433,
            'charset' => 'latin1',
        ];

        $result = $this->invokeSetConfig($config);

        [$driver, $host, $database, $username, $password, $charset, $port] = $result;

        $this->assertSame(5433, $port);
        $this->assertSame('latin1', $charset);
    }

    /**
     * Test MySQL DSN building
     */
    public function testMySQLDSNBuilding()
    {
        $dsn = $this->invokeBuildDSN('mysql', 'localhost', 'testdb', 'utf8mb4', 3306);

        $this->assertSame('mysql:host=localhost;dbname=testdb;charset=utf8mb4;port=3306', $dsn);
    }

    /**
     * Test PostgreSQL DSN building
     */
    public function testPostgreSQLDSNBuilding()
    {
        $dsn = $this->invokeBuildDSN('pgsql', 'localhost', 'testdb', 'utf8', 5432);

        $this->assertSame('pgsql:host=localhost;dbname=testdb;port=5432', $dsn);
    }

    /**
     * Test MariaDB DSN building (should use MySQL format)
     */
    public function testMariaDBDSNBuilding()
    {
        $dsn = $this->invokeBuildDSN('mariadb', 'localhost', 'testdb', 'utf8mb4', 3306);

        $this->assertSame('mariadb:host=localhost;dbname=testdb;charset=utf8mb4;port=3306', $dsn);
    }

    /**
     * Test Sanite with PDO instance
     */
    public function testConstructWithPDOInstance()
    {
        $pdoMock = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdoMock->expects($this->exactly(3))
            ->method('setAttribute')
            ->willReturn(true);

        $pdoMock->expects($this->once())
            ->method('getAttribute')
            ->with(PDO::ATTR_SERVER_VERSION)
            ->willReturn('8.0.0');

        $sanite = new Sanite($pdoMock);

        $this->assertInstanceOf(Sanite::class, $sanite);
        $this->assertSame($pdoMock, $sanite->getConnection());
    }

    /**
     * Test getPDOVersion returns correct version
     */
    public function testGetPDOVersion()
    {
        $pdoMock = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdoMock->method('setAttribute')->willReturn(true);
        $pdoMock->method('getAttribute')
            ->with(PDO::ATTR_SERVER_VERSION)
            ->willReturn('15.0');

        new Sanite($pdoMock);

        $this->assertSame('15.0', Sanite::getPDOVersion());
    }

    /**
     * Helper method to invoke private setConfig method
     */
    private function invokeSetConfig(array $config): array
    {
        $reflection = new ReflectionClass(Sanite::class);
        $method = $reflection->getMethod('setConfig');
        $method->setAccessible(true);

        return $method->invoke(null, $config);
    }

    /**
     * Helper method to invoke private buildDSN method
     */
    private function invokeBuildDSN(string $driver, string $host, string $database, string $charset, int $port): string
    {
        $reflection = new ReflectionClass(Sanite::class);
        $method = $reflection->getMethod('buildDSN');
        $method->setAccessible(true);

        return $method->invoke(null, $driver, $host, $database, $charset, $port);
    }
}
