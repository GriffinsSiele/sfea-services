<?php

/**
 * @global TokenStorageInterface $tokenStorage
 */
declare(strict_types=1);

use App\Repository\SiteRepository;
use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

//
// dd($tokenStorage->getToken()?->getUser());
//
// function get_sites(ContainerInterface $container): array
// {
//    /** @var Connection $connection */
//    $connection = $container->get('doctrine.dbal.default_connection');
//
//    /** @var array<string, array<string, mixed>> $sites */
//    $sites = [];
//
//    if ($result = $connection->executeQuery('SELECT * FROM Site')) {
//        while ($row = $result->fetchAssociative()) {
//            $sites[$row['host']] = $row;
//        }
//    }
//
//    return $sites;
// }
//
// function get_user_id(ContainerInterface $container): false|int
// {
//    /** @var Connection $connection */
//    $connection = $container->get('doctrine.dbal.default_connection');
//
//    $sites = get_sites($container);
//    $userid = false;
//
//    if (!isset($user->getUserIdentifier(), $user->getPassword())) {
//        return false;
//    }
//    $stmt = $connection
//        ->prepare(
//            <<<'SQL'
// SELECT *
// FROM SystemUsers
// WHERE Login = :login
//    AND (
//        Password = :password
//        OR Password = :hashedPasseord
//    )
//    AND (
//        Locked IS NULL
//        OR Locked = 0
//        OR Locked >= 2
//    )
//    AND (
//        AllowedIP IS NULL
//        OR LOCATE(:remoteAddr, AllowedIP) > 0
//    )
//    LIMIT 1
// SQL,
//        );
//
//    $stmt->bindValue('login', $user->getUserIdentifier());
//    $stmt->bindValue('password', $user->getPassword());
//    $stmt->bindValue('hashedPasseord', \md5($user->getPassword()));
//    $stmt->bindValue('remoteAddr', $_SERVER['REMOTE_ADDR']);
//
//    if (!$result = $stmt->executeQuery()) {
//        return false;
//    }
//
//    while ($row = $result->fetchAssociative()) {
//        $userid = $row['Id'];
//        $siteId = $row['SiteId'];
//        $accessLevel = $row['AccessLevel'];
//    }
//
//    if (!$userid) {
//        return false;
//    }
//
//    @\session_start();
//
//    return $_SESSION['userid'] = $userid;
// }
//
// function get_client_id(ContainerInterface $container): int
// {
//    /** @var Connection $connection */
//    $connection = $container->get('doctrine.dbal.default_connection');
//
//    $userid = get_user_id($container);
//
//    $stmt = $connection->prepare(
//        <<<'SQL'
// SELECT ClientId
// FROM SystemUsers
// WHERE ClientId IS NOT NULL
//  AND id = :id
// SQL,
//    );
//    $stmt->bindValue('id', $userid);
//
//    return (int) $stmt->executeQuery()->fetchOne();
// }
//
// function get_user_level(ContainerInterface $container): int
// {
//    /** @var Connection $connection */
//    $connection = $container->get('doctrine.dbal.default_connection');
//
//    $userid = get_user_id($container);
//    if (!$userid) {
//        return 0;
//    }
//
//    $stmt = $connection->prepare('SELECT AccessLevel FROM SystemUsers WHERE id = :id');
//    $stmt->bindValue('id', $userid);
//
//    return (int) $stmt->executeQuery()->fetchOne();
// }
//
// function get_user_area(ContainerInterface $container, $field = 'AccessArea'): int|string
// {
//    $userid = get_user_id($container);
//
//    /** @var Connection $connection */
//    $connection = $container->get('doctrine.dbal.default_connection');
//    $stmt = $connection->prepare(
//        <<<'SQL'
// SELECT IFNULL(:field, AccessArea) Area
// FROM SystemUsers WHERE id = :id
// SQL,
//    );
//    $stmt->bindValue('field', $field);
//    $stmt->bindValue('id', $userid);
//    $result = $stmt->executeQuery();
//    $accessarea = 0;
//    while ($row = $result->fetchAssociative()) {
//        $accessarea = $row['Area'];
//    }
//
//    return $accessarea;
// }
//
// function get_user_access(TokenStorageInterface $tokenStorage): array
// {
//    dd($tokenStorage);
//
//    /** @var Connection $connection */
//    $connection = $container->get('doctrine.dbal.default_connection');
//
//    $userid = get_user_id($container);
//    if (!$userid) {
//        return [];
//    }
//
//    $stmt = $connection->prepare(
//        <<<'SQL'
// SELECT a.*
// FROM Access a, SystemUsers u
// WHERE a.Level = u.AccessLevel
//  AND u.id = :id
// SQL,
//    );
//    $stmt->bindValue('id', $userid);
//
//    return $stmt->executeQuery()->fetchAssociative();
// }
//
//
// function get_user_sources(ContainerInterface $container): array
// {
//    /** @var Connection $connection */
//    $connection = $container->get('doctrine.dbal.default_connection');
//
//    $userid = get_user_id($container);
//    if (!$userid) {
//        return [];
//    }
//
//    $stmt = $connection->prepare(
//        <<<'SQL'
// SELECT a.source_name
// FROM AccessSource a, SystemUsers u
// WHERE a.allowed = 1
//  AND a.Level = u.AccessLevel
//  AND u.id = :id
// SQL,
//    );
//    $stmt->bindValue('id', $userid);
//
//    $result = $stmt->executeQuery();
//    $sources = [];
//    while ($row = $result->fetchAssociative()) {
//        $sources[$row['source_name']] = true;
//    }
//
//    return $sources;
// }
//
// function get_user_rules(ContainerInterface $container): array
// {
//    /** @var Connection $connection */
//    $connection = $container->get('doctrine.dbal.default_connection');
//
//    $userid = get_user_id($container);
//    if (!$userid) {
//        return [];
//    }
//
//    $stmt = $connection->prepare(
//        <<<'SQL'
// SELECT a.rule_name
// FROM AccessRule a, SystemUsers u
// WHERE a.allowed = 1
//  AND a.Level = u.AccessLevel
//  AND u.id = :id
// SQL,
//    );
//    $stmt->bindValue('id', $userid);
//
//    $result = $stmt->executeQuery();
//    $rules = [];
//    while ($row = $result->fetchAssociative()) {
//        $rules[$row['role_name']] = true;
//    }
//
//    return $rules;
// }
//
// function get_user_message(ContainerInterface $container)
// {
//    /** @var Connection $connection */
//    $connection = $container->get('doctrine.dbal.default_connection');
//
//    $clientid = get_client_id($container);
//    $userid = get_user_id($container);
//
//    if ($clientid) {
//        $stmt = $connection->prepare(
//            <<<'SQL'
// SELECT m.Text
// FROM Message m, Client c
// WHERE m.id = c.MessageId
//  AND c.id = :id
// SQL,
//        );
//        $stmt->bindValue('id', $clientid);
//    } elseif ($userid) {
//        $stmt = $connection->prepare(
//            <<<'SQL'
// SELECT m.Text
// FROM Message m, SystemUsers u
// WHERE m.id = u.MessageId
//  AND u.id = :id
// SQL,
//        );
//        $stmt->bindValue('id', $userid);
//    } else {
//        return '';
//    }
//
//    return $stmt->executeQuery()->fetchOne() ?: '';
// }
//
// function auth_basic(): void
// {
//    global $servicenames;
//    \header('WWW-Authenticate: Basic realm=""');
//    \header('HTTP/1.1 401 Unauthorized');
//    echo 'Для доступа в панель управления введите действующий логин и пароль.';
//    if (isset($servicenames[$_SERVER['HTTP_HOST']])) {
//        echo '<br/>Если у вас нет логина и пароля, отправьте заявку c сайта '.$servicenames[$_SERVER['HTTP_HOST']];
//    }
//    $user->getUserIdentifier() = '';
//    $user->getPassword() = '';
//    if (isset($_SESSION['PHP_AUTH_USER'])) {
//        unset($_SESSION['PHP_AUTH_USER']);
//    }
//    @\session_destroy();
//    return;
// }
//
// $defaultConnection = $container->get('doctrine.dbal.default_connection');
// $defaultConnection->executeStatement('Set character set utf8');
// $defaultConnection->executeStatement("Set names 'utf8'");
//
// /*
// $mysqls = mysqli_connect ($dbstat['server'],$dbstat['login'],$dbstat['password'], $dbstat['name']);
// if ($mysqls) {
//    mysqli_query($mysqls, "Set character set utf8");
//    mysqli_query($mysqls, "Set names 'utf8'");
// }
// */
// /** @var SiteRepository $siteRepository */
// $siteRepository = $container->get(SiteRepository::class);
//
// $sites = get_sites($container);
// $userid = get_user_id($container);
//
// if (!$userid) {
//    auth_basic();
// }
// //    mysqli_close($db);
//
// $user = $tokenStorage->getToken()?->getUser();
// $userid = $user?->getUserIdentifier();
