<?php

/** @global $database array */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

use App\GraphQL\Type\Query;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;

// legacy style globals

$mysql = new PDO(
    sprintf(
        'mysql:host=%s;dbname=%s',
        $database['server'],
        $database['name']
    ),
    $database['login'],
    $database['password']
);

$mysql->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

// init

$queryType = new Query();

$schema = new Schema([
    'query' => $queryType,
]);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$query = $input['query'] ?? null;
$variableValues = $input['variables'] ?? null;

if (isset($_SERVER['HTTP_ORIGIN'])) {
    if (strpos($_SERVER['HTTP_ORIGIN'], 'localhost') !== false
        || strpos($_SERVER['HTTP_ORIGIN'], '127.0.0.1') !== false
        || strpos($_SERVER['HTTP_ORIGIN'], '0.0.0.0') !== false
        || strpos($_SERVER['HTTP_ORIGIN'], 'svc.cluster.local') !== false
        || strpos($_SERVER['HTTP_ORIGIN'], '172.16.97.10') !== false
    ) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: OPTIONS, GET, POST');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }
    }
}

try {
    $rootValue = [];
    $result = GraphQL::executeQuery($schema, $query, $rootValue, null, $variableValues);
    $output = $result->toArray();
} catch (\Throwable $e) {
    $output = [
        'errors' => [
            [
                'message' => $e->getMessage(),
            ],
        ],
    ];
}

header('Content-Type: application/json');
echo json_encode($output);
