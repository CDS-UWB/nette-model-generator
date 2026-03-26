<?php

declare(strict_types=1);

namespace Cds\NetteModelGenerator\TypeMappers;

use Cds\NetteModelGenerator\Reflections\PostgreSqlReflection;
use DateInterval;
use DateTime;

/**
 * @extends TypeMapper<PostgreSqlReflection>
 */
readonly class PostgreSqlTypeMapper extends TypeMapper
{
    public const BaseTypes = [
        'void' => 'void',
        'smallint' => 'int',
        'integer' => 'int',
        'bigint' => 'int',
        'int4' => 'int',
        'int8' => 'int',
        'decimal' => 'int',
        'numeric' => 'int',
        'float' => 'float',
        'float4' => 'float',
        'float8' => 'float',
        'real' => 'float',
        'double precision' => 'float',
        'string' => 'string',
        'text' => 'string',
        'varchar' => 'string',
        'bytea' => 'string',
        'boolean' => 'bool',
        'bool' => 'bool',
        'date' => '\\' . DateTime::class,
        'datetime' => '\\' . DateTime::class,
        'datetimetz' => '\\' . DateTime::class,
        'time' => '\\' . DateTime::class,
        'time without time zone' => '\\' . DateTime::class,
        'timestamp' => '\\' . DateTime::class,
        'timestamp without time zone' => '\\' . DateTime::class,
        'json' => 'string',
        'jsonb' => 'string',
        'character varying' => 'string',
        'character' => 'string',
        'char' => 'string',
        'interval' => '\\' . DateInterval::class,
        'inet' => 'string',
        'tsrange' => 'string',
        'daterange' => 'string',
        'uuid' => 'string',
        'bit' => 'string',
        'name' => 'string',
        'anyelement' => 'mixed',
        'record' => 'array',
        // Object identifier types
        'oid' => 'int',
        'regclass' => 'string',
        'regcollation' => 'string',
        'regconfig' => 'string',
        'regdictionary' => 'string',
        'regnamespace' => 'string',
        'regoper' => 'string',
        'regoperator' => 'string',
        'regproc' => 'string',
        'regprocedure' => 'string',
        'regrole' => 'string',
        'regtype' => 'string',
    ];

    protected function customConversion(string $type, int|null $size): string|null
    {
        if (($result = $this->reflection->translateDomain($type)) !== null) {
            return $this->toPhp($result);
        }

        if ($this->reflection->isEnum($type)) {
            return 'string';
        }

        return null;
    }
}
