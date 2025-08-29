<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class AuthorizedUser
{
    public function toArray(): array
    {
        return [
            'type' => Type::nonNull(new ObjectType([
                'name' => 'AuthorizedUser',
                'fields' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                    ],
                    'username' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => [$this, 'resolveUsername'],
                    ],
                ],
            ])),
            'resolve' => [$this, 'resolve'],
        ];
    }

    public function resolve($rootValue, array $args): array
    {
        /** @var $mysqli \mysqli */
        global $mysqli;

        $id = get_user_id($mysqli);
        if (empty($id)) {
            throw new \RuntimeException('Unauthorized');
        }

        return ['id' => $id];
    }

    public function resolveUsername($rootValue, array $args): string
    {
        /** @var $mysqli \mysqli */
        global $mysqli;

        $stmt = mysqli_prepare(
            $mysqli,
            // language=mysql
            'select Login
from SystemUsers
where Id = ?
'
        );
        $stmt->bind_param('i', $rootValue['id']);
        $stmt->execute();
        $stmt->bind_result($username);
        $stmt->fetch();
        $stmt->close();

        return $username;
    }
}
