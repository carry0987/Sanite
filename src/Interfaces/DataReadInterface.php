<?php
namespace carry0987\Sanite\Interfaces;

interface DataReadInterface
{
    public function getSingleData(array $queryArray, array $dataArray);
    public function getMultipleData(array $queryArray, array $dataArray);
    public function getDataCount(array $queryArray, array $dataArray);
}
