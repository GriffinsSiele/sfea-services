<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class CheckTypes
{
    public function toArray(): array
    {
        return [
            'type' => Type::listOf(new ObjectType([
                'name' => 'CheckType',
                'fields' => [
                    'code' => [
                        'type' => Type::nonNull(Type::id()),
                    ],
                ],
            ])),
            'resolve' => [$this, 'resolve'],
        ];
    }

    public function resolve($rootValue, array $args): iterable
    {
        /** @var $mysqli \mysqli */
        global $mysqli;

        /** @var array<string, bool> $userSources */
        $userSources = get_user_sources($mysqli);

        $stmt = mysqli_prepare(
            $mysqli,
            // language=mysql
            'select code
from CheckType
where status > 0
'
        );
        $stmt->execute();
        $stmt->bind_result($code);

        while ($stmt->fetch()) {
            if (!$userSources[$code]) {
                continue;
            }

            yield [
                'code' => $code,
            ];
        }

        $stmt->close();
    }
}
