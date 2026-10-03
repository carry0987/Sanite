# Sanite
[![Packgist](https://img.shields.io/packagist/v/carry0987/sanite.svg?style=flat-square)](https://packagist.org/packages/carry0987/sanite) 
![CI](https://github.com/carry0987/Sanite/actions/workflows/php-unit.yml/badge.svg)  
Sanite is a PHP library that provide base CRUD structure and methods, using PDO.

## Getting Started
Sanite requires PHP 8.0 or later and PDO. Enable the PDO extension for the database you use:

- MySQL or MariaDB: `pdo_mysql`
- PostgreSQL: `pdo_pgsql`

Install Sanite with Composer:

```bash
composer require carry0987/sanite
```

After installation, you can include `Sanite` in your project and start using it.

## Establishing a Database Connection

Use `Sanite` to establish a database connection:

```php
use carry0987\Sanite\Sanite;

// MySQL or MariaDB connection settings
$config = array(
    'driver' => 'mysql', // Optional; mysql is the default
    'host' => 'mariadb',
    'database' => 'dev_sanite',
    'username' => 'test_user',
    'password' => 'test1234',
    'port' => 3306, // Optional
    'charset' => 'utf8mb4' // Optional
);

// Create a database connection
$sanite = new Sanite($config);
```

Only the `mysql` and `pgsql` driver values are accepted. MariaDB uses the `mysql` driver.

For PostgreSQL, set the driver to `pgsql`. The default port is `5432` and the default client encoding is `utf8`:

```php
$config = [
    'driver' => 'pgsql',
    'host' => 'postgres',
    'database' => 'dev_sanite',
    'username' => 'test_user',
    'password' => 'test1234',
    // 'port' => 5432,
    // 'charset' => 'utf8',
];

$sanite = new Sanite($config);
```

PostgreSQL applies `client_encoding` to the current connection session. A new connection receives its own session setting.

## Using a Data Model

Create your own data models to perform CRUD operations. Here's an example of using `UserModel` to retrieve user data.

First, ensure your model extends `DataReadModel` (or corresponding `DataCreateModel`, `DataDeleteModel`, `DataUpdateModel`):

```php
namespace carry0987\Sanite\Example;

use carry0987\Sanite\Models\DataReadModel;

class UserModel extends DataReadModel
{
    // Implement your methods, for example:
    public function getUserById(int $userId)
    {
        $queryArray = [
            'query' => 'SELECT * FROM user WHERE uid = ? LIMIT 1',
            'bind'  => 'i',  // This value needs to be relative when using DBUtil::getPDOType
        ];
        $dataArray = [$userId];

        return $this->getSingleData($queryArray, $dataArray);
    }

    public function getAllUsers()
    {
        $queryArray = [
            'query' => 'SELECT * FROM user'
        ];

        return $this->getMultipleData($queryArray);
    }
}
```

Then, you can use your model like so:

```php
use carry0987\Sanite\Example\UserModel;

// Instantiate UserModel
$userModel = new UserModel($sanite);

// Retrieve user information for user with ID 1
$user = $userModel->getUserById(1);
$users = $userModel->getAllUsers();

print_r($user);
print_r($users);
```

## Insert IDs and PostgreSQL RETURNING

`createSingleData()` only calls PDO's `lastInsertId()` when its third argument is `true`. PostgreSQL sequence lookup belongs to the current connection session; pass the sequence name explicitly when an ID is required:

```php
$result = $userModel->createSingleData(
    [
        'query' => 'INSERT INTO users (username) VALUES (?)',
        'bind' => 's',
    ],
    ['alice'],
    true,
    'users_id_seq'
);

$userId = $result['auto_increment'];
```

For PostgreSQL `RETURNING`, use `createSingleReturning()`. Sanite does not add or translate SQL clauses; the caller chooses the fields to return:

```php
$user = $userModel->createSingleReturning(
    [
        'query' => 'INSERT INTO users (username) VALUES (?) RETURNING id, username',
        'bind' => 's',
    ],
    ['alice']
);
```

## Exception Handling

`Sanite` defines a specific exception class `DatabaseException`. Capture and handle it appropriately in your code:

```php
try {
    // ... attempt some database operations ...
} catch (\carry0987\Sanite\Exceptions\DatabaseException $e) {
    // Includes the PDO driver error details, such as PostgreSQL SQLSTATE 23505.
    $errorInfo = $e->getErrorInfo();
    echo "Error: " . $e->getMessage();
}
```
