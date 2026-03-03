<?php

namespace Cds\NetteModelGenerator\TypeMappers;

use Cds\NetteModelGenerator\Reflections\MySqlReflection;
use DateTime;

/**
 * @extends TypeMapper<MySqlReflection>
 */
readonly class MySqlTypeMapper extends TypeMapper
{
    public const array BaseTypes = [
        'integer' => 'int',
        'int' => 'int',
        'smallint' => 'int',
        'tinyint' => 'int',
        'mediumint' => 'int',
        'bigint' => 'int',
        'decimal' => 'int',
        'numeric' => 'float',
        'dec' => 'float',
        'fixed' => 'float',
        'float' => 'float',
        'real' => 'float',
        'double' => 'float',
        'double precision' => 'float',
        'char' => 'string',
        'varchar' => 'string',
        'binary' => 'string',
        'varbinary' => 'string',
        'tinyblob' => 'string',
        'blob' => 'string',
        'mediumblob' => 'string',
        'longblob' => 'string',
        'tinytext' => 'string',
        'text' => 'string',
        'mediumtext' => 'string',
        'longtext' => 'string',
        'enum' => 'string',
        'set' => 'string',
        'boolean' => 'bool',
        'bool' => 'bool',
        'date' => '\\' . DateTime::class,
        'datetime' => '\\' . DateTime::class,
        'timestamp' => '\\' . DateTime::class,
        'time' => '\\' . DateTime::class,
        'year' => '\\' . DateTime::class,
        'json' => 'string',
        'bit' => 'string',
    ];

    protected function customConversion(string $type, int|null $size): string|null
    {
        if ($type === 'tinyint' && $size === 1) {
            return 'bool';
        }

        return null;
    }
}
