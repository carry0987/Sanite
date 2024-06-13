<?php
namespace carry0987\Sanite\Tests\Models;

use carry0987\Sanite\Models\DataDeleteModel;
use carry0987\Sanite\Sanite;
use carry0987\Sanite\Exceptions\DatabaseException;
use PDO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DataDeleteModelTest extends TestCase
{
    private MockObject $pdoMock;
    private DataDeleteModel $dataDeleteModel;

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

        // create a subclass of DataDeleteModel directly
        $this->dataDeleteModel = new class($saniteStub) extends DataDeleteModel
        {
        };
    }

    public function testDeleteSingleData()
    {
        $queryArray = ['query' => 'DELETE FROM users WHERE id = ?', 'bind' => 'i'];
        $dataArray = [1];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);

        $result = $this->dataDeleteModel->deleteSingleData($queryArray, $dataArray);

        $this->assertTrue($result);
    }

    public function testDeleteSingleDataThrowsException()
    {
        $queryArray = ['query' => 'DELETE FROM users WHERE id = ?', 'bind' => 'i'];
        $dataArray = [1];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->will($this->throwException(new \PDOException()));

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);

        $this->expectException(DatabaseException::class);
        $this->dataDeleteModel->deleteSingleData($queryArray, $dataArray);
    }

    public function testDeleteMultipleData()
    {
        $queryArray = ['query' => 'DELETE FROM users WHERE id = ?', 'bind' => 'i'];
        $dataArray = [[1], [2]];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->exactly(2))->method('execute')->willReturn(true);

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $result = $this->dataDeleteModel->deleteMultipleData($queryArray, $dataArray);

        $this->assertTrue($result);
    }

    public function testDeleteMultipleDataThrowsException()
    {
        $queryArray = ['query' => 'DELETE FROM users WHERE id = ?', 'bind' => 'i'];
        $dataArray = [[1], [2]];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->will($this->throwException(new \PDOException()));

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('inTransaction');

        $this->expectException(DatabaseException::class);
        $this->dataDeleteModel->deleteMultipleData($queryArray, $dataArray);
    }
}
