<?php
namespace carry0987\Sanite\Tests\Models;

use carry0987\Sanite\Models\DataCreateModel;
use carry0987\Sanite\Sanite;
use carry0987\Sanite\Exceptions\DatabaseException;
use PDO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DataCreateModelTest extends TestCase
{
    private MockObject $pdoMock;
    private DataCreateModel $dataCreateModel;

    protected function setUp(): void
    {
        // create the mock of PDO
        $this->pdoMock = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->getMock();

        // create a stub for Sanite
        $saniteStub = $this->getMockBuilder(Sanite::class)
            ->disableOriginalConstructor()
            ->getMock();
        $saniteStub->method('getConnection')->willReturn($this->pdoMock);

        // create a subclass of DataCreateModel directly
        $this->dataCreateModel = new class($saniteStub) extends DataCreateModel
        {
        };
    }

    public function testCreateSingleData()
    {
        $queryArray = ['query' => 'INSERT INTO users (username) VALUES (?)', 'bind' => 's'];
        $dataArray = ['testuser'];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);
        $this->pdoMock->expects($this->once())->method('lastInsertId')->with(null)->willReturn('1');

        $result = $this->dataCreateModel->createSingleData($queryArray, $dataArray, true);

        $this->assertTrue($result['execute']);
        $this->assertSame(1, $result['auto_increment']);
    }

    public function testCreateSingleDataWithSequenceName()
    {
        $queryArray = ['query' => 'INSERT INTO users (username) VALUES ($1) RETURNING id', 'bind' => 's'];
        $dataArray = ['testuser'];
        $sequenceName = 'users_id_seq';

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);
        $this->pdoMock->expects($this->once())->method('lastInsertId')->with($sequenceName)->willReturn('42');

        $result = $this->dataCreateModel->createSingleData($queryArray, $dataArray, true, $sequenceName);

        $this->assertTrue($result['execute']);
        $this->assertSame(42, $result['auto_increment']);
    }

    public function testCreateSingleDataWithoutAutoIncrementDoesNotRequestId()
    {
        $queryArray = ['query' => 'INSERT INTO users (username) VALUES (?)', 'bind' => 's'];
        $dataArray = ['testuser'];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);
        $this->pdoMock->expects($this->never())->method('lastInsertId');

        $this->assertTrue($this->dataCreateModel->createSingleData($queryArray, $dataArray));
    }

    public function testCreateSingleReturning()
    {
        $queryArray = ['query' => 'INSERT INTO users (username) VALUES (?) RETURNING id, username', 'bind' => 's'];
        $dataArray = ['testuser'];
        $returnedRow = ['id' => 42, 'username' => 'testuser'];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($returnedRow);

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);
        $this->pdoMock->expects($this->never())->method('lastInsertId');

        $this->assertSame($returnedRow, $this->dataCreateModel->createSingleReturning($queryArray, $dataArray));
    }

    public function testDatabaseExceptionPreservesPDOErrorInfo()
    {
        $queryArray = ['query' => 'INSERT INTO users (username) VALUES (?)', 'bind' => 's'];
        $dataArray = ['duplicate'];
        $pdoException = new \PDOException('Unique violation');
        $pdoException->errorInfo = ['23505', 7, 'duplicate key value'];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->method('execute')->willThrowException($pdoException);
        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        try {
            $this->dataCreateModel->createSingleData($queryArray, $dataArray);
            $this->fail('Expected DatabaseException was not thrown');
        } catch (DatabaseException $exception) {
            $this->assertSame($pdoException->errorInfo, $exception->getErrorInfo());
            $this->assertSame($pdoException, $exception->getPrevious());
        }
    }

    public function testCreateSingleDataThrowsException()
    {
        $queryArray = ['query' => 'INSERT INTO users (username) VALUES (?)', 'bind' => 's'];
        $dataArray = ['testuser'];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->will($this->throwException(new \PDOException()));

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);

        $this->expectException(DatabaseException::class);
        $this->dataCreateModel->createSingleData($queryArray, $dataArray, true);
    }

    public function testCreateMultipleData()
    {
        $queryArray = ['query' => 'INSERT INTO users (username) VALUES (?)', 'bind' => 's'];
        $dataArray = [['testuser1'], ['testuser2']];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->exactly(2))->method('execute')->willReturn(true);

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $result = $this->dataCreateModel->createMultipleData($queryArray, $dataArray);

        $this->assertTrue($result);
    }

    public function testCreateMultipleDataThrowsException()
    {
        $queryArray = ['query' => 'INSERT INTO users (username) VALUES (?)', 'bind' => 's'];
        $dataArray = [['testuser1'], ['testuser2']];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->will($this->throwException(new \PDOException()));

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('inTransaction');

        $this->expectException(DatabaseException::class);
        $this->dataCreateModel->createMultipleData($queryArray, $dataArray);
    }
}
