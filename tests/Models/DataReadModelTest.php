<?php
namespace carry0987\Sanite\Tests\Models;

use carry0987\Sanite\Models\DataReadModel;
use carry0987\Sanite\Sanite;
use carry0987\Sanite\Exceptions\DatabaseException;
use PDO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DataReadModelTest extends TestCase
{
    private MockObject $pdoMock;
    private DataReadModel $dataReadModel;

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

        // create a subclass of DataReadModel directly
        $this->dataReadModel = new class($saniteStub) extends DataReadModel
        {
        };
    }

    public function testGetSingleData()
    {
        $queryArray = ['query' => 'SELECT * FROM users WHERE id = ?', 'bind' => 'i'];
        $dataArray = [1];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['id' => 1, 'username' => 'test']);

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);

        $result = $this->dataReadModel->getSingleData($queryArray, $dataArray);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testGetSingleDataThrowsException()
    {
        $queryArray = ['query' => 'SELECT * FROM users WHERE id = ?', 'bind' => 'i'];
        $dataArray = [1];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->will($this->throwException(new \PDOException()));

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);

        $this->expectException(DatabaseException::class);
        $this->dataReadModel->getSingleData($queryArray, $dataArray);
    }

    public function testGetMultipleData()
    {
        $queryArray = ['query' => 'SELECT * FROM users', 'bind' => ''];
        $dataArray = null;

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([
            ['id' => 1, 'username' => 'test1'],
            ['id' => 2, 'username' => 'test2']
        ]);

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);

        $result = $this->dataReadModel->getMultipleData($queryArray, $dataArray);

        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
    }

    public function testGetMultipleDataThrowsException()
    {
        $queryArray = ['query' => 'SELECT * FROM users', 'bind' => ''];
        $dataArray = null;

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->will($this->throwException(new \PDOException()));

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);

        $this->expectException(DatabaseException::class);
        $this->dataReadModel->getMultipleData($queryArray, $dataArray);
    }

    public function testGetDataCount()
    {
        $queryArray = ['query' => 'SELECT COUNT(*) FROM users', 'bind' => ''];
        $dataArray = null;

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())->method('fetchColumn')->willReturn(42);

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);

        $result = $this->dataReadModel->getDataCount($queryArray, $dataArray);

        $this->assertEquals(42, $result);
    }

    public function testGetDataCountThrowsException()
    {
        $queryArray = ['query' => 'SELECT COUNT(*) FROM users', 'bind' => ''];
        $dataArray = null;

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->will($this->throwException(new \PDOException()));

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);

        $this->expectException(DatabaseException::class);
        $this->dataReadModel->getDataCount($queryArray, $dataArray);
    }
}
