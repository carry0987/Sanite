<?php
require dirname(__DIR__).'/vendor/autoload.php';

use carry0987\Sanite\Sanite;
use carry0987\Sanite\Example\UserModel;

$db_host = 'mariadb';
$db_name = 'dev_sanite';
$db_user = 'test_user';
$db_password = 'test1234';
$db_charset = 'utf8mb4';
$db_port = 3306;

try {
    $sanite = new Sanite($db_host, $db_name, $db_user, $db_password, $db_charset, $db_port);
    $userModel = new UserModel($sanite);
    $user = $userModel->getUserById(1);
    echo '<pre>';
    print_r($user);
    echo '</pre>';
} catch (\carry0987\Sanite\Exceptions\DatabaseException $e) {
    echo "Database error: " . $e->getMessage();
}
