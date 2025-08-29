<?php

class GKSPlugin implements PluginInterface
{
    private $names = array (
        'I. ВНЕОБОРОТНЫЕ АКТИВЫ' => array(
            'Нематериальные активы' => array('f1_1110'),
            'Результаты исследований и разработок' => array('f1_1120'),
            'Нематериальные поисковые активы' => array('f1_1130'),
            'Материальные поисковые активы' => array('f1_1140'),
            'Основные средства' => array('f1_1150'),
            'Доходные вложения в материальные ценности' => array('f1_1160'),
            'Финансовые вложения' => array('f1_1170'),
            'Отложенные налоговые активы' => array('f1_1180'),
            'Прочие внеоборотные активы' => array('f1_1190'),
            'Итого по разделу I' => array('f1_1100', 'Итого внеоборотные активы', 'Итого внеоборотные активы (I)'),
        ),
        'II. ОБОРОТНЫЕ АКТИВЫ' => array(
            'Запасы' => array('f1_1210'),
            'Налог на добавленную стоимость по приобретенным ценностям' => array('f1_1220', 'НДС по приобретенным ценностям'),
            'Дебиторская задолженность' => array('f1_1230'),
            'Финансовые вложения (за исключением денежных эквивалентов)' => array('f1_1240', 'Финансовые вложения'),
            'Денежные средства и денежные эквиваленты' => array('f1_1250'),
            'Прочие оборотные активы' => array('f1_1260'),
            'Итого по разделу II' => array('f1_1200', 'Итого оборотные активы', 'Итого оборотные активы (II)'),
            'БАЛАНС' => array('f1_1600', 'Итого активы', 'Итого активы (баланс)'),
        ),
        'III. КАПИТАЛ И РЕЗЕРВЫ' => array(
            'Уставный капитал (складочный капитал, уставный фонд, вклады товарищей)' => array('f1_1310', 'Уставный капитал'),
            'Собственные акции, выкупленные у акционеров' => array('f1_1320', 'Собственные акции'),
            'Переоценка внеоборотных активов' => array('f1_1340'),
            'Добавочный капитал (без переоценки)' => array('f1_1350', 'Добавочный капитал'),
            'Резервный капитал' => array('f1_1360'),
            'Нераспределенная прибыль (непокрытый убыток)' => array('f1_1370', 'Нераспределенная прибыль'),
            'Итого по разделу III' => array('f1_1300', 'Итого собственный капитал', 'Итого собственный капитал (III)'),
        ),
        'IV. ДОЛГОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА' => array(
            'Заемные средства' => array('f1_1410'),
            'Отложенные налоговые обязательства' => array('f1_1420'),
            'Оценочные обязательства' => array('f1_1430'),
            'Прочие обязательства' => array('f1_1450'),
            'Итого по разделу IV' => array('f1_1400', 'Итого долгосрочные обязательства', 'Итого долгосрочные обязательства (IV)'),
        ),
        'V. КРАТКОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА' => array(
            'Заемные средства' => array('f1_1510'),
            'Кредиторская задолженность' => array('f1_1520'),
            'Доходы будущих периодов' => array('f1_1530'),
            'Оценочные обязательства' => array('f1_1540'),
            'Прочие обязательства' => array('f1_1550'),
            'Итого по разделу V' => array('f1_1500', 'Итого краткосрочные обязательства', 'Итого краткосрочные обязательства (V)'),
            'БАЛАНС' => array('f1_1700', 'Итого пассивы', 'Итого пассивы (баланс)'),
        ),
        'Отчет о финансовых результатах' => array(
            'Выручка' => array('f2_2110'),
            'Себестоимость продаж' => array('f2_2120'),
            'Валовая прибыль (убыток)' => array('f2_2100', 'Валовая прибыль'),
            'Коммерческие расходы' => array('f2_2210'),
            'Управленческие расходы' => array('f2_2220'),
            'Прибыль (убыток) от продаж' => array('f2_2200', 'Прибыль от продаж'),
            'Доходы от участия в других организациях' => array('f2_2310'),
            'Проценты к получению' => array('f2_2320'),
            'Проценты к уплате' => array('f2_2330'),
            'Прочие доходы' => array('f2_2340'),
            'Прочие расходы' => array('f2_2350'),
            'Прибыль (убыток) до налогообложения' => array('f2_2300', 'Прибыль до налогообложения'),
            'Текущий налог на прибыль' => array('f2_2410'),
            'Постоянные налоговые обязательства (активы)' => array('f2_2420', 'Постоянные налоговые обязательства'),
            'Изменение отложенных налоговых обязательств' => array('f2_2430'),
            'Изменение отложенных налоговых активов' => array('f2_2450'),
            'Прочее' => array('f2_2460'),
            'Чистая прибыль (убыток)' => array('f2_2400', 'Чистая прибыль'),
        ),
        'СПРАВОЧНО' => array(
            'Результат от переоценки внеоборотных активов, не включаемый в чистую прибыль (убыток) периода' => array('f2_2510', 'Результат от переоценки внеоборотных активов'),
            'Результат от прочих операций, не включаемый в чистую прибыль (убыток) периода' => array('f2_2520', 'Результат от прочих операций'),
            'Совокупный финансовый результат периода' => array('f2_2500', 'Совокупный финансовый результат'),
        ),
/*
                          47 => array('f3_3100', 'Величина капитала на начало года', 'Величина капитала на 31 декабря предыдущего года'),
                          48 => array('f3_3120', 'Себестоимость продаж', 'Себестоимость продаж'),
                          49 => array('f3_3100', 'Валовая прибыль', 'Валовая прибыль (убыток)'),
                          50 => array('f3_3210', 'Коммерческие расходы', 'Коммерческие расходы'),
                          51 => array('f3_3220', 'Управленческие расходы', 'Управленческие расходы'),
                          52 => array('f3_3200', 'Прибыль от продаж', 'Прибыль (убыток) от продаж'),
                          53 => array('f3_3320', 'Проценты к получению', 'Проценты к получению'),
                          54 => array('f3_3330', 'Проценты к уплате', 'Проценты к уплате'),
                          55 => array('f3_3310', 'Доходы от участия в других организациях', 'Доходы от участия в других организациях'),
                          56 => array('f3_3340', 'Прочие доходы', 'Прочие доходы'),
                          57 => array('f3_3350', 'Прочие расходы', 'Прочие расходы'),
                          58 => array('f3_3300', 'Прибыль до налогообложения', 'Прибыль (убыток) до налогообложения'),
                          59 => array('f3_3450', 'Изменение отложенных налоговых активов', 'Изменение отложенных налоговых активов'),
                          60 => array('f3_3430', 'Изменение отложенных налоговых обязательств', 'Изменение отложенных налоговых обязательств'),
                          61 => array('f3_3410', 'Текущий налог на прибыль', 'Текущий налог на прибыль'),
                          62 => array('f3_3400', 'Чистая прибыль', 'Чистая прибыль (убыток)'),
*/
    );

    public function getName()
    {
        return 'gks';
    }

    public function getTitle()
    {
        return 'Поиск в Росстате';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['inn']) && !isset($initData['ogrn'])) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ИНН или ОГРН)');

            return false;
        }
/*
        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен');
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        if(isset($initData['ogrn']) && !isset($swapData['ogrn'])) {
            $swapData['ogrn']=$initData['ogrn'];
            $rContext->setSwapData($swapData);
        }

        $ch = $rContext->getCurlHandler();

        if (!isset($swapData['ogrn'])) {
            $url = 'https://zachestnyibiznes.ru/search?query='.$initData['inn'];
        } elseif (!isset($swapData['okpo'])) {
            $url = 'http://91.203.194.58:8033/?link='.urlencode('https://zachestnyibiznes.ru/company/ul/'.$swapData['ogrn']);
        } else {
            $url = 'https://zachestnyibiznes.ru/company/balance?okpo='.$swapData['okpo'];
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest'));
//            curl_setopt($ch, CURLOPT_COOKIE, 'advanced-zchb=1cr5r50hnup1brurpt4poda2fh;');
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = ($swapData['iteration']>5) && curl_error($rContext->getCurlHandler());
        if(!$error) {
            $srccontent = curl_multi_getcontent($rContext->getCurlHandler());
            $content = str_replace("<div class=\"helloparsers\">По данным портала <a href=\"https://zachestnyibiznes.ru\">ЗАЧЕСТНЫЙБИЗНЕС</a></div>","",$srccontent);
            $content = preg_replace("/<div class=\"helloparsers\"><a[^>]*>По данным портала <\/a><a href=\"https:\/\/zachestnyibiznes.ru\">ЗАЧЕСТНЫЙБИЗНЕС<\/a><\/div>/","",$content);
//            if (preg_match("/\$\.each\(\$\(\"\.([\d]+)\"\)\, function\(\)\{/sim",$content,$matches)) {
            if (preg_match_all("/each[^\"]+\"\.([0-9]+)\"/",$content,$matches)) {
                foreach($matches[1] as $class) {
                    $content = preg_replace_callback("/<span class=\"".$class."\">[^<]+<\/span>/",
                        create_function(
                            '$matches',
                            'return base64_decode(strip_tags($matches[0]));'
                        ),
                    $content);
                }
            }

            if (strlen($content)==0 || strpos($content,"Checking your browser before accessing")) {
                $rContext->setError("Ошибка обработки запроса");
                $rContext->setFinished();
                return false;
            }

            if (!isset($swapData['ogrn'])) {
                file_put_contents('./logs/gks/gks_search_'.time().'.html',$content);
                $resultData = new ResultDataList();

                if(preg_match("/<tbody>(.*?)<\/tbody>/sim", $content, $matches)){
                    $parts = preg_split("/<\/tr>/",$matches[1]);
                    array_pop($parts);
                    foreach ($parts as $i => $dataPart) {
                        $data = array();
                        if(preg_match("/<a itemprop=\"legalName\" href='\/company\/ul\/([0-9]+)_([0-9]+)[^>]+>([^<]+)<br[^<]+<span[^>]+>([^<]+)<\/span><\/a>/", $dataPart, $matches)){
                            $swapData['ogrn'] = trim($matches[1]);
                            $rContext->setSwapData($swapData);
                            return true;
                        }
                    }
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif (!isset($swapData['okpo'])) {
                file_put_contents('./logs/gks/gks_'.time().'.html',$content);
                $resultData = new ResultDataList();

                if(preg_match("/<span id=\"okpo\">([0-9]+)<\/span>/", $content, $matches)){
                    $swapData['okpo'] = trim($matches[1]);
                    $rContext->setSwapData($swapData);
                    return true;
                }

                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } else {
                file_put_contents('./logs/gks/gks_okpo_'.time().'.html',$content);
                $resultData = new ResultDataList();
                $totals = array();

                $rows = preg_split("/<tr[^>]*>/",$content);
                array_shift($rows);
                $section = '';
                foreach ($rows as $i => $row) {
                    if(preg_match("/<td colspan=[^>]+>(.*?)<\/td>/", $row, $matches)){
                        $lines = preg_split("/<br>/",$matches[1]);
                        $section = trim(strip_tags($lines[sizeof($lines)-1]));
//                        echo "$i: Раздел: $section\n";
                    }
                    if(preg_match_all("/<td>([^<]+)<\/td>/", $row, $matches) && sizeof($matches[1])==1){
                        $title = $matches[1][0];
                        if (strlen($title) && $title[0]=='_') {
                            $title = base64_decode($title);                                
                        }
//                        echo "$i: $title\n";
                        if(preg_match_all("/<td data-th=\"([\d]{4})[^>]+>([^<]+)<\/td>/", $row, $matches) && sizeof($matches[1])>=5){
                            foreach ($matches[1] as $j => $year) {
                                $val = $matches[2][$j];
                                if (strlen($val) && $val[0]=='_') {
                                    $val = base64_decode($val);                                
                                }
                                $val = strtr($val,array(' '=>'',','=>'.'));
                                $totals[$year][$section][$title] = $val;
//                                echo "$year: $val\n";
                            }
                        }
                    }
                }
                
                foreach($totals as $year => $sections) {
                    if ($year) {
                        $data = array();
                        $data['year'] = new ResultDataField('string', 'year', $year, 'Год', 'Год');
//                        echo "Год: $year\n";
                        foreach($sections as $section => $titles) {
                            foreach($titles as $title => $val) {
//                                echo $title.': '.$val."\n";
                                if (isset($this->names[$section][$title])) {
                                    $field = $this->names[$section][$title];
                                    if ($val=='0') $val='0.00';
                                    if ($val && ($val!=='-')) {
                                        $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $val, isset($field[1])?$field[1]:$title, isset($field[2])?$field[2]:$title);
//                                        echo $field[0].' '.$title.': '.$val."\n";
                                    }
                                } elseif ($year=2017) {
//                                    echo 'Не найдено: '.$section.': '.$title.': '.$val."\n";
                                }
                            }
                        }
                        if(sizeof($data)>1)
                            $resultData->addResult($data);
                    }
                }

                $rContext->setResultData($resultData);
                $rContext->setFinished();
            }
            $rContext->setSwapData($swapData);
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>5)
            $error='Превышено количество попыток получения ответа';

        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        return true;
    }
}

?>