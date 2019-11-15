<?php

declare(strict_types=1);

namespace Dyke\TableGateway\Mapper;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    public function testMapRowToObjectSimple() : void
    {
        $platform = new MySqlPlatform();
        $mapper   = new Mapper(Simple::class, $platform);
        $object   = $mapper->mapRowToObject([
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
