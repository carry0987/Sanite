<?php
namespace carry0987\Sanite\Example;

use carry0987\Sanite\Models\DataReadModel;

class UserModel extends DataReadModel
{
    public function getUserById(int $userId)
    {
        $queryArray = [
            'query' => 'SELECT * FROM user WHERE uid = ? LIMIT 1',
            'bind'  => 'i',  // This value needs to be relative when using DBUtil::getPDOType
        ];
        $dataArray = [$userId];

        return $this->getSingleData($queryArray, $dataArray);
    }
}