<?php
namespace carry0987\Sanite\Tests\Models;

use carry0987\Sanite\Models\DataUpdateModel;
use carry0987\Sanite\Sanite;
use carry0987\Sanite\Exceptions\DatabaseException;
use PDO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DataUpdateModelTest extends TestCase
{
    private MockObject $pdoMock;
    private DataUpdateModel $dataUpdateModel;

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

        // create a subclass of DataUpdateModel directly
        $this->dataUpdateModel = new class($saniteStub) extends DataUpdateModel
        {
        };
    }

    public function testUpdateSingleData()
    {
        $queryArray = ['query' => 'UPDATE users SET username = ? WHERE id = ?', 'bind' => 'si'];
        $dataArray = ['newusername', 1];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);

        $result = $this->dataUpdateModel->updateSingleData($queryArray, $dataArray);

        $this->assertTrue($result);
    }

    public function testUpdateSingleDataThrowsException()
    {
        $queryArray = ['query' => 'UPDATE users SET username = ? WHERE id = ?', 'bind' => 'si'];
        $dataArray = ['newusername', 1];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->will($this->throwException(new \PDOException()));

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);

        $this->expectException(DatabaseException::class);
        $this->dataUpdateModel->updateSingleData($queryArray, $dataArray);
    }

    public function testUpdateMultipleData()
    {
        $queryArray = ['query' => 'UPDATE users SET username = ? WHERE id = ?', 'bind' => 'si'];
        $dataArray = [['newusername1', 1], ['newusername2', 2]];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->exactly(2))->method('execute')->willReturn(true);

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $result = $this->dataUpdateModel->updateMultipleData($queryArray, $dataArray);

        $this->assertTrue($result);
    }

    public function testUpdateMultipleDataThrowsException()
    {
        $queryArray = ['query' => 'UPDATE users SET username = ? WHERE id = ?', 'bind' => 'si'];
        $dataArray = [['newusername1', 1], ['newusername2', 2]];

        $stmtMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmtMock->expects($this->once())->method('execute')->will($this->throwException(new \PDOException()));

        $this->pdoMock->expects($this->once())->method('prepare')->with($queryArray['query'])->willReturn($stmtMock);
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('inTransaction');

        $this->expectException(DatabaseException::class);
        $this->dataUpdateModel->updateMultipleData($queryArray, $dataArray);
    }
}
