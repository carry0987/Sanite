<?php
namespace carry0987\Sanite\Tests\Integration;

use carry0987\Sanite\Exceptions\DatabaseException;
use carry0987\Sanite\Models\DataCreateModel;
use carry0987\Sanite\Models\DataDeleteModel;
use carry0987\Sanite\Models\DataReadModel;
use carry0987\Sanite\Models\DataUpdateModel;
use carry0987\Sanite\Sanite;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseIntegrationTest extends TestCase
{
    private string $driver;
    private PDO $pdo;
    private IntegrationCreateModel $createModel;
    private IntegrationReadModel $readModel;
    private IntegrationUpdateModel $updateModel;
    private IntegrationDeleteModel $deleteModel;

    protected function setUp(): void
    {
        $driver = getenv('SANITE_TEST_DRIVER');
        if (!in_array($driver, [Sanite::DRIVER_MYSQL, Sanite::DRIVER_PGSQL], true)) {
            $this->markTestSkipped('Set SANITE_TEST_DRIVER to mysql or pgsql to run integration tests.');
        }

        $this->driver = $driver;
        $sanite = new Sanite([
            'driver' => $driver,
            'host' => getenv('SANITE_TEST_HOST') ?: '127.0.0.1',
            'database' => getenv('SANITE_TEST_DATABASE') ?: 'sanite_test',
            'username' => getenv('SANITE_TEST_USERNAME') ?: 'sanite',
            'password' => getenv('SANITE_TEST_PASSWORD') ?: 'sanite',
            'port' => (int) (getenv('SANITE_TEST_PORT') ?: ($driver === Sanite::DRIVER_PGSQL ? 5432 : 3306)),
        ]);
        $this->pdo = $sanite->getConnection();
        $this->resetTable();

        $this->createModel = new IntegrationCreateModel($sanite);
        $this->readModel = new IntegrationReadModel($sanite);
        $this->updateModel = new IntegrationUpdateModel($sanite);
        $this->deleteModel = new IntegrationDeleteModel($sanite);
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo)) {
            $this->pdo->exec('DROP TABLE IF EXISTS sanite_integration');
        }
    }

    public function testConnectionAndQuestionMarkPlaceholder(): void
    {
        $statement = $this->pdo->prepare('SELECT ? AS value');
        $statement->execute(['connected']);

        $this->assertSame($this->driver, $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        $this->assertSame('connected', $statement->fetchColumn());
    }

    public function testCrud(): void
    {
        $this->assertTrue($this->createModel->createSingleData(
            ['query' => 'INSERT INTO sanite_integration (username) VALUES (?)', 'bind' => 's'],
            ['alice']
        ));

        $row = $this->readModel->getSingleData(
            ['query' => 'SELECT id, username FROM sanite_integration WHERE username = ?', 'bind' => 's'],
            ['alice']
        );
        $this->assertSame('alice', $row['username']);

        $this->assertTrue($this->updateModel->updateSingleData(
            ['query' => 'UPDATE sanite_integration SET username = ? WHERE id = ?', 'bind' => 'si'],
            ['bob', (int) $row['id']]
        ));
        $this->assertTrue($this->deleteModel->deleteSingleData(
            ['query' => 'DELETE FROM sanite_integration WHERE id = ?', 'bind' => 'i'],
            [(int) $row['id']]
        ));
        $this->assertSame(0, $this->readModel->getDataCount([
            'query' => 'SELECT COUNT(*) FROM sanite_integration',
        ]));
    }

    public function testLastInsertId(): void
    {
        $sequenceName = $this->driver === Sanite::DRIVER_PGSQL ? 'sanite_integration_id_seq' : null;
        $result = $this->createModel->createSingleData(
            ['query' => 'INSERT INTO sanite_integration (username) VALUES (?)', 'bind' => 's'],
            ['alice'],
            true,
            $sequenceName
        );

        $this->assertTrue($result['execute']);
        $this->assertGreaterThan(0, $result['auto_increment']);
    }

    public function testReturning(): void
    {
        if ($this->driver !== Sanite::DRIVER_PGSQL) {
            $this->markTestSkipped('RETURNING integration is PostgreSQL-specific.');
        }

        $row = $this->createModel->createSingleReturning(
            ['query' => 'INSERT INTO sanite_integration (username) VALUES (?) RETURNING id, username', 'bind' => 's'],
            ['alice']
        );

        $this->assertGreaterThan(0, $row['id']);
        $this->assertSame('alice', $row['username']);
    }

    public function testMultipleCreateRollsBackOnFailure(): void
    {
        try {
            $this->createModel->createMultipleData(
                ['query' => 'INSERT INTO sanite_integration (username) VALUES (?)', 'bind' => 's'],
                [['duplicate'], ['duplicate']]
            );
            $this->fail('Expected DatabaseException was not thrown');
        } catch (DatabaseException $exception) {
            $this->assertNotEmpty($exception->getErrorInfo());
            $this->assertStringStartsWith('23', (string) $exception->getErrorInfo()[0]);
        }

        $this->assertSame(0, $this->readModel->getDataCount([
            'query' => 'SELECT COUNT(*) FROM sanite_integration',
        ]));
    }

    private function resetTable(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS sanite_integration');
        $idDefinition = $this->driver === Sanite::DRIVER_PGSQL
            ? 'SERIAL PRIMARY KEY'
            : 'INT AUTO_INCREMENT PRIMARY KEY';
        $this->pdo->exec(
            "CREATE TABLE sanite_integration (id {$idDefinition}, username VARCHAR(100) NOT NULL UNIQUE)"
        );
    }
}

final class IntegrationCreateModel extends DataCreateModel
{
}

final class IntegrationReadModel extends DataReadModel
{
}

final class IntegrationUpdateModel extends DataUpdateModel
{
}

final class IntegrationDeleteModel extends DataDeleteModel
{
}
