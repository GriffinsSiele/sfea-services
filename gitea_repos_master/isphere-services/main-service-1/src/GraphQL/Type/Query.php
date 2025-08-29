<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;

class Query extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Query',
            'fields' => [
                'authorizedUser' => (new AuthorizedUser())->toArray(),
                'checkTypes' => (new CheckTypes())->toArray(),
                'proxies' => (new Proxies())->toArray(),
                'userAccesses' => (new UserAccesses())->toArray(),
            ],
        ]);
    }
}
