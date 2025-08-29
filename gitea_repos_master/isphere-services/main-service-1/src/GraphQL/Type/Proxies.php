<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\Security\Exception\ForbiddenException;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use NilPortugues\Sql\QueryBuilder\Builder\MySqlBuilder;

class Proxies
{
    public function toArray(): array
    {
        return [
            'type' => Type::listOf(new ObjectType([
                'name' => 'Proxy',
                'fields' => [
                    'id' => [
                        'type' => Type::id(),
                    ],
                    'server' => [
                        'type' => Type::string(),
                    ],
                    'port' => [
                        'type' => Type::int(),
                    ],
                    'login' => [
                        'type' => Type::string(),
                    ],
                    'password' => [
                        'type' => Type::string(),
                    ],
                    'country' => [
                        'type' => Type::string(),
                    ],
                    'proxygroup' => [
                        'type' => Type::id(),
                    ],
                    'status' => [
                        'type' => Type::int(),
                    ],
                    'starttime' => [
                        'type' => Type::string(),
                    ],
                    'lasttime' => [
                        'type' => Type::string(),
                    ],
                    'successtime' => [
                        'type' => Type::string(),
                    ],
                    'endtime' => [
                        'type' => Type::string(),
                    ],
                    'name' => [
                        'type' => Type::string(),
                    ],
                    'enabled' => [
                        'type' => Type::boolean(),
                    ],
                ],
            ])),
            'args' => [
                'id' => [
                    'type' => Type::id(),
                ],
                'country' => [
                    'type' => Type::string(),
                ],
                'proxygroup' => [
                    'type' => Type::id(),
                ],
                'enabled' => [
                    'type' => Type::boolean(),
                ],
                'status' => [
                    'type' => Type::int(),
                ],
                'orderby' => [
                    'type' => Type::string(),
                ],
                'order' => [
                    'type' => Type::string(),
                ],
                'limit' => [
                    'type' => Type::int(),
                ],
                'offset' => [
                    'type' => Type::int(),
                ],
            ],
            'resolve' => [$this, 'resolve'],
        ];
    }

    public function resolve($rootValue, array $args): array
    {
        /** @var $mysqli \mysqli */
        global $mysqli;

        if (!get_user_access($mysqli)['stats'] ?? false) {
            throw new ForbiddenException();
        }

        /** @var $mysql \PDO */
        global $mysql;

        $queryBuilder = new MySqlBuilder();
        $query = $queryBuilder->select()->setTable('proxy');
        $query->distinct();
        $query->setFunctionAsColumn('ifnull', ['comment', 'server'], 'name');
        $query->setColumns([
            'id' => 'id',
            'server' => 'server',
            'port' => 'port',
            'login' => 'login',
            'password' => 'password',
            'country' => 'country',
            'proxygroup' => 'proxygroup',
            'status' => 'status',
            'starttime' => 'starttime',
            'lasttime' => 'lasttime',
            'successtime' => 'successtime',
            'endtime' => 'endtime',
            'enabled' => 'enabled',
        ]);

        $query->where()->greaterThan('id', 0);

        if (isset($args['id'])) {
            $query->where()->eq('id', $args['id']);
        }

        if (isset($args['country'])) {
            $query->where()->eq('country', $args['country']);
        }

        if (isset($args['proxygroup'])) {
            $query->where()->eq('proxygroup', $args['proxygroup']);
        }

        if (isset($args['enabled'])) {
            $query->where()->eq('enabled', (int)$args['enabled']);
        }

        if (isset($args['status'])) {
            $query->where()->eq('status', $args['status']);
        }

        if (isset($args['orderby'])) {
            $query->orderBy($args['orderby'], strtoupper($args['order'] ?? 'asc'));
        }

        if (isset($args['limit'])) {
            $query->limit($args['offset'] ?? 0, $args['limit']);
        }

        $sql = $queryBuilder->writeFormatted($query);
        $values = $queryBuilder->getValues();

        $stmt = $mysql->prepare($sql);
        $stmt->execute($values);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
