<?php
require dirname(__DIR__).'/vendor/autoload.php';

use carry0987\Sanite\Sanite;
use carry0987\Sanite\Example\UserModel;

$config = array(
    'host' => 'mariadb',
    'port' => 3306,
    'database' => 'dev_sanite',
    'username' => 'test_user',
    'password' => 'test1234',
    'charset' => 'utf8mb4',
);

try {
    $sanite = new Sanite($config);
    $userModel = new UserModel($sanite);
    $user = $userModel->getUserById(1);
    echo '<pre>';
    print_r($user);
    echo '</pre>';
} catch (\carry0987\Sanite\Exceptions\DatabaseException $e) {
    echo "Database error: " . $e->getMessage();
}
