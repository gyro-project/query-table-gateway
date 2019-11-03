<?php

namespace Delta\TableGateway;

use Generator;

interface Gateway
{
    public function findOneBySql(string $className, string $sql, array $parameters = [], array $types = []) : ?object;
    public function findBySql(string $className, string $sql, array $parameters = [], array $types = []) : Generator;
}
