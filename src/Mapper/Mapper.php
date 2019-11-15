<?php

declare(strict_types=1);

namespace Dyke\TableGateway\Mapper;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use ReflectionClass;
use RuntimeException;

class Mapper
{
    private string $className;
    private ReflectionClass $reflectionClass;
    private AbstractPlatform $platform;
    private array $columnsToProperties = [];

    private static array $simpleTypeMap = [
        'int' => 'integer',
        'bool' => 'boolean',
        'string' => 'string',
        'array' => 'simple_array',
        'float' => 'float',
        'resource' => 'blob',
        'DateTime' => 'datetime',
        'DateTimeImmutable' => 'datetime_immutable',
    ];

    private static array $databaseTypeMap = ['json' => 'json_array'];

    public function __construct(string $className, AbstractPlatform $platform)
    {
        $this->className = $className;
        /** @psalm-suppress ArgumentTypeCoercion */
        $this->reflectionClass = new ReflectionClass($className);
        $this->platform        = $platform;
    }

    /**
     * @param array<string,string> $row
     * @param array<string,string> $columnMeta
     */
    public function mapRowToObject(array $row, array $columnMeta = []) : object
    {
        $object = $this->reflectionClass->newInstanceWithoutConstructor();

        foreach ($row as $columnName => $value) {
            if (! isset($this->columnsToProperties[$columnName])) {
                $this->columnsToProperties[$columnName] = $this->mapColumnToProperty($columnName, $columnMeta[$columnName] ?? '');
            }

            $mappedProperty = $this->columnsToProperties[$columnName];

            $value = $mappedProperty->mappedType->convertToPHPValue($value, $this->platform);
            $mappedProperty->reflection->setValue($object, $value);
        }

        return $object;
    }

    /**
     * @param array<string,string> $columnMeta
     */
    private function mapColumnToProperty(string $columnName, string $columnType) : MappedProperty
    {
        $propertyName = Inflector::camelize($columnName);

        $reflection = $this->reflectionClass->getProperty($propertyName);

        if (! $reflection->isPublic()) {
            $reflection->setAccessible(true);
        }

        /** @psalm-suppress UndefinedMethod */
        if (! $reflection->hasType()) {
            throw new RuntimeException(sprintf('%s::%s is missing a type or class declaration.', $this->className, $propertyName));
        }

        /** @psalm-suppress UndefinedMethod */
        $type = $reflection->getType()->getName();

        if (isset(self::$databaseTypeMap[$columnType])) {
            $mapType = Type::getType(self::$databaseTypeMap[$columnType]);

            return new MappedProperty($propertyName, $mapType, $reflection);
        }

        if (isset(self::$simpleTypeMap[$type])) {
            $mapType = Type::getType(self::$simpleTypeMap[$type]);

            return new MappedProperty($propertyName, $mapType, $reflection);
        }

        throw new RuntimeException(sprintf(
            'When mapping row to %s::%s could not map "%s" to simple type.',
            $this->className,
            $propertyName,
            $type
        ));
    }
}
