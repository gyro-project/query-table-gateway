<?php

declare(strict_types=1);

namespace Dyke\TableGateway;

use Generator;

interface Gateway
{
    public function findOneBySql(string $className, string $sql, array $parameters = [], array $types = []) : ?object;

    public function findBySql(string $className, string $sql, array $parameters = [], array $types = []) : Generator;
}
