<?php
namespace carry0987\Sanite\Models;

use carry0987\Sanite\Sanite;
use carry0987\Sanite\Exceptions\DatabaseException;
use carry0987\Sanite\Interfaces\DataDeleteInterface;
use carry0987\Sanite\Utils\DBUtil;

abstract class DataDeleteModel implements DataDeleteInterface
{
    protected $connectdb;

    public function __construct(Sanite $sanite)
    {
        $this->connectdb = $sanite->getConnection();
    }

    /**
     *  Delete single row of data
     *  @param array $queryArray
     *  @param array $dataArray
     *  
     *  @return bool
     */
    public function deleteSingleData(array $queryArray, array $dataArray)
    {
        $result = false;
        try {
            $stmt = $this->connectdb->prepare($queryArray['query']);
            $params = $dataArray['param'];
            $formats = $queryArray['bind'];
            $types = DBUtil::getPDOType($formats, $params);
            foreach ($params as $key => &$val) {
                $stmt->bindValue($key+1, $val, $types[$key]);
            }
            $result = $stmt->execute();
        } catch(\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     *  Use transaction to delete multiple data
     *  @param array $queryArray
     *  @param array $dataArray
     *  
     *  @return bool
     */
    public function deleteMultipleData(array $queryArray, array $dataArray)
    {
        $result = false;
        try {
            $this->connectdb->beginTransaction();
            $stmt = $this->connectdb->prepare($queryArray['query']);
            $list = $dataArray['list'];
            $formats = $queryArray['bind'];
            foreach ($list as $value) {
                $types = DBUtil::getPDOType($formats, $value);
                foreach ($value as $subKey => $subValue) {
                    $stmt->bindValue($subKey+1, $value[$subKey], $types[$subKey]);
                }
                $stmt->execute();
            }
            $result = $this->connectdb->commit();
        } catch(\PDOException $e) {
            $this->connectdb->rollBack();
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $result;
    }
}
