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
        $this->pdoMock->expects($this->once())->method('lastInsertId')->willReturn('1');

        $result = $this->dataCreateModel->createSingleData($queryArray, $dataArray, true);

        $this->assertTrue($result['execute']);
        $this->assertSame(1, $result['auto_increment']);
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
