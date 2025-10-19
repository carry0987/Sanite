<?php
namespace carry0987\Sanite\Utils;

use carry0987\Sanite\Exceptions\UtilsException;
use PDO;

class DBUtil
{
    public static function getPDOType(string $format_str, mixed &$values)
    {
        if (!is_array($values)) {
            if (strlen($format_str) !== 1) throw new UtilsException('Quantity Mismatch');
            $type = self::getParamType($format_str, $values);
            $values = array($values);
            return array($type);
        }
        $formats = str_split($format_str);
        if (count($formats) != count($values)) {
            throw new UtilsException('Quantity Mismatch for formats and values count');
        }
        $types = array();
        $values = array_values($values);
        foreach ($values as $index => $value) {
            $format = $formats[$index];
            $type = self::getParamType($format, $value);
            array_push($types, $type);
        }

        return $types;
    }

    public static function getParamType(string $param, mixed $value): int
    {
        switch ($param) {
            case 's': // string
                return is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR;
            case 'i': // integer
                return is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_INT;
            case 'b': // boolean
                return is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_BOOL;
            case 'j': // Pass string; json_encode first in application layer
                return is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR;
            case 'n': // explicit null
                return PDO::PARAM_NULL;
            default:
                throw new UtilsException('Unsupported format type');
        }
    }
}
