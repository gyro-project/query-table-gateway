<?php

namespace Delta\TableGateway\Mapper;

use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Platforms\MySqlPlatform;

class MapperTest extends TestCase
{
    public function testMapRowToObjectSimple()
    {
        $platform = new MySqlPlatform();
        $mapper = new Mapper(Simple::class, $platform);
        $object = $mapper->mapRowToObject([
            'number' => '1234',
            'text' => 'foobar',
            'flag' => '1',
        ]);

        $this->assertSame(1234, $object->number);
        $this->assertSame('foobar', $object->text);
        $this->assertTrue($object->flag);
    }
}

class Simple
{
    public int $number;
    public string $text;
    public bool $flag;
}
