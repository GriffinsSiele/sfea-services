<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;

function logResponse(array $params, $results, $status)
{
    /** @var Connection $connection */
    $connection = $params['_connection'];
    $reqId = $params['_reqId'];
    $xmlpath = \rtrim($params['_xmlpath'], '/');
    $userId = $params['_userId'];
    $clientId = $params['_clientId'];
    $restime = &$params['_restime'];

    $restime = \date("Y-m-d\TH:i:s");

    if ($params['async'] || $status) {
        $response = generateResponse($params, $results, $status);
        if ($status) {
            $connection->executeStatement(
                "UPDATE RequestNew set status=$status" . ($status ? ",processed_at='$restime'" : '') . " WHERE id=$reqId"
            );
            $connection->executeStatement(
                "INSERT INTO RequestSource SELECT $reqId,min(created_at),min(created_date),$userId,$clientId,source_id,source_name,start_param,SUM(1),SUM(res_code<400),SUM(res_code=200),SUM(res_code>=400),MAX(process_time) FROM ResponseNew WHERE request_id=$reqId and res_code>0 GROUP BY source_id,source_name,start_param"
            );
        }

        $sid = \str_pad($reqId, 9, '0', \STR_PAD_LEFT);
        $dir = $xmlpath . '/' . \substr($sid, 0, 3) . '/' . \substr($sid, 3, 3);

        if (!\is_dir($dir) && !\mkdir($dir, 0777, true) && !\is_dir($dir)) {
            throw new \RuntimeException(\sprintf('Directory "%s" was not created', $dir));
        }

        \file_put_contents($dir . '/' . \substr($sid, 6, 3) . '_res.xml', $response);

        if ($status && (2783 == $userId || 2840 == $userId || 2899 == $userId)) {
            $url = 'https://infosphere' . (2899 == $userId ? '' : '-test') . '.sberleasing.ru:18093/sendInfoSphereDataResult';
            $user = 'esb';
            $password = 'xYlkFYl3ZG0decgv';
            $ch = \curl_init();
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_USERPWD, $user . ':' . $password);
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $response);
            \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 2);
            \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);
            $content = \curl_exec($ch);
            if (\curl_errno($ch)) {
                \file_put_contents('logs/callback_' . $reqId . '_' . \time() . '.err', \curl_error($ch));
            } else {
                \file_put_contents('logs/callback_' . $reqId . '_' . \time() . '.xml', $content);
            }
            \curl_close($ch);
        }

        return $response;
    }
}

function logSourceResult(array $params, $result): void
{
    /** @var Connection $connection */
    $connection = $params['_connection'];
    $userId = $params['_userId'];
    $clientId = $params['_clientId'];
    $reqId = $params['_reqId'];
    $reqdate = $params['_reqdate'];
    $reqtime = $params['_reqtime'];

    if ('' == $result->getSourceName()) {
        return;
    }

    $id = $result->getInitData();
    $xml = '';
    $error = 'NULL';
    $rCount = 0;
    $rCode = 0;
    $rData = '';
    $data = $result->getResultData();

    if ($result->getError()) {
        $error = "'" . $result->getError() . "'";
        $rCode = 500;
    } elseif ($result->getResultData() instanceof ResultDataList) {
        $rData = $result->getResultData();
        $rCount = $rData->getResultsCount();
        $rCode = $rCount > 0 ? 200 : 204;
        $status = 1;

        foreach ($rData->getResults() as $record) {
            compileField($xml, $record);
        }
    } else {
        return;
    }

    $connection->executeStatement(
        "INSERT INTO ResponseNew (request_id, user_id, client_id, source_name, checktype, start_param, check_index, check_level, created_at, created_date, processed_at, process_time, result_count, res_code) VALUES
               ($reqId,$userId,$clientId,'" . $result->getSourceName() . "','" . $result->getCheckType() . "','" . $result->getStart() . "'," . $result->getLevel() . ',' . \substr_count(
            $result->getPath(),
            '/'
        ) . ',FROM_UNIXTIME(' . $result->startTime() . "),'$reqdate',FROM_UNIXTIME(" . $result->endTime() . '),' . $result->processTime() . ',' . $rCount . ',' . $rCode . ')'
    );
    $resId = $connection->lastInsertId();

    if ($result->getError()) {
        $connection->executeStatement(
            "INSERT INTO ResponseError (response_id, text) VALUES ($resId,'" . $result->getError() . "')"
        );
    } elseif ($result->getResultData() instanceof ResultDataList) {
        $rData = $result->getResultData();
        foreach ($rData->getResults() as $record) {
            if (\count($record)) {
                //                       $mysqli->query("INSERT INTO Record (response_id) VALUES ($resId)");
                //                       $recId = $mysqli->insert_id;
                foreach ($record as $field) {
                    $query = $connection->executeQuery(
                        "SELECT id FROM Field WHERE source_name='" . $result->getPlugin()->getName() . "' AND name='" . $field->getName() . "'"
                    )->fetchAllAssociative();
                    if ($query) {
                        if ($row = ($query[0] ?? null)) {
                            $fieldId = $row['id'];
                        } else {
                            $connection->executeStatement(
                                "INSERT INTO Field (source_name, name, type, title, description) VALUES
                                       ('" . $result->getPlugin()->getName() . "','" . $field->getName() . "','" . $field->getType() . "','" . $field->getTitle() . "','" . $field->getDesc() . "')"
                            );
                            $fieldId = $connection->lastInsertId();
                        }
                        /*
                                                       $value = $mysqli->query("INSERT INTO Value (record_id, field_id, value) VALUES ($recId,$fieldId,'".preg_replace('/(?:\\\\u[\pL\p{Zs}])+/','',strval($field->getValue()))."')");
                                                       if ($value) {
                                                       } else {
                        // Ошибка при сохранении значения поля
                        //                                   print "INSERT INTO Value (record_id, field_id, value) VALUES ($recId,$fieldId,'".preg_replace('/(?:\\\\u[\pL\p{Zs}])+/','',strval($field->getValue()))."')\n";
                                                       }
                        */
                    } else {
                        // Ошибка при поиске поля
                    }
                }
            }
        }
    }
}

function logRequest($params, $request)
{
    /** @var Connection $connection */
    $connection = $params['_connection'];
    $userId = $params['_userId'];
    $clientId = $params['_clientId'];
    $xmlpath = \rtrim($params['_xmlpath'], '/');
    $reqdate = $params['_reqdate'];
    $reqtime = $params['_reqtime'];

    if ($clientId === null) {
        $clientId = 'NULL';
    }

    $sql = "INSERT INTO RequestNew (created_at, created_date, ip, user_id, client_id, external_id, channel, type, `recursive`, status) VALUES ('$reqtime','$reqdate','" . $params['user_ip'] . "',$userId,$clientId," . ($params['request_id'] ? "'" . $params['request_id'] . "'" : 'NULL') . ',' . (0 === \strpos(
            $params['request_type'],
            'check'
        ) ? '1' : '0') . ',' . ($params['request_type'] ? "'" . $params['request_type'] . "'" : 'NULL') . ',' . ($params['recursive'] ? '1' : '0') . ',0)';
//    echo $sql,PHP_EOL;

    $connection->executeStatement($sql);
    $id = $connection->lastInsertId();

    $sid = \str_pad($id, 9, '0', \STR_PAD_LEFT);
    $dir = $xmlpath . '/' . \substr($sid, 0, 3) . '/' . \substr($sid, 3, 3);

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

//    if (!\is_dir($dir) && !\mkdir($dir, 0777, true) && !\is_dir($dir)) {
//        throw new \RuntimeException(\sprintf('Directory "%s" was not created', $dir));
//    }

    \file_put_contents($dir . '/' . \substr($sid, 6, 3) . '_req.xml', $request);

    return $id;
}

function parseParams($xml, $total_timeout)
{
    $params = [
        'user_ip' => $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '127.0.0.1',
        'request_id' => false,
        'request_type' => false,
        'sources' => [],
        'rules' => [],
        'recursive' => 0,
        'async' => 0,
        'timeout' => $total_timeout,
        'person' => [],
        'phone' => [],
        'email' => [],
        'nick' => [],
        'url' => [],
        'car' => [],
        'ip' => [],
        'org' => [],
        'card' => [],
        'fssp_ip' => [],
        'osago' => [],
        'text' => [],
    ];

    if (\preg_match('/<UserIP>([^<]+)/', $xml, $res)) {
        $params['user_ip'] = \trim($res[1]);
    }

    if (\preg_match('/<requestId>([^<]+)/', $xml, $res)) {
        $params['request_id'] = \trim($res[1]);
    }

    if (\preg_match('/<requestType>([^<]+)/', $xml, $res)) {
        $params['request_type'] = \trim($res[1]);
    }

    if (\preg_match('/<sources>([^<]+)/', $xml, $res)) {
        $params['sources'] = \explode(',', $res[1]);
        foreach ($params['sources'] as $i => $source) {
            $params['sources'][$i] = \strtr(\strtolower($source), [' ' => '', "\u{a0}" => '']);
        }
    }

    if (\preg_match('/<rules>([^<]+)/', $xml, $res)) {
        $params['rules'] = \explode(',', $res[1]);
        foreach ($params['rules'] as $i => $rule) {
            $params['rules'][$i] = \strtolower(\trim($rule));
        }
    }

    if (\preg_match('/<recursive>([^<]+)/', $xml, $res) && (int)$res[1]) {
        $params['recursive'] = (int)$res[1];
    }
    if (\preg_match('/<async>([^<]+)/', $xml, $res) && (int)$res[1]) {
        $params['async'] = (int)$res[1];
    }
    if ((\preg_match('/<timeout>([^<]+)/', $xml, $res) || \preg_match(
                '/<Timeout>([^<]+)/',
                $xml,
                $res
            )) && (int)$res[1] && (int)$res[1] <= 600) {
        $params['timeout'] = (int)$res[1];
    }

    if (\preg_match('/<PersonReq/', $xml)) {
        if (\preg_match('/<paternal>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['person']['last_name'] = \trim(
                \strtr(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8'), ["\u{200b}" => ''])
            );
        }

        if (\preg_match('/<first>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['person']['first_name'] = \trim(
                \strtr(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8'), ["\u{200b}" => ''])
            );
        }

        if (\preg_match('/<middle>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $m = \trim(\strtr(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8'), ["\u{200b}" => '']));
            if ('-' != $m && 'НЕТ' != \mb_strtoupper($m)) {
                $params['person']['patronymic'] = $m;
            }
        }

        if (\preg_match('/<birthDt>([^<]+)/', $xml, $res) && \strtotime(\trim($res[1]))) {
            $params['person']['date'] = \date('d.m.Y', \strtotime(\trim($res[1])));
        }

        if (\preg_match('/<placeOfBirth>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['person']['placeOfBirth'] = \trim(
                \strtr(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8'), ["\u{200b}" => ''])
            );
        }

        if (\preg_match('/<passport_series>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['person']['passport_series'] = \sprintf('%04d', \strtr($res[1], ["\u{200b}" => '']));
        }

        if (\preg_match('/<passport_number>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['person']['passport_number'] = \sprintf('%06d', \strtr($res[1], ["\u{200b}" => '']));
        }

        if (\preg_match('/<issueDate>([^<]+)/', $xml, $res) && \strtotime(\trim($res[1]))) {
            $params['person']['issueDate'] = \date('d.m.Y', \strtotime(\trim($res[1])));
        }

        if (\preg_match('/<issueAuthority>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['person']['issueAuthority'] = \trim(
                \strtr(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8'), ["\u{200b}" => ''])
            );
        }

        if (\preg_match('/<driver_number>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['person']['driver_number'] = \trim(
                \strtr(
                    \mb_strtoupper(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8')),
                    [
                        ' ' => '',
                        "\u{200b}" => '',
                        '№' => '',
                        'N' => '',
                        'A' => 'А',
                        'B' => 'В',
                        'C' => 'С',
                        'E' => 'Е',
                        'H' => 'Н',
                        'K' => 'К',
                        'M' => 'М',
                        'O' => 'О',
                        'P' => 'Р',
                        'T' => 'Т',
                        'Y' => 'У',
                        'X' => 'Х',
                        'a' => 'а',
                        'c' => 'с',
                        'e' => 'е',
                        'k' => 'к',
                        'm' => 'м',
                        'o' => 'о',
                        'p' => 'р',
                        't' => 'т',
                        'y' => 'у',
                        'x' => 'х',
                    ]
                )
            );
        }

        if (\preg_match('/<driver_date>([^<]+)/', $xml, $res) && \strtotime(\trim($res[1]))) {
            $params['person']['driver_date'] = \date('d.m.Y', \strtotime(\trim($res[1])));
        }

        if (\preg_match('/<inn>([^<]+)/', $xml, $res) && ($inn = normal_inn(\trim($res[1])))) {
            $params['person']['inn'] = $inn;
        }

        if (\preg_match('/<snils>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['person']['snils'] = \trim($res[1]);
        }

        if (\preg_match('/<region_id>([^<]+)/', $xml, $res)) {
            $params['person']['region_id'] = \trim($res[1]);
        }

        if (\preg_match('/<homeaddress>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['person']['homeaddress'] = \trim(
                \strtr(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8'), ["\u{200b}" => ''])
            );
        }

        if (\preg_match('/<homeaddressArr>([^<]+)/', $xml, $res)) {
            $params['person']['homeaddressArr'] = \trim(res[1]);
        }

        if (\preg_match('/<regaddress>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['person']['regaddress'] = \trim(
                \strtr(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8'), ["\u{200b}" => ''])
            );
        }

        if (\preg_match('/<regaddressArr>([^<]+)/', $xml, $res)) {
            $params['person']['regaddressArr'] = \trim($res[1]);
        }

        if (\preg_match('/<bik>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['person']['bik'] = \trim($res[1]);
        }
    }

    if (\preg_match('/<PhoneReq/', $xml)) {
        if (\preg_match_all('/<phone>([^<]+)/', $xml, $res)) {
            foreach ($res[1] as $elem) {
                $list = \preg_split('/[,;]+/', \trim(\html_entity_decode($elem)));
                foreach ($list as $phone) {
                    if (($phone = normal_phone($phone)) && !\in_array($phone, $params['phone'])) {
                        $params['phone'][] = $phone;
                    }
                }
            }
        }
    }

    if (\preg_match('/<EmailReq/', $xml)) {
        if (\preg_match_all('/<email>([^<]+)/', $xml, $res)) {
            foreach ($res[1] as $elem) {
                $list = \preg_split("/[\s,;]+/", \trim(\html_entity_decode($elem)));
                foreach ($list as $email) {
                    if (($email = normal_email($email)) && !\in_array($email, $params['email'])) {
                        $params['email'][] = $email;
                    }
                }
            }
        }
    }

    if (\preg_match('/<SkypeReq/', $xml)) {
        if (\preg_match_all('/<skype>([^<]+)/', $xml, $res)) {
            foreach ($res[1] as $elem) {
                $list = \preg_split("/[\s,;]+/", \trim(\html_entity_decode($elem)));
                foreach ($list as $skype) {
                    if (\trim($skype)) {
                        $params['nick'][] = \trim($skype);
                    }
                }
            }
        }
    }

    if (\preg_match('/<URLReq/', $xml)) {
        if (\preg_match_all('/<url>([^<]+)/', $xml, $res)) {
            foreach ($res[1] as $elem) {
                $list = \preg_split("/[\s,;]+/", \trim(\html_entity_decode($elem)));
                foreach ($list as $url) {
                    if (\trim($url)) {
                        $params['url'][] = \trim($url);
                    }
                }
            }
        }
    }

    if (\preg_match('/<CarReq/', $xml)) {
        if (\preg_match('/<vin>([^<]+)/', $xml, $res) && ($vin = \trim(
                \strtr(
                    \mb_strtoupper(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8')),
                    [
                        'ОТСУТСТВУЕТ' => '',
                        ' ' => '',
                        "\u{200b}" => '',
                        'I' => '1',
                        'O' => '0',
                        'Q' => '0',
                        'А' => 'A',
                        'В' => 'B',
                        'С' => 'C',
                        'Е' => 'E',
                        'Н' => 'H',
                        'К' => 'K',
                        'М' => 'M',
                        'О' => '0',
                        'Р' => 'P',
                        'Т' => 'T',
                        'У' => 'Y',
                        'Х' => 'X',
                        'а' => 'a',
                        'с' => 'c',
                        'е' => 'e',
                        'к' => 'k',
                        'м' => 'm',
                        'о' => '0',
                        'р' => 'p',
                        'т' => 't',
                        'у' => 'y',
                        'х' => 'x',
                    ]
                )
            ))) {
            $params['car']['vin'] = $vin;
        }

        if (\preg_match('/<bodynum>([^<]+)/', $xml, $res) && ($bodynum = \trim(
                \strtr(
                    \mb_strtoupper(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8')),
                    [
                        'ОТСУТСТВУЕТ' => '',
                        ' ' => '',
                        "\u{200b}" => '',
                        'А' => 'A',
                        'В' => 'B',
                        'С' => 'C',
                        'Е' => 'E',
                        'Н' => 'H',
                        'К' => 'K',
                        'М' => 'M',
                        'О' => 'O',
                        'Р' => 'P',
                        'Т' => 'T',
                        'У' => 'Y',
                        'Х' => 'X',
                        'а' => 'a',
                        'с' => 'c',
                        'е' => 'e',
                        'к' => 'k',
                        'м' => 'm',
                        'о' => 'o',
                        'р' => 'p',
                        'т' => 't',
                        'у' => 'y',
                        'х' => 'x',
                    ]
                )
            ))) {
            $params['car']['bodynum'] = $bodynum;
        }

        if (\preg_match('/<regnum>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['car']['regnum'] = \trim(
                \strtr(
                    \mb_strtoupper(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8')),
                    [
                        ' ' => '',
                        "\u{200b}" => '',
                        'A' => 'А',
                        'B' => 'В',
                        'C' => 'С',
                        'E' => 'Е',
                        'H' => 'Н',
                        'K' => 'К',
                        'M' => 'М',
                        'O' => 'О',
                        'P' => 'Р',
                        'T' => 'Т',
                        'Y' => 'У',
                        'X' => 'Х',
                        'a' => 'а',
                        'c' => 'с',
                        'e' => 'е',
                        'k' => 'к',
                        'm' => 'м',
                        'o' => 'о',
                        'p' => 'р',
                        't' => 'т',
                        'y' => 'у',
                        'x' => 'х',
                    ]
                )
            );
        }

        if (\preg_match('/<ctc>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['car']['ctc'] = \trim(
                \strtr(
                    \mb_strtoupper(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8')),
                    [
                        ' ' => '',
                        "\u{200b}" => '',
                        '№' => '',
                        'N' => '',
                        'A' => 'А',
                        'B' => 'В',
                        'C' => 'С',
                        'E' => 'Е',
                        'H' => 'Н',
                        'K' => 'К',
                        'M' => 'М',
                        'O' => 'О',
                        'P' => 'Р',
                        'T' => 'Т',
                        'Y' => 'У',
                        'X' => 'Х',
                        'a' => 'а',
                        'c' => 'с',
                        'e' => 'е',
                        'k' => 'к',
                        'm' => 'м',
                        'o' => 'о',
                        'p' => 'р',
                        't' => 'т',
                        'y' => 'у',
                        'x' => 'х',
                    ]
                )
            );
        }

        if (\preg_match('/<pts>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['car']['pts'] = \trim(
                \strtr(
                    \mb_strtoupper(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8')),
                    [
                        ' ' => '',
                        "\u{200b}" => '',
                        '№' => '',
                        'N' => '',
                        'A' => 'А',
                        'B' => 'В',
                        'C' => 'С',
                        'E' => 'Е',
                        'H' => 'Н',
                        'K' => 'К',
                        'M' => 'М',
                        'O' => 'О',
                        'P' => 'Р',
                        'T' => 'Т',
                        'Y' => 'У',
                        'X' => 'Х',
                        'a' => 'а',
                        'c' => 'с',
                        'e' => 'е',
                        'k' => 'к',
                        'm' => 'м',
                        'o' => 'о',
                        'p' => 'р',
                        't' => 'т',
                        'y' => 'у',
                        'x' => 'х',
                    ]
                )
            );
        }

        if (\preg_match('/<reqdate>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['car']['reqdate'] = \trim($res[1]);
        }
    }

    if (\preg_match('/<IPReq/', $xml)) {
        if (\preg_match_all('/<ip>([^<]+)/', $xml, $res)) {
            foreach ($res[1] as $elem) {
                $list = \preg_split("/[\s,;]+/", \trim(\html_entity_decode($elem)));
                foreach ($list as $ip) { //                if ($ip = normal_ip($ip))
                    if (\trim($ip)) {
                        $params['ip'][] = \trim($ip);
                    }
                }
            }
        }
    }

    if (\preg_match('/<OrgReq/', $xml)) {
        if (\preg_match('/<inn>([^<]+)/', $xml, $res) && ($inn = normal_inn(\trim($res[1])))) {
            $params['org']['inn'] = $inn;
        }

        if (\preg_match('/<ogrn>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['org']['ogrn'] = \trim($res[1]);
        }

        if (\preg_match('/<name>([^<]+)/', $xml, $res) && ($name = \trim(
                \strtr(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8'), ["\u{200b}" => ''])
            ))) {
            $params['org']['name'] = $name;
        }

        if (\preg_match('/<address>([^<]+)/', $xml, $res) && ($address = \trim(
                \strtr(\html_entity_decode($res[1], \ENT_COMPAT, 'UTF-8'), ["\u{200b}" => ''])
            ))) {
            $params['org']['address'] = $address;
        }

        if (\preg_match('/<region_id>([^<]+)/', $xml, $res)) {
            $params['org']['region_id'] = \trim($res[1]);
        }

        if (\preg_match('/<bik>([^<]+)/', $xml, $res) && \trim($res[1])) {
            $params['org']['bik'] = \trim($res[1]);
        }
    }

    if (\preg_match('/<CardReq/', $xml)) {
        if (\preg_match_all('/<card>([^<]+)/', $xml, $res)) {
            foreach ($res[1] as $card) {
                if (/* ($card = normal_card($card)) && */ !\in_array($card, $params['card'])) {
                    $params['card'][] = \preg_replace("/\D/", '', \trim($card));
                }
            }
        }
    }

    if (\preg_match('/<TextReq/', $xml)) {
        if (\preg_match_all('/<text>([^<]+)/', $xml, $res)) {
            foreach ($res[1] as $text) {
                $params['text'][] = \trim($text);
            }
        }
    }

    if (\preg_match('/<OtherReq/', $xml)) {
        if (\preg_match_all('/<fsspip>([^<]+)/', $xml, $res)) {
            foreach ($res[1] as $fsspip) {
                $params['fssp_ip'][] = \trim($fsspip);
            }
        }

        if (\preg_match_all('/<osago>([^<]+)/', $xml, $res)) {
            foreach ($res[1] as $osago) {
                $params['osago'][] = \trim(
                    \strtr(
                        \mb_strtoupper(\html_entity_decode($osago, \ENT_COMPAT, 'UTF-8')),
                        [
                            ' ' => '',
                            "\u{200b}" => '',
                            'A' => 'А',
                            'B' => 'В',
                            'C' => 'С',
                            'E' => 'Е',
                            'H' => 'Н',
                            'K' => 'К',
                            'M' => 'М',
                            'O' => 'О',
                            'P' => 'Р',
                            'T' => 'Т',
                            'Y' => 'У',
                            'X' => 'Х',
                            'a' => 'а',
                            'c' => 'с',
                            'e' => 'е',
                            'k' => 'к',
                            'm' => 'м',
                            'o' => 'о',
                            'p' => 'р',
                            't' => 'т',
                            'y' => 'у',
                            'x' => 'х',
                        ]
                    )
                );
            }
        }
    }

    return $params;
}

function initPluginsUsingUser(\App\Entity\SystemUser $user)
{
    return initPlugins(
        [
            '_clientId' => $user->getClient()?->getId(),
        ],
        [],
    );
}

function initPlugins(array $params, $sources)
{
    $clientId = $params['_clientId'];

    /*
        $fms = new FMSPlugin();
        $fmsdb = new FMSDBPlugin();
        $mvdwanted = new MVDWantedPlugin();
        $gosuslugi = new GosuslugiPlugin();
        $fns = new FNSPlugin();
        $egrul = new EGRULPlugin();
        $gisgmp = new GISGMPPlugin();
        $fssp = new FSSPPlugin();
        $fsspapi = new FSSPAPIPlugin();
        $fsspapp = new FSSPAppPlugin();
        $vestnik = new VestnikPlugin();
        $gks = new GKSPlugin();
        $kad = new KADPlugin();
        $zakupki = new ZakupkiPlugin();
        $bankrot = new BankrotPlugin();
        $cbr = new CBRPlugin();
        $terrorist = new TerroristPlugin();
        $croinform = new CROINFORMPlugin();
        $nbki = new NBKIPlugin();
        $people = new YaPeoplePlugin();
        $vk = new VKPlugin();
        $ok = new OKPlugin();
        $mailru = new MailRuPlugin();

        $rossvyaz = new RossvyazPlugin();
        $smsc = new SMSCPlugin();
        $infobip = new InfobipPlugin();
    //    $infobip = new InfobipNewPlugin();
        $stream = new StreamPlugin();
        $facebook = new FacebookPlugin();
        $instagram = new InstagramPlugin();
        $twitter = new TwitterPlugin();
        $hh = new HHPlugin();
        $whatsapp = new WhatsAppPlugin();
        $whatsappweb = new WhatsAppWebPlugin();
    //    $checkwa = new CheckWAPlugin();
        $announcement = new AnnouncementPlugin();
        $boards = new BoardsPlugin();
        $yamap = new YaMapPlugin();
        $gis = new GISPlugin();
        $listorg = new ListOrgPlugin();
        $commerce = new CommercePlugin();
        $viber = new ViberPlugin();
        $viberwin = new ViberWinPlugin();
        $telegram = new TelegramPlugin();
        $truecaller = new TrueCallerPlugin();
        $truecallerweb = new TrueCallerWebPlugin();
        $tc = new TCPlugin();
        $emt = new EMTPlugin();
        $getcontact = new GetContactPlugin();
        $numbuster = new NumBusterPlugin();
        $names = new NamesPlugin();
        $phones = new PhonesPlugin();
        $vkcheck = new VKCheckPlugin();
        $sberbank = new SberbankPlugin();

    //    $tinkoff = new TinkoffPlugin();
    //    $banks = new BanksPlugin();
    //    $visa = new VISAPlugin();
    //    $sbert = new SberTPlugin();
    //    $alfabankt = new AlfabankTPlugin();
    //    $raiffeisent = new RaiffeisenTPlugin();
    //    $psbankt = new PSBankTPlugin();
    //    $rosbankt = new RosbankTPlugin();
    //    $raiffeisen = new RaiffeisenPlugin();
    //    $tinkoffr = new TinkoffRPlugin();
    //    $alfabankr = new AlfabankRPlugin();
    //    $psbankr = new PSBankRPlugin();
    //    $rosbankr = new RosbankRPlugin();
    //    $sovcombankr = new SovcombankRPlugin();
    //    $gazprombankr = new GazprombankRPlugin();
    //    $qiwibankr = new QiwibankRPlugin();

        $sberw = new SberWPlugin();
        $sbers = new SberSPlugin();
        $sbps = new SBPSPlugin();

    //    $tinkoffs = new TinkoffSPlugin();
    //    $alfabanks = new AlfabankSPlugin();
    //    $psbanks = new PSBankSPlugin();
    //    $raiffeisens = new RaiffeisenSPlugin();
    //    $sovcombank = new SovcombankPlugin();

        $phonenumber = new PhoneNumberPlugin();
        $avinfo = new AvInfoPlugin();
        $beholder = new BeholderPlugin();
        $skype = new SkypePlugin();
        $googleplus = new GooglePlusPlugin();
        $google = new GooglePlugin();
        $apple = new ApplePlugin();
        $qiwi = new QiwiPlugin();
        $yamoney = new YaMoneyPlugin();
        $elecsnet = new ElecsnetPlugin();
        $webmoney = new WebMoneyPlugin();
        $aeroflot = new AeroflotPlugin();
        $uralair = new UralAirPlugin();
        $rzd = new RZDPlugin();
        $papajohns = new PapaJohnsPlugin();
        $biglion = new BiglionPlugin();
        $avito = new AvitoPlugin();

        $gibdd = new GIBDDPlugin();
        $eaisto = new EAISTOPlugin();
        $rsa = new RSAPlugin();
        $kbm = new KBMPlugin();
        $rz = new RZPlugin();
        $reestrzalogov = new ReestrZalogovPlugin();
    //    $autoru = new AutoRuPlugin();
        $vin = new VINPlugin();
        $avtokod = new AvtoKodPlugin();
        $mosru = new MosRuPlugin();
        $mosrufines = new MosRuFinesPlugin();
        $nbkiauto = new NBKIAutoPlugin();
        $avinfo = new AvInfoPlugin();

        $dns = new DNSPlugin();
        $ripe = new RIPEPlugin();
        $ipgeo = new IPGeoBasePlugin();
        $sypexgeo = new SypexGeoPlugin();
        $shodan = new ShodanPlugin();
        $censys = new CensysPlugin();
    */
    $fms = 'FMSPlugin';
    $fmsdb = 'FMSDBPlugin';
    $mvdwanted = 'MVDWantedPlugin';
    $gosuslugi = 'GosuslugiPlugin';
    $fns = 'FNSPlugin';
    $egrul = 'EGRULPlugin';
    $gisgmp = 'GISGMPPlugin';
    $notariat = 'NotariatPlugin';
    $fssp = 'FSSPPlugin';
    $fsspapi = 'FSSPAPIPlugin';
    $fsspapp = 'FSSPAppPlugin';
    $vestnik = 'VestnikPlugin';
    $gks = 'GKSPlugin';
    $kad = 'KADPlugin';
    $zakupki = 'ZakupkiPlugin';
    $bankrot = 'BankrotPlugin';
    $cbr = 'CBRPlugin';
    $terrorist = 'TerroristPlugin';
    $croinform = 'CROINFORMPlugin';
    $nbki = 'NBKIPlugin';
    $people = 'YaPeoplePlugin';
    $vk = 'VKPlugin';
    $ok = 'OKPlugin';
    $okapp = 'OKAppPlugin';
    $okappr = 'OKAppRPlugin';
    $mailru = 'MailRuPlugin';

    $rossvyaz = 'RossvyazPlugin';
    $smsc = 'SMSCPlugin';
    $infobip = 'InfobipPlugin';
    //    $infobip = 'InfobipNewPlugin';
    $stream = 'StreamPlugin';
    $smspilot = 'SMSPilotPlugin';
    $hlr = 'HLRPlugin';
    $facebook = 'FacebookPlugin';
    $instagram = 'InstagramPlugin';
    $twitter = 'TwitterPlugin';
    $hh = 'HHPlugin';
    $whatsapp = 'WhatsAppPlugin';
    $whatsappweb = 'WhatsAppWebPlugin';
    //    $checkwa = 'CheckWAPlugin';
    $announcement = 'AnnouncementPlugin';
    $boards = 'BoardsPlugin';
    $yamap = 'YaMapPlugin';
    $gis = 'GISPlugin';
    $listorg = 'ListOrgPlugin';
    $commerce = 'CommercePlugin';
    $viber = 'ViberPlugin';
    $viberwin = 'ViberWinPlugin';
    $telegram = 'TelegramPlugin';
    $telegramweb = 'TelegramWebPlugin';
    $icq = 'ICQPlugin';
    $truecaller = 'TrueCallerPlugin';
    $truecallerweb = 'TrueCallerWebPlugin';
    $tc = 'TCPlugin';
    $emt = 'EMTPlugin';
    $getcontact = 'GetContactPlugin';
    $getcontactapp = 'GetContactAppPlugin';
    $callapp = 'CallAppPlugin';
    $simpler = 'SimplerPlugin';
    $numbuster = 'NumBusterPlugin';
    $numbusterapp = 'NumBusterAppPlugin';
    $names = 'NamesPlugin';
    $phones = 'PhonesPlugin';
    $vkcheck = 'VKCheckPlugin';
    $okbot = 'OKBotPlugin';
    $sberbank = 'SberbankPlugin';
    /*
        $tinkoff = 'TinkoffPlugin';
        $banks = 'BanksPlugin';
        $visa = 'VISAPlugin';
        $sbert = 'SberTPlugin';
        $alfabankt = 'AlfabankTPlugin';
        $raiffeisent = 'RaiffeisenTPlugin';
        $psbankt = 'PSBankTPlugin';
        $rosbankt = 'RosbankTPlugin';
        $raiffeisen = 'RaiffeisenPlugin';
        $tinkoffr = 'TinkoffRPlugin';
        $alfabankr = 'AlfabankRPlugin';
        $psbankr = 'PSBankRPlugin';
        $rosbankr = 'RosbankRPlugin';
        $sovcombankr = 'SovcombankRPlugin';
        $gazprombankr = 'GazprombankRPlugin';
        $qiwibankr = 'QiwibankRPlugin';
    */
    $sberw = 'SberWPlugin';
    $sbers = 'SberSPlugin';
    $sbpw = 'SBPWPlugin';
    $sbps = 'SBPSPlugin';
    /*
        $sovcombank = 'SovcombankPlugin';
        $tinkoffs = 'TinkoffSPlugin';
        $alfabanks = 'AlfabankSPlugin';
        $psbanks = 'PSBankSPlugin';
        $raiffeisens = 'RaiffeisenSPlugin';
    */
    $sb = 'SBPlugin';
    $phonenumber = 'PhoneNumberPlugin';
    $avinfo = 'AvInfoPlugin';
    $beholder = 'BeholderPlugin';
    $skype = 'SkypePlugin';
    $googleplus = 'GooglePlusPlugin';
    $google = 'GooglePlugin';
    $apple = 'ApplePlugin';
    $qiwi = 'QiwiPlugin';
    $yamoney = 'YaMoneyPlugin';
    $elecsnet = 'ElecsnetPlugin';
    $webmoney = 'WebMoneyPlugin';
    $pochta = 'PochtaPlugin';
    $aeroflot = 'AeroflotPlugin';
    $uralair = 'UralAirPlugin';
    $rzd = 'RZDPlugin';
    $papajohns = 'PapaJohnsPlugin';
    $biglion = 'BiglionPlugin';
    $avito = 'AvitoPlugin';

    $gibdd = 'GIBDDPlugin';
    $eaisto = 'EAISTOPlugin';
    $rsa = 'RSAPlugin';
    $kbm = 'KBMPlugin';
    $rz = 'RZPlugin';
    $reestrzalogov = 'ReestrZalogovPlugin';
    //    $autoru = 'AutoRuPlugin';
    $vin = 'VINPlugin';
    $avtokod = 'AvtoKodPlugin';
    $mosru = 'MosRuPlugin';
    $mosrufines = 'MosRuFinesPlugin';
    $nbkiauto = 'NBKIAutoPlugin';
    $avinfo = 'AvInfoPlugin';

    $dns = 'DNSPlugin';
    $ripe = 'RIPEPlugin';
    $ipgeo = 'IPGeoBasePlugin';
    $sypexgeo = 'SypexGeoPlugin';
    $shodan = 'ShodanPlugin';
    $censys = 'CensysPlugin';

    $plugins = [
        'person' => [
            'fms' => ['fms_passport' => $fms],
            'fmsdb' => ['fmsdb_passport' => $fmsdb],
            'gosuslugi' => ['gosuslugi_passport' => $gosuslugi, 'gosuslugi_inn' => $gosuslugi],
            'fns' => [
                'fns_inn' => $fns,
                'fns_bi' => $fns,
                'fns_disqualified' => $fns,
                'fns_mru' => $fns,
                'fns_npd' => $fns,
                'fns_invalid' => $fns,
            ],
            'mvd' => ['mvd_wanted' => $mvdwanted],
            'gisgmp' => [
                'gisgmp_taxes' => $gisgmp, /* 'gisgmp_fssp' => $gisgmp, */
                'gisgmp_fines' => $gisgmp,
            ],
            'notariat' => ['notariat_person' => $notariat],
            'fssp' => [
                'fssp_person' => /*$fsspapp),
        'fsspsite' => array('fssp_person' => */
                    $fssp,
            ],
//        'fsspapi' => array('fssp_person' => $fsspapi),
            'fssp_suspect' => ['fssp_suspect' => $fssp],
            'bankrot' => ['bankrot_person' => $bankrot, 'bankrot_inn' => $bankrot],
            'cbr' => ['cbr_person' => $cbr],
            'terrorist' => ['terrorist_person' => $terrorist],
//        'croinform' => array('croinform_person' => $croinform),
//        'nbki' => array('nbki_credithistory' => $nbki),
//        'people' => array('people' => $people),
            'vk' => ['vk_person' => $vk],
            'ok' => ['ok_person' => $ok],
            'rz' => ['rz_person' => $rz],
            'reestrzalogov' => ['reestrzalogov_person' => $reestrzalogov],
//        'avtokod' => array(/*'avtokod_driver' => $avtokod, *//*'avtokod_fines' => $mosrufines*/),
            'gibdd' => ['gibdd_driver' => $gibdd],
            'rsa' => ['rsa_kbm' => $rsa /* $kbm */],
            'egrul' => ['egrul_person' => $egrul],
            'zakupki' => [/* 'zakupki_eruz' => $zakupki, */
                'zakupki_order' => $zakupki,
                'zakupki_contract' => $zakupki,
                'zakupki_fz223' => $zakupki,
                'zakupki_capital' => $zakupki,
                'zakupki_dishonest' => $zakupki,
                'zakupki_guarantee' => $zakupki,
                'zakupki_rkpo' => $zakupki,
            ],
//        'kad' => array('kad_person' => $kad),
            '2gis' => ['2gis_inn' => $gis],
        ],
        'phone' => [
            'gosuslugi' => ['gosuslugi_phone' => $gosuslugi],
            'rossvyaz' => ['rossvyaz_phone' => $rossvyaz],
            'hlr' => ['hlr_phone' => $hlr /* $smspilot */ /* $stream */],
//        'ss7' => array('infobip_phone' => $stream),
            'smsc' => ['smsc_phone' => $smsc],
//        'infobip' => array('infobip_phone' => $infobip),
//        'sber' => array('sberbank_phone' => $sberbank),
            'sberbank' => ['sberbank_phone' => $sbers],
            'sbertest' => ['sberbank_phone' => $sberw],
            'tinkoff' => ['tinkoff_phone' => $sbps],
            'alfabank' => ['alfabank_phone' => $sbps],
//        'vtb' => array('vtb_phone' => $sbps),
            'openbank' => ['openbank_phone' => $sbps],
            'psbank' => ['psbank_phone' => $sbps],
            'rosbank' => ['rosbank_phone' => $sbps],
            'unicredit' => ['unicredit_phone' => $sbps],
            'raiffeisen' => ['raiffeisen_phone' => $sbps],
            'sovcombank' => ['sovcombank_phone' => $sbps],
            'gazprombank' => ['gazprombank_phone' => $sbps],
            'mkb' => ['mkb_phone' => $sbps],
            'rsb' => ['rsb_phone' => $sbps],
            'avangard' => ['avangard_phone' => $sbps],
            'qiwibank' => ['qiwibank_phone' => $sbps],
            'rnko' => ['rnko_phone' => $sbps],
//        'visa' => array('visa_phone' => $visa),
            'facebook' => ['facebook_phone' => $facebook],
            'vk' => ['vk_phone' => $vk, 'vk_phonecheck' => $vkcheck],
            'ok' => [
                'ok_phone' => $ok,
                'ok_phonecheck' => $okbot,
                'ok_phoneapp' => (264 == $clientId || 265 == $clientId ? $okappr : $okapp)
            ],
            'instagram' => ['instagram_phone' => $instagram],
            'twitter' => ['twitter_phone' => $twitter],
//        'beholder' => array('beholder_phone' => $beholder),
            'skype' => ['skype_phone' => $skype],
            'googleplus' => ['googleplus_phone' => $googleplus],
            'google' => ['google_phone' => $google, 'google_name' => $google],
//        'googlename' => array('googlename_phone' => $google),
            'viber' => ['viber_phone' => $viber],
            'viberwin_phone' => ['viberwin_phone' => $viberwin],
            'telegram' => ['telegram_phone' => $telegram],
//        'telegramweb' => array('telegramweb_phone' => $telegram),
//        'telegramweb' => array('telegramweb_phone' => $telegramweb),
//        'icq' => array('icq_phone' => $icq),
            'whatsapp' => ['whatsappweb_phone' => $whatsapp],
//        'whatsappweb' => array('whatsappweb_phone' => $whatsappweb),
            'whatsapp_phone' => ['whatsapp_phone' => $whatsapp],
//        'hh' => array('hh_phone' => $hh),
            'truecaller' => ['truecaller_phone' => $truecaller],
//        'truecaller' => array('truecallerweb_phone' => $tc),
            'tc' => ['truecaller_phone' => $truecaller],
//        'tc' => array('truecallerweb_phone' => $tc),
            'emt' => ['emt_phone' => $emt],
//        'getcontactweb' => array('getcontactweb_phone' => $getcontact),
            'getcontact' => ['getcontact_phone' => $getcontactapp],
            'callapp' => ['callapp_phone' => $callapp],
            'simpler' => ['simpler_phone' => $simpler],
            'numbuster' => ['numbuster_phone' => (15 == $clientId || 19 == $clientId || 264 == $clientId || 265 == $clientId ? $numbuster : $numbusterapp)],
//        'numbusterapp' => array('numbusterapp_phone' => $numbusterapp),
            'names' => ['names_phone' => $names],
            'phones' => ['phones_phone' => $phones],
//        'qiwi' => array('qiwi_phone' => $qiwi),
//        'yamoney' => array('yamoney_phone' => $yamoney),
//        'elecsnet' => array('elecsnet_phone' => $elecsnet),
            'webmoney' => ['webmoney_phone' => $webmoney],
//        'phonenumber' => array('phonenumber_phone' => $phonenumber),
            'announcement' => ['announcement_phone' => $announcement],
            'boards' => [
                'boards_phone' => $boards,
                'boards_phone_kz' => $boards,
                'boards_phone_by' => $boards,
                'boards_phone_pl' => $boards,
                'boards_phone_ua' => $boards,
                'boards_phone_uz' => $boards,
                'boards_phone_ro' => $boards,
                'boards_phone_pt' => $boards,
                'boards_phone_bg' => $boards,
            ],
//        'commerce' => array(/*'commerce_phone' => $commerce*/),
            'yamap' => ['yamap_phone' => $yamap],
            '2gis' => ['2gis_phone' => $gis],
            'egrul' => ['listorg_phone' => $listorg],
//        'avinfo' => array('avinfo_phone' => $avinfo),
            'pochta' => ['pochta_phone' => $pochta],
            'aeroflot' => ['aeroflot_phone' => $aeroflot],
//        'uralair' => array('uralair_phone' => $uralair),
            'papajohns' => ['papajohns_phone' => $papajohns],
            'avito' => ['avito_phone' => $avito],
//        'biglion' => array('biglion_phone' => $biglion),
        ],
        'email' => [
            'gosuslugi' => ['gosuslugi_email' => $gosuslugi],
            'facebook' => ['facebook_email' => $facebook],
            'vk' => ['vk_email' => $vk, 'vk_emailcheck' => $vkcheck],
            'ok' => [
                'ok_email' => $ok,
                'ok_emailcheck' => $okbot,
                'ok_emailapp' => (264 == $clientId || 265 == $clientId ? $okappr : $okapp)
            ],
            'instagram' => ['instagram_email' => $instagram],
            'twitter' => ['twitter_email' => $twitter],
            'mailru' => ['mailru_email' => $mailru],
            'skype' => ['skype_email' => $skype],
            'googleplus' => ['googleplus_email' => $googleplus],
            'google' => ['google_email' => $google, 'google_name' => $google],
//        'googlename' => array('googlename_email' => $google),
            'apple' => ['apple_email' => $apple],
//        'hh' => array('hh_email' => $hh),
//        'commerce' => array('commerce_email' => $commerce),
            'aeroflot' => ['aeroflot_email' => $aeroflot],
//        'uralair' => array('uralair_email' => $uralair),
            'rzd' => ['rzd_email' => $rzd],
//        'papajohns' => array('papajohns_email' => $papajohns),
            'avito' => ['avito_email' => $avito],
        ],
        'skype' => [
            'skype' => ['skype' => $skype],
//        'commerce' => array('commerce_skype' => $commerce),
        ],
        'nick' => [
            'skype' => ['skype' => $skype],
//        'commerce' => array('commerce_skype' => $commerce),
        ],
        'url' => [
//        'facebook' => array('facebook_url' => $facebook),
            'vk' => ['vk_url' => $vk],
            'ok' => ['ok_url' => $ok/* , 'ok_urlcheck' => $okbot */],
            'instagram' => ['instagram_url' => $instagram],
//        'hh' => array('hh_url' => $hh),
        ],
        'car' => [
            'gibdd' => [
                'gibdd_history' => $gibdd,
                'gibdd_aiusdtp' => $gibdd,
                'gibdd_wanted' => $gibdd,
                'gibdd_restricted' => $gibdd,
                'gibdd_diagnostic' => $gibdd,
                'gibdd_fines' => $gibdd,
            ],
            'eaisto' => ['eaisto' => $eaisto],
            'rsa' => ['rsa_policy' => $rsa],
            'rz' => ['rz_auto' => $rz],
            'reestrzalogov' => ['reestrzalogov_auto' => $reestrzalogov],
            'gisgmp' => ['gisgmp_fines' => $gisgmp],
//        'autoru' => array('autoru' => $autoru),
//        'vin' => array('vin' => $vin),
//        'avtokod' => array('avtokod_history' => $mosru, 'avtokod_pts' => $mosru, /*'avtokod_fines' => $mosrufines,*/ /*'avtokod_status' => $avtokod,*/ 'avtokod_taxi' => $mosru),
//        'nbki' => array('nbki_auto' => $nbkiauto),
//        'avinfo' => array('avinfo_auto' => $avinfo),
        ],
        'ip' => [
            'dns' => ['dns' => $dns],
            'ripe' => ['ripe' => $ripe],
            'ipgeo' => ['ipgeo' => $ipgeo],
            'sypexgeo' => ['sypexgeo' => $sypexgeo],
            'shodan' => ['shodan' => $shodan],
            'censys' => ['censys' => $censys],
        ],
        'org' => [
            'egrul' => ['egrul_org' => $egrul/* 'egrul_daughter' => $egrul */ /* , 'listorg_org' => $listorg */],
            'fns' => [
                'fns_bi' => $fns,
                'fns_svl' => $fns,
                'fns_disfind' => $fns,
                'fns_zd' => $fns,
                /* 'fns_sshr' => $fns, 'fns_snr' => $fns, 'fns_revexp' => $fns, 'fns_paytax' => $fns, 'fns_debtam' => $fns, 'fns_taxoffence' => $fns */
                /* , 'fns_uwsfind' => $fns, 'fns_ofd' => $fns */
            ],
            'vestnik' => ['vestnik_org' => $vestnik/* , 'vestnik_fns' => $vestnik */],
//        'gks' => array('gks_org' => $gks),
            'zakupki' => [/* 'zakupki_eruz' => $zakupki, */
                'zakupki_org' => $zakupki, /* 'zakupki_customer223' => $zakupki, */
                'zakupki_order' => $zakupki,
                'zakupki_contract' => $zakupki,
                'zakupki_fz223' => $zakupki,
                'zakupki_capital' => $zakupki,
                'zakupki_dishonest' => $zakupki,
                'zakupki_guarantee' => $zakupki,
                'zakupki_rkpo' => $zakupki,
            ],
//        'kad' => array('kad_org' => $kad),
            'bankrot' => ['bankrot_org' => $bankrot],
            'cbr' => ['cbr_org' => $cbr],
            'rz' => ['rz_org' => $rz],
            'reestrzalogov' => ['reestrzalogov_org' => $reestrzalogov],
            'rsa' => ['rsa_org' => $rsa],
            'fssp' => ['fssp_org' => $fssp],
//        'fsspapi' => array('fssp_org' => $fsspapi),
            'fsspsite' => ['fssp_org' => $fssp],
            '2gis' => ['2gis_inn' => $gis],
        ],
        'card' => [//        'sber' => array('sberbank_card' => $sb),
        ],
        'fssp_ip' => [
            'fssp' => ['fssp_ip' => $fssp],
//        'fsspapi' => array('fssp_ip' => $fsspapi),
//        'fsspsite' => array('fssp_ip' => $fssp),
//        'gisgmp' => array('gisgmp_ip' => $gisgmp),
        ],
        'osago' => [
            'rsa' => ['rsa_bsostate' => $rsa/* , 'rsa_osagovehicle' => $rsa */],
        ],
        'text' => [
//        'facebook' => array('facebook_text' => $facebook),
//        'vk' => array('vk_text' => $vk),
//        'ok' => array('ok_text' => $ok),
//        'hh' => array('hh_text' => $hh),
        ],
    ];

    return $plugins;
}


function runRequests($params)
{
    if (false !== \array_search('rsa', $params['sources'])) {
        //        $params['timeout'] = $params['timeout']*5;
    }
    \set_time_limit($params['timeout'] + 10);

    $plugins = initPlugins($params, $params['sources']);
    $uuid = \uniqid();
    $params['_logger']->debug('Plugins request', [
        'uuid' => $uuid,
        'request' => $params,
    ]);
    $rm = new RequestManager($params['timeout']);
    $response = $rm->performRequests($params, $plugins);
    $params['_logger']->debug('Plugins response', [
        'uuid' => $uuid,
        'response' => $response,
    ]);

    return $response;
}

function generateResponse(array $params, $results, $status)
{
    $serviceurl = $params['_serviceurl'];
    $reqId = $params['_reqId'];
    $req = $params['_req'];
    $restime = $params['_restime'];

    $response = '<?xml version="1.0" encoding="utf-8"?>';
    $response .= "\n<Response id=\"$reqId\" status=\"$status\" datetime=\"$restime\" result=\"/showresult?id=$reqId&amp;mode=xml\" view=\"/showresult?id=$reqId\">\n" . $req;

    foreach ($results as $result) {
        if (/* ($result->getPlugin() instanceof PluginInterface) && */ $result->getResultData() || $result->getError()) {
            $response .= '
            <Source code="' . $result->getSource() . '" checktype="' . $result->getCheckType() . '" request_id="' . $result->getId() . '" process_time="' . $result->processTime() . '">
                <Name>' . $result->getSourceName() . '</Name>
                <Title>' . $result->getSourceTitle() . '</Title>
                <CheckTitle>' . $result->getCheckTitle() . '</CheckTitle>';

            $response .= '
                <Request>' . \htmlspecialchars(
                    \implode(' ', 'hh_url' == $result->getCheckType() ? ['hh_url *****'] : $result->getInitData()),
                    \ENT_XML1
                ) . '</Request>';

            if ($result->getError()) {
                if (is_string($result->getError())) {
                    $error = \htmlspecialchars($result->getError(), \ENT_XML1);
                } else {
                    $error = 'Unexpected error';
                }
                $response .= '
                <Error>' . $error . '</Error>';
            } else {
                $rData = $result->getResultData();

                /*$appendResult = function(&$response, $record)
                {
                    if(sizeof($record))
                    {
                        $response .= "
                <Record>";

                        foreach($record as $field)
                            $response .= "
                    <Field>
                        <FieldType>".$field->getType()."</FieldType>
                        <FieldName>".$field->getName()."</FieldName>
                        <FieldTitle>".$field->getTitle()."</FieldTitle>
                        <FieldDescription>".$field->getDesc()."</FieldDescription>
                        <FieldValue>".$field->getValue()."</FieldValue>
                    </Field>";

                        $response .= "
                 </Record>";
                    }

                };*/

                if ($rData instanceof ResultDataList) {
                    $response .= '
                <ResultsCount>' . $rData->getResultsCount() . '</ResultsCount>';
                    foreach ($rData->getResults() as $record) {
                        compileField($response, $record);
                    }
                    foreach ($rData->getResults() as $record) {
                        compileContact($params, $response, $record);
                    }
                } else {
                    compileField($response, $rData);
                }
            }

            $response .= "
            </Source>\n";
        }
    }
    $response .= '</Response>';

    if ($status && \count($params['rules'] ?? [])) {
        require_once 'decision.php';
        if ($dec_response = make_decision($response, $params['rules'])) {
            $response = $dec_response;
        }
    }

    return $response;
}


function compileField(&$response, $record): void
{
    if (\count($record)) {
        $response .= '
                <Record>';

        foreach ($record as $field) {
            $response .= '
                    <Field>
                        <FieldType>' . $field->getType() . '</FieldType>
                        <FieldName>' . $field->getName() . '</FieldName>
                        <FieldTitle>' . $field->getTitle() . '</FieldTitle>
                        <FieldDescription>' . $field->getDesc() . '</FieldDescription>
                        <FieldValue>' . ('hidden' == $field->getType() ? ' *****' : \htmlspecialchars(
                    \preg_replace('/(?:\\\\u[\pL\p{Zs}])+/', '', (string)$field->getValue()),
                    \ENT_XML1
                )) . '</FieldValue>
                    </Field>';
        }

        $response .= '
                 </Record>';
    }
}

function compileContact(array $params, &$response, $record): void
{
    $contact_types = $params['_contact_types'];
    $contact_urls = $params['_contact_urls'];

    foreach ($record as $field) {
        if (\in_array($field->getType(), $contact_types) || ('url' == $field->getType() && \in_array(
                    \parse_url($field->getValue(), \PHP_URL_HOST),
                    $contact_urls
                ))) {
            $response .= '
                <Contact>
                    <ContactType>' . $field->getType() . '</ContactType>
                    <ContactTitle>' . $field->getTitle() . '</ContactTitle>
                    <ContactId>' . \htmlspecialchars(
                    \preg_replace('/(?:\\\\u[\pL\p{Zs}])+/', '', (string)$field->getValue()),
                    \ENT_XML1
                ) . '</ContactId>
                </Contact>';
        }
    }
}
