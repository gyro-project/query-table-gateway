<?php

declare(strict_types=1);

namespace Dyke\TableGateway;

use Generator;

interface Gateway
{
    /**
     * @param mixed[] $parameters
     * @param mixed[] $types
     */
    public function findOneBySql(string $className, string $sql, array $parameters = [], array $types = []) : ?object;

    /**
     * @param mixed[] $parameters
     * @param mixed[] $types
     */
    public function findBySql(string $className, string $sql, array $parameters = [], array $types = []) : Generator;
}
