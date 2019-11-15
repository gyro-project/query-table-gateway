<?php

namespace Delta\TableGateway\Mapper;

use ReflectionClass;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class Mapper
{
    private string $className;
    private ReflectionClass $reflectionClass;
    private AbstractPlatform $platform;
    private array $reflectionProperties = [];
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

    public function __construct(string $className, AbstractPlatform $platform)
    {
        $this->className = $className;
        $this->reflectionClass = new ReflectionClass($className);
        $this->platform = $platform;
    }

    public function mapRowToObject(array $row) : object
    {
        $object = $this->reflectionClass->newInstanceWithoutConstructor();

        foreach ($row as $columnName => $value) {
            if (!isset($this->columnsToProperties[$columnName])) {
                $this->columnsToProperties[$columnName] = $this->mapColumnToProperty($columnName);
            }

            $mappedProperty = $this->columnsToProperties[$columnName];

            $value = $mappedProperty->mappedType->convertToPHPValue($value, $this->platform);
            $mappedProperty->reflection->setValue($object, $value);
        }

        return $object;
    }

    private function mapColumnToProperty(string $columnName) : MappedProperty
    {
        $propertyName = Inflector::camelize($columnName);

        $reflection = $this->reflectionClass->getProperty($propertyName);

        if (!$reflection->isPublic()) {
            $reflection->setAccessible(true);
        }

        if (!$reflection->hasType()) {
            throw new \RuntimeException("{$this->className}::{$propertyName} is missing a type or class declaration.");
        }

        $type = $reflection->getType()->getName();

        if (!isset(self::$simpleTypeMap[$type])) {
            throw new \RuntimeException(sprintf(
                'When mapping row to %s::%s could not map "%s" to simple type.',
                $this->className,
                $propertyName,
                $type
            ));
        }

        $mapType = Type::getType(self::$simpleTypeMap[$type]);

        return new MappedProperty($propertyName, $mapType, $reflection);
    }
}
