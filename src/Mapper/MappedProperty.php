<?php

namespace Delta\TableGateway\Mapper;

use Doctrine\DBAL\Types\Type;
use ReflectionProperty;

class MappedProperty
{
    public $propertyName;
    public $reflection;
    public $mappedType;

    public function __construct(string $propertyName, Type $mappedType, ReflectionProperty $reflection)
    {
        $this->propertyName = $propertyName;
        $this->mappedType = $mappedType;
        $this->reflection = $reflection;
    }
}
