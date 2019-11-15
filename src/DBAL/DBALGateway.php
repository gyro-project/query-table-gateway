<?php

declare(strict_types=1);

namespace Dyke\TableGateway\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
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

    /**
     * @param mixed[] $parameters
     * @param mixed[] $types
     */
    public function findOneBySql(string $className, string $sql, array $parameters = [], array $types = []) : ?object
    {
        $statement  = $this->connection->executeQuery($sql, $parameters, $types);
        $columnMeta = $this->getColumnMeta($statement);
        $rows       = $statement->fetchAll();

        if (count($rows) === 0) {
            return null;
        }

        $mapper = $this->getMapper($className);

        return $mapper->mapRowToObject($rows[0], $columnMeta);
    }

    /**
     * @param mixed[] $parameters
     * @param mixed[] $types
     */
    public function findBySql(string $className, string $sql, array $parameters = [], array $types = []) : Generator
    {
        $statement  = $this->connection->executeQuery($sql, $parameters, $types);
        $mapper     = $this->getMapper($className);
        $columnMeta = $this->getColumnMeta($statement);

        while ($row = $statement->fetch()) {
            yield $mapper->mapRowToObject($row, $columnMeta);
        }
    }

    /**
     * @return array<string,string>
     */
    private function getColumnMeta(ResultStatement $statement) : array
    {
        $columnMeta = [];

        for ($i = 0; $i < $statement->columnCount(); $i++) {
            $column                      = $statement->getColumnMeta($i);
            $columnMeta[$column['name']] = strtolower($column['sqlite:decl_type'] ?? $column['native_type']);
        }

        return $columnMeta;
    }

    private function getMapper(string $className) : Mapper
    {
        if (! isset($this->mappers[$className])) {
            $this->mappers[$className] = new Mapper($className, $this->connection->getDatabasePlatform());
        }

        return $this->mappers[$className];
    }
}
