<?php
namespace carry0987\Sanite\Models;

use carry0987\Sanite\Sanite;
use carry0987\Sanite\Exceptions\DatabaseException;
use carry0987\Sanite\Interfaces\DataReadInterface;
use carry0987\Sanite\Utils\DBUtil;

abstract class DataReadModel implements DataReadInterface
{
    protected $connectdb;

    public function __construct(Sanite $sanite)
    {
        $this->connectdb = $sanite->getConnection();
    }

    /**
     *  Get single row of data
     *  @param array $queryArray
     *  @param array|null $dataArray
     *  
     *  @return array
    */
    public function getSingleData(array $queryArray, array|null $dataArray = null)
    {
        $result = false;
        try {
            $stmt = $this->connectdb->prepare($queryArray['query']);
            if (isset($dataArray['param'])) {
                $params = $dataArray['param'];
                $formats = $queryArray['bind'];
                $types = DBUtil::getPDOType($formats, $params);
                foreach ($params as $index => $param) {
                    $stmt->bindValue($index + 1, $params[$index], $types[$index]);
                }
            }
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     *  Get multiple row of data
     *  @param array $queryArray
     *  @param array|null $dataArray
     *  
     *  @return array
    */
    public function getMultipleData(array $queryArray, array|null $dataArray = null)
    {
        $result = array();
        try {
            $stmt = $this->connectdb->prepare($queryArray['query']);
            if (isset($dataArray['param'])) {
                $types = DBUtil::getPDOType($queryArray['bind'], $dataArray['param']);
                if (!is_array($dataArray['param'])) $dataArray['param'] = array($dataArray['param']);
                foreach ($dataArray['param'] as $index => $param) {
                    $stmt->bindValue($index+1, $param, $types[$index]);
                }
            }
            $stmt->execute();
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return !empty($result) ? $result : false;
    }

    /**
     *  Get total row of data
     *  @param array $queryArray
     *  @param array|null $dataArray
     *
     *  @return int
    */
    public function getDataCount(array $queryArray, array|null $dataArray = null)
    {
        $result = false;
        try {
            $stmt = $this->connectdb->prepare($queryArray['query']);
            if (isset($dataArray['param'])) {
                $types = DBUtil::getPDOType($queryArray['bind'], $dataArray['param']);
                foreach ($dataArray['param'] as $index => &$param) {
                    $stmt->bindValue($index+1, $param, $types[$index]);
                }
            }
            $stmt->execute();
            $result = $stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }

        return $result;
    }
}
