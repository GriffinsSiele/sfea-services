<?php

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require 'functions.php';
require 'vendor/autoload.php';
require '../config.php';

function cycle($database): void
{
    $files = \glob('/opt/bulk/*/status.txt');

    if (\count($files) < 1) {
        \sleep(60);
        exit;
    }

    foreach ($files as $file) {
        $tmp = \explode('/', $file);
        $dir = $tmp[3];
        echo $dir."\n";
        // echo $file."\n";
        $status = \file_get_contents($file);
        if (0 == $status && \file_exists('/opt/bulk/'.$dir.'/pre.csv')) { // свежезалитый файл...
            \file_put_contents('/opt/bulk/'.$dir.'/pre.csv', \trim(\file_get_contents('/opt/bulk/'.$dir.'/pre.csv')));
            $content = \file_get_contents('/opt/bulk/'.$dir.'/pre.csv');
            if (\preg_match("/(^|\s)(\"\"\,)+\"\"\s/", $content)) {
                echo "empty lines\n";
                $data = \explode("\n", $content);
                $newContent = '';
                foreach ($data as $string) {
                    if (!\preg_match("/^(\"\"\,)+\"\"/", $string)) {
                        $newContent .= $string."\n";
                    }
                }
                \file_put_contents('/opt/bulk/'.$dir.'/pre.csv', $newContent);
            }
            $content = \file_get_contents('/opt/bulk/'.$dir.'/pre.csv');
            if (\preg_match("/\d{1,2}\/\d{1,2}\/\d{4}/", $content)) {
                echo "Excel style dates\n";
                $data = \explode("\n", $content);
                $newContent = '';
                foreach ($data as $string) {
                    if (\preg_match_all("/\d{1,2}\/\d{1,2}\/\d{4}/", $string, $matches)) {
                        foreach ($matches[0] as $v) {
                            $string = \str_replace($v, \date('d.m.Y', \strtotime($v)), $string);
                        }
                    }
                    $newContent .= $string."\n";
                }
                \file_put_contents('/opt/bulk/'.$dir.'/pre.csv', $newContent);
            }
            \file_put_contents($file, '10'); // преобработка
        } elseif (10 == $status && \file_exists('/opt/bulk/'.$dir.'/the.conf')) { // пилим на запросы...
            $config = getConfig('/opt/bulk/'.$dir.'/the.conf');
            $fields = \json_decode(\file_get_contents('/opt/bulk/'.$dir.'/fields.txt'), true);
            $list = \file('/opt/bulk/'.$dir.'/pre.csv');
            //			print_r($config);
            //			exit;
            foreach ($list as $key => $str) {
                if (!\preg_match("/\d{4}/", $str)) {
                    continue;
                }
                foreach ($config['sources'] as $source) {
                    if (\file_exists('/opt/bulk/'.$dir.'/'.$source.'/'.($key + 1).'/fResult.txt') && \filesize('/opt/bulk/'.$dir.'/'.$source.'/'.($key + 1).'/fResult.txt')) {
                        echo "response exists\n";
                        continue;
                    }
                    $info = [];

                    $tmpArr = \str_getcsv(\trim($str));

                    if (isset($fields['id'])) {
                        $info['id'] = \trim($tmpArr[$fields['id']]);
                    } else {
                        $info['id'] = $key + 1;
                    }

                    if (isset($fields['fio'])) {
                        if (\preg_match("/\([^\(\)]+\)/", $tmpArr[$fields['fio']])) {
                            $tmpArr[$fields['fio']] = \preg_replace("/\([^\(\)]+\)/", ' ', $tmpArr[$fields['fio']]);
                        }
                        $fio = \explode(' ', \preg_replace("/\s+/", ' ', \trim($tmpArr[$fields['fio']])));
                        $info['last_name'] = $fio[0];
                        $info['first_name'] = $fio[1];
                        $info['patronymic'] = \count($fio) > 2 ? $fio[2] : '';
                        if (\count($fio) > 3) {
                            $info['patronymic'] .= ' '.$fio[3];
                        }
                    }

                    if (isset($fields['iof'])) {
                        if (\preg_match("/\([^\(\)]+\)/", $tmpArr[$fields['iof']])) {
                            $tmpArr[$fields['iof']] = \preg_replace("/\([^\(\)]+\)/", ' ', $tmpArr[$fields['iof']]);
                        }
                        $fio = \explode(' ', \preg_replace("/\s+/", ' ', \trim($tmpArr[$fields['iof']])));
                        $info['last_name'] = $fio[\count($fio) - 1];
                        $info['first_name'] = $fio[0];
                        $info['patronymic'] = \count($fio) > 2 ? $fio[1] : '';
                        if (\count($fio) > 3) {
                            $info['patronymic'] .= ' '.$fio[2];
                        }
                    }

                    if (isset($fields['lastName'])) {
                        $info['last_name'] = \trim($tmpArr[$fields['lastName']]);
                        if (isset($fields['firstName'])) {
                            $info['first_name'] = \trim($tmpArr[$fields['firstName']]);
                        }
                        if (isset($fields['patronymic'])) {
                            $info['patronymic'] = \trim($tmpArr[$fields['patronymic']]);
                        }
                    }

                    if (isset($fields['bDate'])) {
                        if (\preg_match("/^\d{5}$/", \trim($tmpArr[$fields['bDate']]))) {
                            require_once 'vendor/autoload.php';
                            $info['date'] = \date('d.m.Y', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp(\trim($tmpArr[$fields['bDate']])));
                        } else {
                            $info['date'] = \trim($tmpArr[$fields['bDate']]);
                        }
                    }

                    if (isset($fields['phone'])) {
                        $info['mobile_phone'] = $tmpArr[$fields['phone']];
                    }

                    if (isset($fields['region_id'])) {
                        $info['region_id'] = \preg_replace("/\D+/", '', $tmpArr[$fields['region_id']]);
                    }

                    if ('fns' == $source) {
                        if (isset($fields['passport'])) {
                            $tmpPassport = \preg_replace("/\D+/", '', $tmpArr[$fields['passport']]);
                            while (\strlen($tmpPassport) > 6 && \strlen($tmpPassport) < 10) {
                                $tmpPassport = '0'.$tmpPassport;
                            }
                            $info['serial'] = \substr($tmpPassport, 0, 4);
                            $info['number'] = \substr($tmpPassport, 4, 6);
                        }

                        if (isset($fields['serial'])) {
                            $info['serial'] = \preg_replace("/\D+/", '', $tmpArr[$fields['serial']]);
                            while (\strlen($info['serial']) < 4) {
                                $info['serial'] = '0'.$info['serial'];
                            }
                        }

                        if (isset($fields['number'])) {
                            $info['number'] = \preg_replace("/\D+/", '', $tmpArr[$fields['number']]);
                            while (\strlen($info['number']) < 6) {
                                $info['number'] = '0'.$info['number']."\n";
                            }
                        }
                    }

                    if ('bankrot' == $source) {
                        if (isset($fields['inn'])) {
                            $info['inn'] = \preg_replace("/\D/", '', \trim($tmpArr[$fields['inn']]));
                            if (\strlen($info['inn']) > 9) {
                                while (\strlen($info['inn']) < 12) {
                                    $info['inn'] = '0'.$info['inn'];
                                }
                            }
                        }

                        if (isset($info['inn']) && '' != $info['inn']) {
                            unset($info['patronymic']);
                            unset($info['first_name']);
                            unset($info['last_name']);
                            unset($info['date']);
                        }
                    }

                    $wDir = '/opt/bulk/'.$dir.'/'.$source.'/'.($key + 1); // $info['id'];
                    @\mkdir($wDir);
                    /*
                                                            if(!file_exists($wDir.'/fResult.txt') || !filesize($wDir.'/fResult.txt')){
                    // Проверка на одновременную обработку
                                                                $file = fopen($wDir.'/lock.txt', "w");
                                                                if ($file) {
                                                                    if (flock($file,LOCK_EX|LOCK_NB)) {
                    */
                    _process($info, $config, $source, $key, $dir);
                    /*
                                                                        fclose($file);
                                                                        unlink($wDir.'/lock.txt');
                                                                    } else {
                                                                        fclose($file);
                                                                    }
                                                                }
                                                            }
                    */
                }
                //				exit;
            }
            \file_put_contents($file, '20');
            $mysqli = \mysqli_connect($database['server'], $database['login'], $database['password'], $database['name']) || exit(\mysqli_errno($mysqli).': '.\mysqli_error($mysqli));
            if ($mysqli) {
                \mysqli_query($mysqli, 'Set character set utf8');
                \mysqli_query($mysqli, "Set names 'utf8'");
            } else {
                echo 'mysqli ek!!';
            }

            $pr = \count(\explode("\n", \file_get_contents('/opt/bulk/'.$dir.'/pre.csv'))) - 1;

            $mysqli->query("UPDATE isphere.Bulk SET status=0, total_rows='".\count($list)."' WHERE id='".$dir."'");
            $mysqli->close();
        } elseif (20 == $status && \file_exists('/opt/bulk/'.$dir.'/the.conf')) {  // проверяем готовы-ли ответы и есть-ли ошибки...
            $config = getConfig('/opt/bulk/'.$dir.'/the.conf');

            $siteNumber = 'https://i-sphere.ru/2.00/' == $config['serviceurl'] ? 1 : 2;

            $reqFiles = \glob('/opt/forReq/'.$siteNumber.'/'.$dir.'___*');
            if (\count($reqFiles) < 1) { // иссякли запросы...  проверить ошибки...
                $final = true;
                $errors = [];
                foreach ($config['sources'] as $source) {
                    $respFiles = \glob('/opt/bulk/'.$dir.'/'.$source.'/*/fResult.txt');
                    // print_r($files);
                    foreach ($respFiles as $respFile) {
                        $rContent = \file_get_contents($respFile);
                        if ('' == $rContent || \preg_match('/Error/si', $rContent)) {
                            (!isset($errors[$source])) ? $errors[$source] = 1 : $errors[$source]++;
                            $inc = 0;
                            $incFile = \strtr($respFile, ['.txt' => '.inc']);
                            $inc = \file($incFile) ? \file_get_contents($incFile) : 0;
                            if ($inc < 10 && \preg_match('/(Некорректный ответ сервиса|Превышено время ожидания|Превышено количество попыток получения ответа|Нет актуальных сессий|Сервис временно недоступен|Сервис не отвечает|Ошибка при выполнении запроса|Не удалось выполнить поиск|Сервис выключен|Внутренняя ошибка|Произошла внутренняя ошибка|Ошибка получения капчи|Технические работы)/', $rContent)) {
                                //  если источник сдох - зациклит ...   надо проверять статус источника...
                                \copy($respFile, \strtr($respFile, ['.txt' => '.err']));
                                \unlink($respFile);
                                \file_put_contents($incFile, ++$inc);
                                $final = false;
                            }
                        }
                    }
                }
                if ($final) {
                    \file_put_contents($file, '30');  // сделать результаты источников
                    if (\count($errors)) {
                        \file_put_contents('/opt/bulk/'.$dir.'/comment.txt', 'Не удалось обработать часть запросов в '.\implode(',', \array_keys($errors)));
                    }
                } else {
                    \file_put_contents($file, '10');
                }
            } else {
                echo $dir." waiting\n";
            }
        } elseif (30 == $status && \file_exists('/opt/bulk/'.$dir.'/the.conf')) {
            $config = getConfig('/opt/bulk/'.$dir.'/the.conf');

            foreach ($config['sources'] as $source) {
                doResult('/opt/bulk/'.$dir.'/'.$source);
            }

            \file_put_contents('/opt/bulk/'.$dir.'/list.csv', \trim(\file_get_contents('/opt/bulk/'.$dir.'/pre.csv')));
            // FNS
            if (\in_array('fns', $config['sources'])) {
                $orgData = \explode("\n", \file_get_contents('/opt/bulk/'.$dir.'/list.csv'));

                $data = \explode("\n", \file_get_contents('/opt/bulk/'.$dir.'/fns/fns_inn.csv'));

                $position = \array_search('"INN"', \explode(';', $data[0]));

                $tmpData = [];

                foreach ($data as $key => $val) {
                    $tmp = \str_getcsv($val, ';');
                    $tmpData[$tmp[0]] = ',"'.$tmp[$position].'"';
                }

                //				print_r($tmpData);

                foreach ($orgData as $k => $v) {
                    if (isset($tmpData[$k + 1])) {
                        $orgData[$k] = $v.$tmpData[$k + 1].'';
                    } else {
                        if (0 == $k) {
                            $orgData[$k] = $v.',"INN"';
                        } else {
                            $orgData[$k] = $v.',""';
                        }
                    }
                }
            }

            // BANKROT
            if (\in_array('bankrot', $config['sources'])) {
                if (!isset($orgData) || !$orgData) {
                    $orgData = \explode("\n", \file_get_contents('/opt/bulk/'.$dir.'/list.csv'));
                }
                $data = \explode("\n", \file_get_contents('/opt/bulk/'.$dir.'/bankrot/bankrot_person.csv'));
                $position = \array_search('"publicationdate"', \explode(';', $data[0]));

                $tmpData = [];

                foreach ($data as $key => $val) {
                    $tmp = \str_getcsv($val, ';');
                    if (isset($tmp[$position]) && \strlen($tmp[$position]) > 5) {
                        $tmpData[$tmp[0]] = $tmp[$position];
                    }
                }

                \print_r($tmpData);

                foreach ($orgData as $k => $v) {
                    if (isset($tmpData[$k + 1])) {
                        $orgData[$k] = $v.',"1"';
                    } else {
                        if (0 == $k) {
                            $orgData[$k] = $v.',"Банкрот"';
                        } else {
                            $orgData[$k] = $v.',"0"';
                        }
                    }
                }
            }

            // FSSP
            if (\in_array('fssp', $config['sources'])) {
                if (!isset($orgData) || !$orgData) {
                    $orgData = \explode("\n", \file_get_contents('/opt/bulk/'.$dir.'/list.csv'));
                }
                $workTmp = [];

                $data = \explode("\n", \file_get_contents('/opt/bulk/'.$dir.'/fssp/fssp_person.csv'));

                // "CloseDate", "Total", "BailiffTotal", "CostsTotal", "FineTotal"

                $posArr = \explode(';', $data[0]);
                $ps = [];
                if (false !== \array_search('"row"', $posArr)) {
                    $ps['rPos'] = \array_search('"row"', $posArr);
                }
                if (false !== \array_search('"CloseDate"', $posArr)) {
                    $ps['clPos'] = \array_search('"CloseDate"', $posArr);
                }
                if (false !== \array_search('"Total"', $posArr)) {
                    $ps['tPos'] = \array_search('"Total"', $posArr);
                }
                if (false !== \array_search('"BailiffTotal"', $posArr)) {
                    $ps['btPos'] = \array_search('"BailiffTotal"', $posArr);
                }
                if (false !== \array_search('"CostsTotal"', $posArr)) {
                    $ps['ctPos'] = \array_search('"CostsTotal"', $posArr);
                }
                if (false !== \array_search('"FineTotal"', $posArr)) {
                    $ps['ftPos'] = \array_search('"FineTotal"', $posArr);
                }

                \print_r($ps);
                //				exit;

                foreach ($data as $key => $val) {
                    $tmp = \str_getcsv($val, ';');
                    if (\count($tmp) < 3) {
                        continue;
                    }
                    if ('row' == $tmp[$ps['rPos']]) {
                        $workTmp[$key + 1] = ['Кол-во ИП', 'Сумма ИП', 'Завершено ИП'];
                    } else {
                        //						foreach($ps as  $somenumber){
                        //							echo $tmp[$somenumber]." => ".$somenumber."\n";
                        //						}

                        if (!isset($workTmp[$tmp[$ps['rPos']]])) {
                            $sum = 0;

                            if (isset($ps['tPos']) && (float) \trim($tmp[$ps['tPos']]) > 0) {
                                $sum += (float) \trim($tmp[$ps['tPos']]);
                            }

                            if (isset($ps['btPos']) && (float) \trim($tmp[$ps['btPos']]) > 0) {
                                $sum += (float) \trim($tmp[$ps['btPos']]);
                            }

                            if (isset($ps['ctPos']) && (float) \trim($tmp[$ps['ctPos']]) > 0) {
                                $sum += (float) \trim($tmp[$ps['ctPos']]);
                            }

                            if (isset($ps['ftPos']) && (float) \trim($tmp[$ps['ftPos']]) > 0) {
                                $sum += (float) \trim($tmp[$ps['ftPos']]);
                            }

                            $workTmp[$tmp[$ps['rPos']]] = [1, $sum,  \preg_match("/\d{2}\.\d{2}\.\d{4}/", $tmp[$ps['clPos']]) ? 1 : 0];
                        } else {
                            $quan = $workTmp[$tmp[$ps['rPos']]][0] + 1;
                            $closed = $workTmp[$tmp[$ps['rPos']]][2];

                            if (\preg_match("/\d{2}\.\d{2}\.\d{4}/", $tmp[$ps['clPos']])) {
                                ++$closed;
                            }

                            $sum = (float) \trim($workTmp[$tmp[$ps['rPos']]][1]);

                            if (isset($ps['tPos']) && (float) \trim($tmp[$ps['tPos']]) > 0) {
                                $sum += (float) \trim($tmp[$ps['tPos']]);
                            }

                            if (isset($ps['btPos']) && (float) \trim($tmp[$ps['btPos']]) > 0) {
                                $sum += (float) \trim($tmp[$ps['btPos']]);
                            }

                            if (isset($ps['ctPos']) && (float) \trim($tmp[$ps['ctPos']]) > 0) {
                                $sum += (float) \trim($tmp[$ps['ctPos']]);
                            }

                            if (isset($ps['ftPos']) && (float) \trim($tmp[$ps['ftPos']]) > 0) {
                                $sum += (float) \trim($tmp[$ps['ftPos']]);
                            }

                            $workTmp[$tmp[$ps['rPos']]] = [$quan, $sum, $closed];
                        }
                        // print_r($workTmp);
                    }
                }

                foreach ($workTmp as $key => $val) {
                    if (\preg_match("/\./", $val[1])) {
                        $workTmp[$key][1] = \preg_replace("/\./", ',', $val[1]);
                    }
                }

                foreach ($orgData as $k => $v) {
                    $tmp = \str_getcsv($v);
                    if (isset($workTmp[$k + 1])) {
                        $orgData[$k] = $v.',"'.\implode('","', $workTmp[$k + 1]).'"';
                    } else {
                        $orgData[$k] = $v.',"","",""';
                    }
                }
            }

            // NOTARIAT
            if (\in_array('notariat', $config['sources'])) {
                if (!isset($orgData) || !$orgData) {
                    $orgData = \explode("\n", \file_get_contents('/opt/bulk/'.$dir.'/list.csv'));
                }
                $data = \explode("\n", \file_get_contents('/opt/bulk/'.$dir.'/notariat/notariat_person.csv'));
                $position = \array_search('"CaseDate"', \explode(';', $data[0]));

                $tmpData = [];

                foreach ($data as $key => $val) {
                    $tmp = \str_getcsv($val, ';');
                    if (isset($tmp[$position]) && \strlen($tmp[$position]) > 5) {
                        $tmpData[$tmp[0]] = $tmp[$position];
                    }
                }

                \print_r($tmpData);

                foreach ($orgData as $k => $v) {
                    if (isset($tmpData[$k + 1])) {
                        $orgData[$k] = $v.',"1"';
                    } else {
                        if (0 == $k) {
                            $orgData[$k] = $v.',"Наследодатель"';
                        } else {
                            $orgData[$k] = $v.',"0"';
                        }
                    }
                }
            }

            // WHATSAPP
            if (\in_array('whatsapp', $config['sources'])) {
                $orgData = \explode("\n", \file_get_contents('/opt/bulk/'.$dir.'/list.csv'));
                $data = \explode("\n", \file_get_contents('/opt/bulk/'.$dir.'/whatsapp/whatsapp_phone.csv'));

                $tmpData = [];

                foreach ($data as $key => $val) {
                    $tmp = \str_getcsv($val, ';');
                    $tmpData[$tmp[0]] = 1;
                }

                \print_r($tmpData);

                foreach ($orgData as $k => $v) {
                    if (isset($tmpData[$k + 1])) {
                        $orgData[$k] = $v.',"1"';
                    } else {
                        if (0 == $k) {
                            $orgData[$k] = $v.',"Whatsapp"';
                        } else {
                            $orgData[$k] = $v.',"0"';
                        }
                    }
                }
            }

            \file_put_contents('/opt/bulk/'.$dir.'/list.csv', \implode("\n", $orgData));
            \file_put_contents($file, '40');
        } elseif (40 == $status && \file_exists('/opt/bulk/'.$dir.'/the.conf')) {
            $config = getConfig('/opt/bulk/'.$dir.'/the.conf');

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('/opt/bulk/'.$dir.'/list.csv');
            $worksheet = $spreadsheet->getActiveSheet();
            $spreadsheet->getActiveSheet()->setAutoFilter(
                $spreadsheet->getActiveSheet()
                ->calculateWorksheetDimension()
            );

            foreach ($worksheet->getColumnIterator() as $column) {
                $worksheet->getStyle($column->getColumnIndex())->getNumberFormat()->setFormatCode('#');
                $worksheet->getStyle($column->getColumnIndex())->getAlignment()->setHorizontal('left');
                $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }

            // ///////////////////////////////////////////////////////////////////////////////

            if (\in_array('fns', $config['sources'])) {
                $fnsSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('/opt/bulk/'.$dir.'/fns/fns_inn.csv');
                $fnsWorksheet = $fnsSpreadsheet->getActiveSheet();
                $fnsWorksheet->setTitle('FNS');

                $fnsWorksheet->setAutoFilter(
                    $fnsWorksheet->calculateWorksheetDimension());

                foreach ($fnsWorksheet->getColumnIterator() as $column) {
                    $fnsWorksheet->getStyle($column->getColumnIndex())->getNumberFormat()->setFormatCode('#');
                    $fnsWorksheet->getStyle($column->getColumnIndex())->getAlignment()->setHorizontal('left');
                    $fnsWorksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                }

                $spreadsheet->addSheet($fnsWorksheet);
            }
            // ///////////////////////////////////////////////////////////////////////////////
            if (\in_array('bankrot', $config['sources'])) {
                $bankrotSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('/opt/bulk/'.$dir.'/bankrot/bankrot_person.csv');
                $bankrotWorksheet = $bankrotSpreadsheet->getActiveSheet();
                $bankrotWorksheet->setTitle('Bankrots');
                $bankrotSpreadsheet->getActiveSheet()->setAutoFilter(
                    $bankrotSpreadsheet->getActiveSheet()
                        ->calculateWorksheetDimension()
                );

                foreach ($bankrotWorksheet->getColumnIterator() as $column) {
                    $bankrotWorksheet->getStyle($column->getColumnIndex())->getNumberFormat()->setFormatCode('#');
                    $bankrotWorksheet->getStyle($column->getColumnIndex())->getAlignment()->setHorizontal('left');
                    $bankrotWorksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                }

                $spreadsheet->addSheet($bankrotWorksheet);
            }

            // /////////////////////////////////////////////////////////////////////////////////

            if (\in_array('fssp', $config['sources'])) {
                $fsspSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('/opt/bulk/'.$dir.'/fssp/fssp_person.csv');
                $fsspWorksheet = $fsspSpreadsheet->getActiveSheet();
                $fsspWorksheet->setTitle('FSSP');
                $fsspSpreadsheet->getActiveSheet()->setAutoFilter(
                    $fsspSpreadsheet->getActiveSheet()
                        ->calculateWorksheetDimension()
                );

                foreach ($fsspWorksheet->getColumnIterator() as $column) {
                    $fsspWorksheet->getStyle($column->getColumnIndex())->getNumberFormat()->setFormatCode('#');
                    $fsspWorksheet->getStyle($column->getColumnIndex())->getAlignment()->setHorizontal('left');
                    $fsspWorksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                }

                $spreadsheet->addSheet($fsspWorksheet);
            }

            // /////////////////////////////////////////////////////////////////////////////////

            if (\in_array('notariat', $config['sources'])) {
                $notariatSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('/opt/bulk/'.$dir.'/notariat/notariat_person.csv');
                $notariatWorksheet = $notariatSpreadsheet->getActiveSheet();
                $notariatWorksheet->setTitle('Notariat');
                $notariatSpreadsheet->getActiveSheet()->setAutoFilter(
                    $notariatSpreadsheet->getActiveSheet()
                        ->calculateWorksheetDimension()
                );

                foreach ($notariatWorksheet->getColumnIterator() as $column) {
                    $notariatWorksheet->getStyle($column->getColumnIndex())->getNumberFormat()->setFormatCode('#');
                    $notariatWorksheet->getStyle($column->getColumnIndex())->getAlignment()->setHorizontal('left');
                    $notariatWorksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                }

                $spreadsheet->addSheet($notariatWorksheet);
            }

            // /////////////////////////////////////////////////////////////////////////////////////

            if (\in_array('whatsapp', $config['sources'])) {
                $whatsappSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('/opt/bulk/'.$dir.'/whatsapp/whatsapp_person.csv');
                $whatsappWorksheet = $whatsappSpreadsheet->getActiveSheet();
                $whatsappWorksheet->setTitle('Whatsapp');
                $whatsappSpreadsheet->getActiveSheet()->setAutoFilter(
                    $whatsappSpreadsheet->getActiveSheet()
                        ->calculateWorksheetDimension()
                );

                foreach ($whatsappWorksheet->getColumnIterator() as $column) {
                    $whatsappWorksheet->getStyle($column->getColumnIndex())->getNumberFormat()->setFormatCode('#');
                    $whatsappWorksheet->getStyle($column->getColumnIndex())->getAlignment()->setHorizontal('left');
                    $whatsappWorksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                }

                $spreadsheet->addSheet($whatsappWorksheet);
            }

            // /////////////////////////////////////////////////////////////////////////////////////

            $writer = new Xlsx($spreadsheet);
            $writer->save('/opt/bulk/'.$dir.'/'.$dir.'.xlsx');

            \shell_exec('zip -j /opt/bulk/'.$dir.'/result.zip /opt/bulk/'.$dir.'/'.$dir.'.xlsx');

            $mysqli = \mysqli_connect($database['server'], $database['login'], $database['password'], $database['name']) || exit(\mysqli_errno($mysqli).': '.\mysqli_error($mysqli));
            if ($mysqli) {
                \mysqli_query($mysqli, 'Set character set utf8');
                \mysqli_query($mysqli, "Set names 'utf8'");
            } else {
                echo 'mysqli ek!!';
            }

            $pr = \count(\explode("\n", \file_get_contents('/opt/bulk/'.$dir.'/pre.csv'))) - 1;

            $mysqli->query("UPDATE isphere.Bulk SET status=1, processed_at=NOW(), processed_rows='".$pr."' WHERE id='".$dir."'");
            $mysqli->close();

            \unlink($file);
            $msg = 'Реестр '.$dir.' успешно обработан!';
            telegramMsg($msg);
        } else {
            echo "new situation?!\n";
        }
        //                break;
    }
    echo "cycle end\n";
    \sleep(5);
    //	exit;
    //	cycle($database);
}

cycle($database);
