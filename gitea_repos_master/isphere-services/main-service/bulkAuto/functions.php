<?php

use PhpOffice\PhpSpreadsheet\Writer\Csv;

function is_inn($inn)
{
    if (!\preg_match("/^\d{10}$/", $inn) && !\preg_match("/^\d{12}$/", $inn)) {
        return false;
    }
    if (\preg_match("/^(\d{10})$/", $inn, $m)) {
        $inn = $m[0];
        $code10 = (($inn[0] * 2 + $inn[1] * 4 + $inn[2] * 10 + $inn[3] * 3 + $inn[4] * 5 + $inn[5] * 9 + $inn[6] * 4 + $inn[7] * 6 + $inn[8] * 8) % 11) % 10;
        if ($code10 == $inn[9]) {
            return $inn;
        }
    } elseif (\preg_match("/^(\d{12})$/", $inn, $m)) {
        $inn = $m[0];
        $code11 = (($inn[0] * 7 + $inn[1] * 2 + $inn[2] * 4 + $inn[3] * 10 + $inn[4] * 3 + $inn[5] * 5 + $inn[6] * 9 + $inn[7] * 4 + $inn[8] * 6 + $inn[9] * 8) % 11) % 10;
        $code12 = (($inn[0] * 3 + $inn[1] * 7 + $inn[2] * 2 + $inn[3] * 4 + $inn[4] * 10 + $inn[5] * 3 + $inn[6] * 5 + $inn[7] * 9 + $inn[8] * 4 + $inn[9] * 6 + $inn[10] * 8) % 11) % 10;
        if ($code11 == $inn[10] && $code12 == $inn[11]) {
            return $inn;
        }
    }

    return false;
}

function is_birthDay($str)
{
    if (\preg_match("/^\d{5}$/", \trim($str))) {
        require_once 'vendor/autoload.php';
        $str = \date('d.m.Y', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp(\trim($str)));
    }

    if (\strtotime($str) && (\time() - \strtotime($str) > 567648000)) {
        return 1;
    }

    return 0;
}

function is_passport($str)
{
    if (\preg_match("/^\d{10}$/", \preg_replace("/\s+/", '', $str))) {
        return 1;
    }

    return 0;
}

function is_passportNumber($str)
{
    if (\preg_match("/^\d{6}$/", \preg_replace("/\s+/", '', $str))) {
        return 1;
    }

    return 0;
}

function is_passportSerial($str)
{
    if (\preg_match("/^\d{4}$/", \preg_replace("/\s+/", '', $str))) {
        return 1;
    }

    return 0;
}

function is_region($str)
{
    if (\preg_match("/^\d{,2}$/", \preg_replace("/\s+/", '', $str))) {
        return 1;
    }

    return 0;
}

function is_LastName($str)
{
    if (\preg_match('/^[а-яё]+(ова?|ева?|ёва?|ина?|ына?|их|ых|ский|цкий|ская|цкая|юк|ук|нко|дзе|швили|ян|ик|ко|ан)$/ui', \trim($str))) {
        return 1;
    }

    return 0;
}

function is_Patronymic($str)
{
    if (\preg_match('/^[а-яё]+(вич|вна|чна|ьич)$/ui', \trim($str))) {
        return 1;
    }

    return 0;
}

function is_FirstName($str)
{
    if (\preg_match('/^[а-яё]+$/ui', \trim($str)) && !is_LastName($str) && !is_Patronymic($str)) {
        return 1;
    }

    return 0;
}

function is_FIO($str)
{
    $str = \preg_replace("/\s+/", ' ', \trim($str));
    if (\preg_match('/^[а-яё]+(ова?|ева?|ёва?|ина?|ына?|их|ых|ский|цкий|ская|цкая|юк|ук|нко|дзе|швили|ян|ик|ко|ан) [а-яё]+ [а-яё]+(вич|вна|чна|ьич)$/ui', \trim($str))) {
        return 1;
    }

    return 0;
}

function is_IOF($str)
{
    $str = \preg_replace("/\s+/", ' ', \trim($str));
    if (\preg_match('/^[а-яё]+ [а-яё]+(вич|вна|чна|ьич) [а-яё]+(ова?|ева?|ёва?|ина?|ына?|их|ых|ский|цкий|ская|цкая|юк|ук|нко|дзе|швили|ян|ик|ко|ан)$/ui', \trim($str))) {
        return 1;
    }

    return 0;
}

function is_phone($str)
{
    $plus = '+' == \substr(\trim($str), 0, 1);
    $str = \preg_replace("/\D/", '', \trim($str));
    if (!$plus && (11 == \strlen($str)) && ('8' == \substr($str, 0, 1))) {
        $str = '7'.\substr($str, 1);
    }
    if (!$plus && (10 == \strlen($str))) {
        $str = '7'.$str;
    }

    return \strlen($str) >= 11 ? 1 : 0;
}

function preWork($workDir)
{
    if (\is_dir($workDir)) {
        //		foreach($sources as $source){
        //			@mkdir('data/'.$source.'/'.$workDir, 0777);
        //			@mkdir('data/'.$source.'/'.$workDir.'/data', 0777);
        //		}
        \chdir($workDir);
        $xlsxFiles = \glob('request.*');
        //		print_r($xlsxFiles);

        //		$xlsx = '';
        $xlsx = $xlsxFiles[0];

        //			if(count($xlsxFiles)){
        //				$xlsx = $xlsxFiles[0];
        //			}else{
        //				$xlsxFiles = glob("*.csv");
        //				$xlsx = $xlsxFiles[0];
        //			}

        if (!$xlsx) {
            echo "Нет файла!!!  Аварийный выход...\n";
            exit;
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($xlsx);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        while (isset($rows[0]) && \is_array($rows[0]) && !\preg_match("/\d{4}/", \implode('', $rows[0]))) { // to kill headers && empty lines
            $rows = \array_slice($rows, 1);
        }

        $typesArray = [];
        $typesKeys = ['lastName', 'firstName', 'patronymic', 'fio', 'iof', 'bDate', 'region_id', 'serial', 'number', 'passport', 'inn', 'phone'];

        foreach ($rows as $row) {
            foreach ($row as $key => $item) {
                $item = \trim($item);
                if (is_region($item)) {
                    @$typesArray[$key]['region_id']++;
                } elseif (is_passportSerial($item)) {
                    @$typesArray[$key]['serial']++;
                } elseif (is_passportNumber($item)) {
                    @$typesArray[$key]['number']++;
                } elseif (is_passport($item)) {
                    @$typesArray[$key]['passport']++;
                } elseif (is_birthDay($item)) {
                    @$typesArray[$key]['bDate']++;
                } elseif (is_Patronymic($item)) {
                    @$typesArray[$key]['patronymic']++;
                } elseif (is_LastName($item)) {
                    @$typesArray[$key]['lastName']++;
                } elseif (is_FirstName($item)) {
                    @$typesArray[$key]['firstName']++;
                } elseif (is_FIO($item)) {
                    @$typesArray[$key]['fio']++;
                } elseif (is_IOF($item)) {
                    @$typesArray[$key]['iof']++;
                } elseif (is_inn($item)) {
                    @$typesArray[$key]['inn']++;
                } elseif (is_phone($item)) {
                    @$typesArray[$key]['phone']++;
                } else {
                    @$typesArray[$key]['trash']++;
                }
            }
        }
        //		print_r($typesArray);
        //		echo "<br>\n";
        foreach ($typesArray as $key => $val) {
            \arsort($val);
            //			echo "<br>\n";
            //			print_r($val);
            $typesArray[$key] = \array_slice($val, 0, 1);
        }
        //		echo "<br>\n";
        //		print_r($typesArray);
        $columns = [];
        $check = [];
        foreach ($typesKeys as $tk) {
            foreach ($typesArray as $k => $arr) {
                if (isset($arr[$tk])) {
                    if (!isset($columns[$tk])) {
                        $columns[$tk] = $k;
                        $check[$tk] = $arr[$tk];
                    } else {
                        if ($arr[$tk] > $check[$tk]) {
                            $columns[$tk] = $k;
                            $check[$tk] = $arr[$tk];
                        }
                    }
                }
            }
        }
        //		print_r($columns);
        $columns['id'] = 0;
        foreach ($columns as $cKey => $cVal) {
            if (0 == $cVal && 'id' != $cKey) {
                if ('fio' == $cKey || 'iof' == $cKey || 'lastName' == $cKey || 'firstName' == $cKey) {
                    unset($columns['id']);
                } else {
                    unset($columns[$cKey]);
                }
            }
        }

        \file_put_contents($workDir.'fields.txt', \json_encode($columns));
        $writer = new Csv($spreadsheet);
        $writer->save($workDir.'pre.csv');

        $forSample = \array_slice(\explode("\n", \file_get_contents($workDir.'pre.csv')), 5, 10);
        $sample = [];
        foreach ($forSample as $string) {
            if (\preg_match_all("/\d{1,2}\/\d{1,2}\/\d{4}/", $string, $matches)) {
                foreach ($matches[0] as $v) {
                    $string = \str_replace($v, \date('d.m.Y', \strtotime($v)), $string);
                }
            }
            $sample[] = \str_getcsv(\trim($string));
        }

        //		$to_return = array_slice($rows, 0, 10);
        //		print_r($to_return);

        // $content = file_get_contents($workDir.'pre.csv');
        // if(preg_match("/\d{1,2}\/\d{1,2}\/\d{4}/", $content)){
        //	//echo "Excel style dates\n";
        //			$sample = array();
        //	$data = explode("\n", file_get_contents($workDir.'.csv'));
        //			foreach($to_return as $string){
        //				if(preg_match_all( "/\d{1,2}\/\d{1,2}\/\d{4}/", $string, $matches)){
        //					foreach($matches[0] as $v){
        //						$string = str_replace($v, date('d.m.Y',strtotime($v)), $string);
        //					}
        //				}
        //				$sample[] = $string;
        //				//file_put_contents($workDir.'pre.csv', $newContent);
        //			}
        // }
        // print_r($sample);
        return $sample;
    } else {
        // echo "goal doesnt exists!!\n";
        return false;
    }
}

function _process($info, $config, $source, $key, $dir)
{
    if ('fns' == $source) {
        $source = 'fns_inn';
    }

    $xml = "
<Request>
        <UserID>{$config['id']}</UserID>
        <Password>{$config['passwd']}</Password>
        <requestId>".$info['id'].'</requestId>
        <requestType>bulk</requestType>
        <sources>'.$source.'</sources>'.
    (!isset($info['last_name']) && !isset($info['number']) && !isset($info['inn']) ? '' : '
        <PersonReq>'.
    (!isset($info['date']) ? '' : "
            <first>{$info['first_name']}</first>
            <middle>{$info['patronymic']}</middle>
            <paternal>{$info['last_name']}</paternal>"
    ).(!isset($info['date']) || !$info['date'] ? '' : "
            <birthDt>{$info['date']}</birthDt>"
    ).(!isset($info['number']) || !$info['number'] ? '' : "
            <passport_series>{$info['serial']}</passport_series>
            <passport_number>{$info['number']}</passport_number>"
    ).(!isset($info['issue_date']) || !$info['issue_date'] ? '' : "
            <issueDate>{$info['issue_date']}</issueDate>"
    ).(!isset($info['region_id']) || !$info['region_id'] ? '' : "
            <region_id>{$info['region_id']}</region_id>"
    ).(!isset($info['inn']) || !$info['inn'] ? '' : "
            <inn>{$info['inn']}</inn>"
    ).'
        </PersonReq>'
    ).(!isset($info['mobile_phone']) || !$info['mobile_phone'] ? '' : "
        <PhoneReq>
            <phone>{$info['mobile_phone']}</phone>
        </PhoneReq>"
    ).(!isset($info['home_phone']) || !$info['home_phone'] ? '' : "
        <PhoneReq>
            <phone>{$info['home_phone']}</phone>
        </PhoneReq>"
    ).(!isset($info['work_phone']) || !$info['work_phone'] ? '' : "
        <PhoneReq>
            <phone>{$info['work_phone']}</phone>
        </PhoneReq>"
    ).(!isset($info['additional_phone']) || !$info['additional_phone'] ? '' : "
        <PhoneReq>
            <phone>{$info['additional_phone']}</phone>
        </PhoneReq>"
    ).(!isset($info['email']) || !$info['email'] ? '' : "
        <EmailReq>
            <email>{$info['email']}</email>
        </EmailReq>"
    ).'
</Request>';
    $site = 'https://i-sphere.ru/2.00/' == $config['serviceurl'] ? 1 : 2;
    \file_put_contents('/opt/forReq/'.$site.'/'.$dir.'___'.$source.'___'.($key + 1).'.qwe', $xml);

    return true;
}

function getConf()
{
    $files = \glob('*.conf');
    if (1 != \count($files)) {
        return false;
    }

    return $files[0];
}

function getConfig($confFile)
{
    require $confFile;
    $config = [];
    $config['id'] = $id;
    $config['passwd'] = $passwd;
    $config['serviceurl'] = $serviceurl;
    $config['sources'] = \explode(',', $sources);

    return $config;
}

function telegramMsg($msg): void
{
    $serviceurl = 'https://api.telegram.org/bot2103347962:AAHMdZY-Bh6ELR-NB7qOapnnD7sbh2c3bsQ/sendMessage?chat_id=-1001662664995';

    $ch = \curl_init();
    \curl_setopt($ch, \CURLOPT_URL, $serviceurl);
    \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
    \curl_setopt($ch, \CURLOPT_TIMEOUT, 10);
    \curl_setopt($ch, \CURLOPT_HEADER, 0);
    \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, 0);
    \curl_setopt($ch, \CURLOPT_SSL_VERIFYHOST, 0);
    \curl_setopt($ch, \CURLOPT_POSTFIELDS, 'text='.\urlencode($msg));
    \curl_setopt($ch, \CURLOPT_POST, 1);

    $data = \curl_exec($ch);

    $answer = $data;

    \curl_close($ch);
}

function checkSource($source, $fields = [])
{
    //	echo $source."<br>";
    //	print_r($fields);
    if ('fssp' == $source || 'notariat' == $source) {
        if (!isset($fields['bDate'])) {
            return false;
        } else {
            if (!isset($fields['fio']) && !isset($fields['iof']) && (!isset($fields['lastName']) || !isset($fields['lastName']) || !isset($fields['patronymic']))) {
                return false;
            } else {
                return true;
            }
        }
    } elseif ('bankrot' == $source) {
        if (isset($fields['inn'])) {
            return true;
        } else {
            if (!isset($fields['bDate'])) {
                return false;
            } else {
                if (!isset($fields['fio']) && !isset($fields['iof']) && (!isset($fields['lastName']) || !isset($fields['lastName']) || !isset($fields['patronymic']))) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    } elseif ('fns' == $source) {
        if (!isset($fields['bDate'])) {
            return false;
        } else {
            if (!isset($fields['fio']) && !isset($fields['iof']) && (!isset($fields['lastName']) || !isset($fields['lastName']) || !isset($fields['patronymic']))) {
                return false;
            } else {
                if (isset($fields['passport']) || (isset($fields['serial']) && isset($fields['number']))) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    } elseif ('whatsapp' == $source || 'viber' == $source || 'hlr' == $source || 'smsc' == $source || 'rossvyaz' == $source ||
        'getcontact' == $source || 'truecaller' == $source || 'numbuster' == $source || 'emt' == $source || 'simpler' == $source || 'callapp' == $source) {
        if (!isset($fields['phone'])) {
            return false;
        } else {
            return true;
        }
    } else {
        return false;
    }
}

function doResult($workDir): void
{
    $orgDir = __DIR__;
    \chdir($workDir);

    $dirs = \glob('*', \GLOB_ONLYDIR);

    $files = [];
    $fields = [];

    foreach ($dirs as $dir) {
        $file = $dir.'/fResult.txt';
        if (!\file_exists($file) || !\filesize($file)) {
            $fileerr = $dir.'/fResult.err';
            if (!\file_exists($fileerr) || !\filesize($fileerr)) {
                echo "$file or $fileerr not found\n";
                exit;
            }
            $file = $fileerr;
        }
        $row = (int) $dir;

        $files[$row] = $file;

        $text = \file_get_contents($file);
        $text = \strtr($text, ['&bull;' => '', '&deg;' => '']);
        $xml = new SimpleXMLElement($text);

        foreach ($xml->Source as $source) {
            $sourceName = (string) $source['checktype'];
            if ('bankrot_inn' == $sourceName) {
                $sourceName = 'bankrot_person';
            }
            if (!isset($fields[$sourceName])) {
                $fields[$sourceName] = ['row' => '', 'ID' => ''];
            }

            foreach ($source->Record as $record) {
                foreach ($record->Field as $field) {
                    $fieldName = (string) $field->FieldName;
                    if (!isset($fields[$sourceName][$fieldName])) {
                        $fields[$sourceName][$fieldName] = '';
                    }
                }
            }

            if (isset($source->Error)) {
                $fields[$sourceName]['error'] = '';
            }
        }
    }

    //    if (!is_dir('results')) mkdir('results',0777,true);
    foreach ($fields as $sourceName => $sourceFields) {
        \file_put_contents($sourceName.'.csv', '"'.\implode('";"', \array_keys($sourceFields))."\"\n");
        $content[$sourceName] = '';
        $rows[$sourceName] = 0;
    }

    \ksort($files);
    foreach ($files as $row => $file) {
        $text = \file_get_contents($file);
        $text = \strtr($text, ['&bull;' => '', '&deg;' => '']);
        $xml = new SimpleXMLElement($text);

        foreach ($xml->Source as $source) {
            $sourceName = (string) $source['checktype'];
            if ('bankrot_inn' == $sourceName) {
                $sourceName = 'bankrot_person';
            }
            foreach ($source->Record as $record) {
                $values = $fields[$sourceName];
                $values['row'] = $row;
                $values['ID'] = $xml->Request->requestId;
                foreach ($record->Field as $field) {
                    $fieldName = (string) $field->FieldName;
                    $values[$fieldName] .= ($values[$fieldName] ? ',' : '').\trim((string) $field->FieldValue);
                }
                foreach ($values as $fieldName => $value) {
                    $values[$fieldName] = '"'.\strtr(\trim(\preg_replace("/\s+/si", ' ', $value)), ['"' => '""']).'"';
                }
                $content[$sourceName] .= \implode(';', $values)."\n";
            }
            if (isset($source->Error)) {
                $values = $fields[$sourceName];
                $values['row'] = $row;
                $values['ID'] = $xml->Request->requestId;
                $values['error'] = $source->Error;
                foreach ($values as $fieldName => $value) {
                    $values[$fieldName] = '"'.\strtr(\trim(\preg_replace("/\s+/si", ' ', $value)), ['"' => '""']).'"';
                }
                $content[$sourceName] .= \implode(';', $values)."\n";
            }
            if (++$rows[$sourceName] >= 1000/* || $row==$maxrow */) {
                \file_put_contents($sourceName.'.csv', $content[$sourceName], \FILE_APPEND);
                $content[$sourceName] = '';
                $rows[$sourceName] = 0;
            }
        }
    }
    foreach ($fields as $sourceName => $sourceFields) {
        \file_put_contents($sourceName.'.csv', $content[$sourceName], \FILE_APPEND);
    }

    // chdir('results');
    // shell_exec('tar --gzip -c -f results.tar.gz *.csv');
    \chdir($orgDir);

    return;
}

function numFormNum($array)
{
    $result = [];

    if (\preg_match("/\"\;\"/", $array[0])) {
        $separator = ';';
    } else {
        $separator = '';
    }
    foreach ($array as $str) {
        if ('' != $separator) {
            $tmpArr = \str_getcsv(\trim($str), ';');
        } else {
            $tmpArr = \str_getcsv(\trim($str));
        }
        foreach ($tmpArr as $k => $v) {
            if (\preg_match("/^\d{12,}$/", \trim($v)) && !\in_array($k, $result)) {
                $result[] = $k;
            }
        }
    }

    return $result;
}
