# Table/Query Gateway with Doctrine DBAL

This is a very simple Table/Query Gateway library on top of [Doctrine
DBAL](https://github.com/doctrine/dbal) (with some ORM mapping support). It
requires PHP 7.4's property types for automatic mapping between SQL queries and
objects.

The idea is to assemble SQL Query, information about the columns/types from
the queries metadata and a fully typed target class into an easy to use
object relational mapper.

Currently this library only implements read functionality, that means
the direction from database to objects.

## Installation

Via Composer:

    composer require gyro/query-table-gateway

Setup in code:

```php
use Doctrine\DBAL\DriverManager;
use Gyro\TableGateway\DBAL\DBALGateway;

$connection = DriverManager::getConnection($config);
$gateway = new DBALGateway($connection);
```

See API below the examples.

## Example

In this query for a list of users, its organizations are joined into the result
as a group contact string, which is then mapped to an array by `explode(',', $tring)`.

```php
<?php

use Gyro\TableGateway\Gateway;

class UserListItem
{
    public int $id;
    public string $name;
    public array $organizations = [];
}

class UserListItemQuery
{
    private Gateway $gateway;

    public function __construct(Gateway $gateway)
    {
        $this->gateway = $gateway;
    }

    public function findLastSeenUsers()
    {
        $sql = 'SELECT u.id, u.name, GROUP_CONTACT(o.name) as organizations
                  FROM user u
            INNER JOIN organization_members om ON om.member_id = u.id
            INNER JOIN organization o ON om.organization_id = o.id
              ORDER BY u.last_seen DESC LIMIT 10';

        return $this->gateway->findBySql(UserListItem::class, $sql);
    }
}

$userListItemQuery = new UserListItemQuery($gateway);
$users = $userListItemQuery->findLastSeenUsers();
```

## Operations

The Gateway class provides the following two methods:

### Fetch a Single Object by SQL

```php
interface Gateway
{
    public function findOneBySql(string $className, string $sql, array $parameters = [], array $types = []) : ?object;
}

$sql = 'SELECT * FROM user WHERE email = ?';
$user = $gateway->findOneBySql(UserView::class, $sql, [$email]);
```

### Find a List of Objects by SQL

```php
interface Gateway
{
    public function findBySql(string $className, string $sql, array $parameters = [], array $types = []) : array;
}

$categories = $gateway->findBySql(CategoryItem::class, 'SELECT * FROM category');
```

## Automatic Type Mapping

While mapping database rows to objects, this gateway looks at the property types and uses Doctrine DBAL Type system and the actual SQL column types for conversion:

| PHP Object Type   | Doctrine Type   | SQL Type       |
| ----------------- | --------------- | -------------- |
| `int`             | `integer`       | integer types  |
| `string`          | `string`        | varchar types  |
| `DateTime`        | `datetime`      | datetime types |
| `array`           | `simple_array`  | varchar types  |
| `array`           | `json_array`    | json types     |
| `bool`            | `boolean`       | tinyint/bool   |

When fetching the gateway looks at the combination of SQL Type and PHP object
type of a field and decides which Doctrine type to use.

## Comparison with Doctrine ORM

Compared to Doctrine ORM the following features are missing.

- No Proxy objects and collections to allow transparent traversal of object
  graph with lazy loading.
- No UnitOfWork with changeset detection when updating entities.
- No IdentityMap that detects when you already fetched a row before, returning
  the same entity.
- No DQL and not many query facilities: You have to write most SQL by hand.
- No Flush Operation that automatically stores all available entities in a
  single transaction. You must handle transaction management, foreign key
  order, and individual update/insert operation in code.
- Many more...

As such I actually recommend to use both together, depening on the use-case.
CRUD and entity-centric business logic with Doctrine. Read layer and high
performance write throughput with the gateway.

### Why combine this with Doctrine?

I regularly see Doctrine ORM used outside its best capabilities, especially
when it comes to read-only or view-centric applications. There we often
see the following steps:

1. Execute complex DQL
2. Convert to Doctrine Entities
3. Serialize for API/Templates inluding much N+1 fetching

In this case none of Doctrine's powerful features are actually used:

- UnitOfWork + Identity Map are useless in read only scenario
- Business Logic on Entities will not be used, only getters
- Result may be converted into a non Doctrine entity representation manually

And the following bad things are still done:

- Complex DQL execution and hydration is very performance intensive.
- Columns and data from the database is overfetched to include *everything* the
  entity contains, even when the view might only need a subset of this data.
- Much N+1 is happening and inefficent, because the normalized entity graph is usually not good for querying

Another downside of Doctrine are its performance downsides in high throughput
write scenarios, where you only update a single column or a few of individual
rows, but you do it many hundred thousand of times every minute. Doctrine goes
through the whole entity hydration, changeset computation algorithm over and
over again. With this Gateway pattern you can write specialized tiny objects
used for individual UPDATE statements.
