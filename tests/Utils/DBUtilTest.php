<?php
namespace carry0987\Sanite\Tests\Utils;

use carry0987\Sanite\Utils\DBUtil;
use carry0987\Sanite\Exceptions\UtilsException;
use PDO;
use PHPUnit\Framework\TestCase;

class DBUtilTest extends TestCase
{
    public function testGetPDOTypeSingleValue()
    {
        $format = 's';
        $value = 'test';

        $types = DBUtil::getPDOType($format, $value);

        $this->assertSame([PDO::PARAM_STR], $types);
    }

    public function testGetPDOTypeMultipleValues()
    {
        $format = 'si';
        $values = ['test', 123];

        $types = DBUtil::getPDOType($format, $values);

        $this->assertSame([PDO::PARAM_STR, PDO::PARAM_INT], $types);
    }

    public function testGetPDOTypeArrayMismatchThrowsException()
    {
        $this->expectException(UtilsException::class);

        $format = 's';
        $values = ['test', 123];

        DBUtil::getPDOType($format, $values);
    }

    public function testGetParamTypeString()
    {
        $format = 's';
        $value = 'test';

        $type = DBUtil::getParamType($format, $value);

        $this->assertSame(PDO::PARAM_STR, $type);
    }

    public function testGetParamTypeInteger()
    {
        $format = 'i';
        $value = 123;

        $type = DBUtil::getParamType($format, $value);

        $this->assertSame(PDO::PARAM_INT, $type);
    }

    public function testGetParamTypeUnsupportedThrowsException()
    {
        $this->expectException(UtilsException::class);

        $format = 'u';
        $value = 'test';

        DBUtil::getParamType($format, $value);
    }
}
