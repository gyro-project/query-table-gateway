<?php

declare(strict_types=1);

namespace Dyke\TableGateway\DBAL;

use Doctrine\DBAL\Connection;
use Dyke\TableGateway\Gateway;
use Dyke\TableGateway\Mapper\Mapper;
use Generator;

class DBALGateway implements Gateway
{
    private Connection $connection;

    private array $mappers = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findOneBySql(string $className, string $sql, array $parameters = [], array $types = []) : ?object
    {
        $rows = $this->connection->fetchAll($sql, $parameters, $types);

        if (count($rows) === 0) {
            return null;
        }

        $mapper = $this->getMapper($className);

        return $mapper->mapRowToObject($rows[0]);
    }

    public function findBySql(string $className, string $sql, array $parameters = [], array $types = []) : Generator
    {
        $statement = $this->connection->executeQuery($sql, $parameters, $types);
        $mapper    = $this->getMapper($className);

        while ($row = $statement->fetch()) {
            yield $mapper->mapRowToObject($row);
        }
    }

    private function getMapper(string $className) : Mapper
    {
        if (! isset($this->mappers[$className])) {
            $this->mappers[$className] = new Mapper($className, $this->connection->getDatabasePlatform());
        }

        return $this->mappers[$className];
    }
}
