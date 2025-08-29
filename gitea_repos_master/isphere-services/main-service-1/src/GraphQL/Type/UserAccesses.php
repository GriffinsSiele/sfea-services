<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\Util\StringUtil;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class UserAccesses
{
    private $filenames = [];

    public function __construct()
    {
        $filenames = [];

        foreach (scandir(__DIR__ . '/../../../') as $filename) {
            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            $filenames[] = $filename;
        }

        $this->filenames = $filenames;
    }

    public function toArray(): array
    {
        return [
            'type' => Type::listOf(new ObjectType([
                'name' => 'UserAccess',
                'fields' => [
                    'code' => [
                        'type' => Type::nonNull(Type::id()),
                    ],
                    'path' => [
                        'type' => Type::nonNull(Type::string()),
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

        foreach (get_user_access($mysqli) as $code => $_) {
            if (!StringUtil::isFirstLevelLowercase($code)) {
                continue;
            }

            $filename = $this->generateUniqueIdentifiesFileWithCode($code);
            if ($filename === false) {
                continue;
            }

            yield [
                'code' => $code,
                'path' => '/' . $filename,
            ];
        }
    }

    private function generateUniqueIdentifiesFileWithCode(string $code)
    {
        foreach ($this->filenames as $filename) {
            if (pathinfo($filename, PATHINFO_FILENAME) === $code) {
                return $filename;
            }
        }

        return false;
    }
}
