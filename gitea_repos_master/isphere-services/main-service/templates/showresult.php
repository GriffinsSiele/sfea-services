<?php

/**
 * @global Connection $connection
 * @global SystemUser $user
 */

declare(strict_types=1);

require 'xml.php';

use App\Entity\SystemUser;
use App\Utils\Legacy\LoggerUtilStatic;
use Doctrine\DBAL\Connection;

function mysqli_result(array $res, int $row = 0, int $col = 0): mixed
{
    if (!isset($res[$row])) {
        return false;
    }

    $values = \array_values($res[$row]);

    return $values[$col] ?? false;
}

$condition = '';
$userid = $user->getId();
$clientid = $user->getClient()?->getId();
$user_level = $user->getAccessLevel();
$user_area = $user->getResultsArea() ?: $user->getAccessArea();

if ($user_area <= 2) {
    $condition .= " AND (user_id=$userid";
    if ($user_area >= 1) {
        $condition .= " OR user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId=$userid)";
        if ($user_area > 1) {
            $condition .= " OR client_id=$clientid";
        }
    }
    $condition .= ')';
}

$id = (isset($_REQUEST['id']) && \preg_match("/^[1-9]\d+$/", $_REQUEST['id'])) ? $_REQUEST['id'] : '';
if (!$id) {
    $id = 0;
    $theResult = false;
}

if ($id) {
    $sql = "SELECT id FROM RequestNew r WHERE id='" . $id . "'" . $condition . ' LIMIT 1';
    $result = $connection->executeQuery($sql)->fetchAllAssociative();

    if (0 === \count($result) || !mysqli_result($result, 0)) {
        $theResult = false;
    } else {
        // Тщимся найти чего надо...   варианты:  1. просто файл (недавнии подии)...   2. файл в архиве (давнии подии)...   ответ из БД - с негодованием исключаем из списка...
        // получаем местоположение файла....

        $trimmed = rtrim($xmlpath, '/');
        $numName = \str_pad($id, 9, '0', \STR_PAD_LEFT);
        $titles = \str_split($numName, 3);

        if (\file_exists($trimmed . '/' . $titles[0] . '/' . $titles[1] . '/' . $titles[2] . '_res.xml')) {
            $theResult = \file_get_contents($trimmed . '/' . $titles[0] . '/' . $titles[1] . '/' . $titles[2] . '_res.xml');
            if (!$theResult) {
                \usleep(10);
                $theResult = \file_get_contents($trimmed . '/' . $titles[0] . '/' . $titles[1] . '/' . $titles[2] . '_res.xml');
            }
        } elseif (\file_exists($trimmed . '/' . $titles[0] . '/' . $titles[1] . '.tar.gz')) {
            $theResult = \shell_exec('tar xzfO ' . $trimmed . '/' . $titles[0] . '/' . $titles[1] . '.tar.gz ' . $titles[2] . '_res.xml');
        }
    }
}
if (!isset($theResult) || !$theResult) {
    if (isset($_REQUEST['mode']) && 'xml' != $_REQUEST['mode'] && 'json' != $_REQUEST['mode']) {
        echo "Данные запроса $id недоступны";
        return;
    } else {
        $theResult = '<?xml version="1.0" encoding="utf-8"?>';
        $theResult .= '<Response id="' . $id . '" status="-1" datetime="2020-01-01T00:00:00" result="/showresult?id=' . $id . '&amp;mode=xml" view="/showresult?id=' . $id . '">';
        $theResult .= '</Response>';
        //                    http_response_code(404);
    }
}

if (isset($_REQUEST['mode']) && 'xml' == $_REQUEST['mode']) {
    \header('Content-Type:text/xml');
    echo $theResult;
} elseif (isset($_REQUEST['mode']) && 'json' == $_REQUEST['mode']) {
    \header('Content-Type:application/json');
    $xml = \simplexml_load_string($theResult);
    $xml['result'] = \strtr((string)$xml['result'], ['mode=xml' => 'mode=json']);
    $json = \json_encode($xml, \JSON_PRETTY_PRINT);
    echo $json;
} else {
    $fn = static function ($path): string {
        if (\file_exists($path)) {
            return $path;
        }

        $path = 'public/' . $path;

        if (\file_exists($path)) {
            return $path;
        }

        throw new \RuntimeException('Path is unreadable: ' . $path);
    };

    $doc = xml_transform(\strtr($theResult, ['request>' => 'Request>']), isset($_REQUEST['mode']) && 'pdf' == $_REQUEST['mode'] ? $fn('isphere_view_pdf.xslt') : $fn('isphere_view.xslt'));
    if ($doc) {
        $servicename = isset($servicenames[$_SERVER['HTTP_HOST']]) ? 'платформой ' . $servicenames[$_SERVER['HTTP_HOST']] : '';
        /** @var \DOMElement $body */
        $body = $doc->getElementsByTagName('body')[0];
        $tmp = new DOMDocument();
        foreach ($body->childNodes as $child) {
            $tmp->appendChild($tmp->importNode($child, true));
        }
        $html = \strtr($tmp->saveHTML(), ['$servicename' => $servicename]);
        if (isset($_REQUEST['mode']) && 'pdf' == $_REQUEST['mode']) {
            $descriptorspec = [
                0 => ['pipe', 'r'], // stdin
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w'], // stderr
            ];
            // --disable-smart-shrinking без этого аргумента, всё становится каких-то не правильных пропорций
            // --dpi 96 если принудительно не поставить dpi, то размеры указанные в css в милиметрах на печати будут совсем не такими!
            // - последний аргумент это прочерк, чтобы передать html через stdin
            $i = 0;
            $pdf = false;
            while ($i++ <= 3 && !$pdf) {
                $logger->debug('pdf generation start', ['iteration' => $i]);
                $process = \proc_open($cmd = 'wkhtmltopdf --quiet --disable-local-file-access --javascript-delay 1000 --margin-left 20mm --dpi 96  - -', $descriptorspec, $pipes);
                $logger->debug('pdf generation executing', ['cmd' => $cmd]);
                if (\is_resource($process)) {
                    \copy($fn('view.css'), '/tmp/view.css');
                    // Пишем html в stdin
                    \fwrite($pipes[0], $html);
                    \fclose($pipes[0]);
                    // Читаем pdf из stdout
                    $pdf = \stream_get_contents($pipes[1]);
                    \fclose($pipes[1]);
                    // Читаем ошибки из stderr
                    $err = \stream_get_contents($pipes[2]);
                    \fclose($pipes[2]);
                    $exitCode = \proc_close($process);
                    if ($exitCode === 127) {
                        $logger->error('executor not found');
                        break;
                    }

                    $start = \strpos($pdf, '%PDF');
                    if ($start) {
                        $pdf = \substr($pdf, $start);
                    } else {
                        if ($pdf) {
                            LoggerUtilStatic::file_put_contents('logs/pdf/' . $_REQUEST['id'] . '_' . \time() . '.txt', $pdf);
                            break;
                        } elseif ($i < 5) {
                            \sleep(5);
                        }
                        $pdf = false;
                    }
                }
            }
            if ($pdf) {
                \header('Content-Type:applcation/pdf');
                \header('Content-Disposition:attachment; filename=report_' . $_REQUEST['id'] . '.pdf');
                echo $pdf;
            } else {
                echo 'Ошибка сохранения в pdf';
                LoggerUtilStatic::file_put_contents('logs/pdf/' . $_REQUEST['id'] . '_' . \time() . '.txt', $pdf);
            }
        } else {
            echo $html;
        }
    } else {
        echo 'Данные недоступны';
    }
}
