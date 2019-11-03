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

    private static $simpleTypeMap = [
        'int' => 'integer',
        'bool' => 'boolean',
        'string' => 'string',
        'array' => 'simple_array',
        'float' => 'numeric',
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
                $this->columnsToProperties[$columnName] = Inflector::camelize($columnName);
            }

            $propertyName = $this->columnsToProperties[$columnName];

            if (!isset($this->reflectionProperties[$propertyName])) {
                $this->reflectionProperties[$propertyName] = $this->reflectionClass->getProperty($propertyName);

                if (!$this->reflectionProperties[$propertyName]->isPublic()) {
                    $this->reflectionProperties[$propertyName]->setAccessible(true);
                }
            }

            $property = $this->reflectionProperties[$propertyName];

            if ($property->hasType()) {
                $type = $property->getType()->getName();
                $mapType = Type::getType(self::$simpleTypeMap[$type]);
                $value = $mapType->convertToPHPValue($value, $this->platform);
            } else {
                throw new \RuntimeException("{$this->className}::{$propertyName} is missing a type or class declaration.");
            }

            $property->setValue($object, $value);
        }

        return $object;
    }
}
