<?php

class MosRuPlugin implements PluginInterface
{
    private $names = array (
                           'kolvl' => array('owners', 'Количество владельцев', 'Количество владельцев'),
                           'marka' => array('mark', 'Марка', 'Марка'),
                           'yearavto' => array('year', 'Год выпуска', 'Год выпуска'),
                           'tiptc' => array('cartype', 'Тип кузова', 'Тип кузова'),
                           'categ' => array('category', 'Категория ТС', 'Категория ТС'),
                           'color' => array('color', 'Цвет', 'Цвет'),
                           'ls' => array('powerls', 'Мощность, л.с.', 'Мощность, л.с.'),
                           'kwt' => array('powerkwt', 'Мощность, кВт', 'Мощность, кВт'),
                           'v3' => array('volume', 'Объем двигателя, куб.см.', 'Объем двигателя, куб.см.'),
                           'massa' => array('weight', 'Полная масса транспортного средства', 'Полная масса транспортного средства'),
                           'rulright' => array('steering_wheel_right', 'Правый руль', 'Правый руль'),
                           'ecologclass' => array('ecoclass', 'Экологический стандарт', 'Экологический стандарт'),
                           'statevyvozname' => array('country', 'Страна ввоза', 'Страна ввоза'),

                           'LicenseDate' => array('LicenseDate', 'Дата выдачи', 'Дата выдачи'),
                           'LicenseNum' => array('LicenseNum', 'Регистрационный номер разрешения', 'Регистрационный номер разрешения'),
                           'Name' => array('Name', 'Наименование перевозчика', 'Наименование перевозчика'),
                           'OgrnNum' => array('OGRN', 'ОГРН перевозчика', 'ОГРН перевозчика'),
                           'Inn' => array('INN', 'ИНН перевозчика', 'ИНН перевозчика'),
                           'Brand' => array('Mark', 'Марка автомобиля', 'Марка автомобиля'),
                           'Model' => array('Model', 'Модель автомобиля', 'Модель автомобиля'),
                           'RegNum' => array('RegNum', 'Государственный регистрационный знак', 'Государственный регистрационный знак'),
                           'Year' => array('Year', 'Год выпуска автомобиля', 'Год выпуска автомобиля'),
                           'BlankNo' => array('Blank', 'Бланк разрешения', 'Бланк разрешения'),
                           'FullName' => array('FullName', 'Полное наименование перевозчика', 'Полное наименование перевозчика'),
                           'ValidityDate' => array('LicensePeriod', 'Срок действия разрешения', 'Срок действия разрешения'),
                           'EditDate' => array('LastChanged', 'Дата обновления', 'Дата обновления'),
                           'Info' => array('Info', 'Сведения о разрешении', 'Сведения о разрешении'),
                           'Region' => array('Region', 'Регион', 'Регион'),
                           'Condition' => array('LicenseStatus', 'Статус разрешения', 'Статус разрешения'),
                           'Color' => array('Color', 'Цвет автомобиля', 'Цвет автомобиля'),
                           'YellowColor' => array('YellowColor', 'Желтое такси', 'Желтое такси'),
                           'YellowRegNum' => array('YellowRegNum', 'Желтый государственный знак', 'Желтый государственный знак'),
                           'STSNum' => array('CTC', 'Свидетельство о регистрации ТС', 'Свидетельство о регистрации ТС'),
                           'STSDate' => array('CTCDate', 'Дата выдачи СТС', 'Дата выдачи СТС'),
//                           'OwnershipType' => array('OwnershipType', '', ''),
//                           'OwnershipNum' => array('OwnershipNum', '', ''),
//                           'OwnershipDate' => array('OwnershipDate', '', ''),
                           '' => array('', '', ''),
    );

    public function getName()
    {
        return 'avtokod';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск штрафов в АвтоКод',
            'avtokod_driver' => 'Автокод - проверка водительского удостоверения',
            'avtokod_history' => 'Автокод - история автомобиля',
            'avtokod_fines' => 'Автокод - поиск штрафов',
            'avtokod_pts' => 'Автокод - проверка ПТС',
            'avtokod_taxi' => 'Автокод - разрешение на работу такси',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск штрафов через АвтоКод';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=30 ORDER BY lasttime limit 1");
//        $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 ORDER BY lasttime limit 1");

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;

                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),statuscode='used',used=ifnull(used,0)+1 WHERE id=".$sessionData->id);

//                $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],8);

        if (($checktype=='fines') && !isset($initData['ctc']) && !isset($initData['driver_number']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (св-во о регистрации ТС или водительское удостоверение)');

            return false;
        }

        if (($checktype=='history') && !isset($initData['vin']) && (!isset($initData['regnum']) || !isset($initData['ctc'])))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (VIN или гос.номер+СТС)');

            return false;
        }

        if (($checktype=='pts') && !isset($initData['pts']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ПТС)');

            return false;
        }

        if (($checktype=='taxi') && !isset($initData['regnum'])/* && !isset($initData['inn'])*/)
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (гос.номер)');
//            $rContext->setError('Указаны не все обязательные параметры (гос.номер или ИНН)');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

/*
        if(!isset($swapData['cookies'])){
            $swapData['cookies'] = time().'_'.rand(100,1000);
            $rContext->setSwapData($swapData);
	}
*/
        $ch = $rContext->getCurlHandler();

        if(!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();

            if(!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration']>=10)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(3);
                }
                return false;
            }

            $rContext->setSwapData($swapData);
        }

        $host = 'https://www.mos.ru';
        $header = array();
        if ($checktype=='fines') {
            $url = $host.'/pgu/common/ajax/index.php';
            $params = array(
                'ajaxModule' => 'fines',
                'ajaxAction' => 'searchFines',
                'vu' => '',
                'sts[]' => '',
            );
            if( isset($initData['ctc'])) {
                $params['sts[]'] = $initData['ctc'];
            } else {
                $params['vu'] = $initData['driver_number'].'643';
            }
        } elseif ($checktype=='history') {
            $params = array(
                'Sts' => isset($initData['ctc'])?$initData['ctc']:'',
            );
            $url = $host.'/services/autohistory/api/AutoHistory/';
            if (isset($initData['vin'])) {
                $params['Vin'] = $initData['vin'];
                $url .= isset($initData['ctc'])?'GetByVin':'GetShortAutoHistoryByVin';
            } elseif (isset($initData['regnum'])) {
                $params['licensePlate'] = $initData['regnum'];
                $url .= 'GetByLicensePlate';
            }
            $url .= '?'.http_build_query($params);
            $params = false;
            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        } elseif ($checktype=='pts') {
            $url = $host.'/altmosmvc/api/v1/vehicle/checkpts/';
            $params = json_encode(array(
                'pts' => $initData['pts'],
            ),JSON_UNESCAPED_UNICODE);
/*
            $header = array(
                'Content-Type: application/json; charset=utf-8',
                'X-Requested-With: XMLHttpRequest',
            );
*/
        } elseif ($checktype=='taxi') {
            $params = array(
                'RegNum' => isset($initData['regnum'])?$initData['regnum']:'',
//                'FullName' => '',
//                'Region' => '',
            );
            $url = $host.'/altmosmvc/api/v1/taxi/getInfo/?'.http_build_query($params);
            $params = false;
            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        if (isset($swapData['session']) && $swapData['session']->proxy) {
            curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
//                curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
            }
        }
        if ($params) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//        curl_setopt($ch, CURLOPT_COOKIEFILE, './logs/cookies/'.$swapData['cookies'].'_cookies.txt');
//        curl_setopt($ch, CURLOPT_COOKIEJAR, './logs/cookies/'.$swapData['cookies'].'_cookies.txt');

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],8);

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $curlError = curl_error($rContext->getCurlHandler());

        if($curlError && $swapData['iteration']>10)
        {
            $rContext->setFinished();
            $rContext->setError($curlError==''?'Превышено количество попыток получения ответа':$curlError);

            return false;
        }

        $rContext->setSwapData($swapData);

        $content = curl_multi_getcontent($rContext->getCurlHandler());
        if (!empty(trim($content))) file_put_contents('./logs/avtokod/mos_'.$checktype.'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
        $start = strpos($content,'{');
        $content = trim(substr($content,$start));
        $res = json_decode($content, true);

        if (!$res) {
//            echo "$content\n";
        } else {
//            var_dump($res); echo "\n";
        }

        if ($checktype=='fines') {
            if (isset($res['status']) && ($res['status']=='SEARCH_STARTED' || $res['status']=='SEARCH_IN_PROGRESS')) {
                $rContext->setSleep(1);
            } elseif (isset($res['status']) && ($res['status']=='SEARCH_ENDED') && isset($res['data'])) {
                $resp = $res['data'];

                $resultData = new ResultDataList();
                if (isset($resp['payed_fines'])) {
                    $fines_map = array();
                    $paid_amount = 0;
                    $unpaid_amount = 0;
                    $paid_sum = 0;
                    $unpaid_sum = 0;
                    $fines = array_merge($resp['payed_fines'],$resp['not_payed_fines']);
                    foreach ($fines as $fine) {
                        $data = array();
                       if (isset($fine['Name']))
                           $data['DAP'] = new ResultDataField('string', 'DAP', $fine['Name'], 'Постановление', 'Постановление административного органа');
                       if (isset($fine['DAP']))
                           $data['DAPNumber'] = new ResultDataField('string', 'DAPNumber', $fine['DAP'], 'Номер постановления', 'Номер постановления административного органа');
                       if (isset($fine['DatDAP']))
                           $data['DAPDate'] = new ResultDataField('string', 'DAPDate', $fine['DatDAP'], 'Дата постановления', 'Дата постановления административного органа');
                       if (isset($fine['FineSum']))
                           $data['Sum'] = new ResultDataField('float', 'Sum', $fine['FineSum'], 'Сумма', 'Сумма');
                       if (isset($fine['isPayed'])) {
                           $data['Status'] = new ResultDataField('string', 'Status', $fine['isPayed'] ? 'оплачен':'не оплачен', 'Статус', 'Статус');
                           if ($fine['isPayed']) {
                               $paid_amount++;
                               $paid_sum += $fine['FineSum'];
                           } else {
                               $unpaid_amount++;
                               $unpaid_sum += $fine['FineSum'];
                           }
                       }
                       if (isset($fine['OdpsName']))
                           $data['OdpsName'] = new ResultDataField('string', 'OdpsName', $fine['OdpsName'], 'Орган власти, выявивший нарушение', 'Орган власти, выявивший нарушение');
                       if (isset($fine['OdpsAddr']))
                           $data['OdpsAddr'] = new ResultDataField('string', 'OdpsAddr', $fine['OdpsAddr'], 'Адрес органа власти', 'Адрес органа власти');
                       if (isset($fine['StAP']))
                           $data['StAP'] = new ResultDataField('string', 'StAP', $fine['StAP'], 'Cостав правонарушения', 'Статья КоАП или закона субъекта РФ, состав правонарушения');
                       if (isset($fine['StDAP']))
                           $data['StAP'] = new ResultDataField('string', 'StAP', $fine['StDAP'], 'Cостав правонарушения', 'Статья КоАП или закона субъекта РФ, состав правонарушения');
                       if (isset($fine['MestoDAP']))
                           $data['MestoDAP'] = new ResultDataField('string', 'MestoDAP', $fine['MestoDAP'], 'Место составления документа', 'Место составления документа');
                       if (isset($fine['DatNar']))
                           $data['DatNar'] = new ResultDataField('string', 'DatNar', $fine['DatNar'], 'Дата и время нарушения', 'Дата и время нарушения');
                       if (isset($fine['MestoNar']))
                           $data['MestoNar'] = new ResultDataField('string', 'MestoNar', $fine['MestoNar'], 'Место нарушения', 'Место нарушения');
                       if (isset($fine['FIONarush']))
                           $data['FIONarush'] = new ResultDataField('string', 'FIONarush', $fine['FIONarush'], 'Нарушитель', 'Нарушитель');
                       if (isset($fine['GRZNarush']))
                           $data['GRZNarush'] = new ResultDataField('string', 'GRZNarush', $fine['GRZNarush'], 'Транспортное средство', 'Транспортное средство');
                       if (isset($fine['GRZNar']))
                           $data['GRZNarush'] = new ResultDataField('string', 'GRZNarush', $fine['GRZNar'], 'Транспортное средство', 'Транспортное средство');
                       if (isset($fine['Vu']))
                           $data['VuNarush'] = new ResultDataField('string', 'VuNarush', $fine['Vu'], 'Водительское удостоверение', 'Водительское удостоверение');
                       if (isset($fine['Latitude']) && isset($fine['Longitude']) && $fine['Latitude'] && $fine['Longitude']) {
                           $map = array(array('coords' => array(+strtr($fine['Latitude'],array(','=>'.')),+strtr($fine['Longitude'],array(','=>'.'))), 'text' => $fine['DatNar'].' '.$fine['MestoNar']));
                           $fines_map = array_merge($fines_map,$map);
//                           $data['Location'] = new ResultDataField('map','Location',strtr(json_encode($map,JSON_UNESCAPED_UNICODE),array("},{"=>"},\n{")),'Местоположение','Местоположение');
                       }
                       if (isset($fine['PhotoUrl']))
                           $data['Photo'] = new ResultDataField('image', 'Photo', $fine['PhotoUrl'], 'Фото', 'Фото');
//                       if (isset($fine['HasPhoto']) && $fine['HasPhoto'] && isset($fine['UID'])) {
//                       }
                       $data['Type'] = new ResultDataField('string', 'Type', 'fine', 'Тип записи', 'Тип записи');
                       $resultData->addResult($data);
                    }

                    if ($paid_amount || $unpaid_amount) {
                       $data = array();
                       $data['PaidAmount'] = new ResultDataField('string', 'PaidAmount', $paid_amount, 'Количество оплаченных штрафов', 'Количество оплаченных штрафов');
                       $data['PaidSum'] = new ResultDataField('float', 'PaidSum', $paid_sum, 'Сумма оплаченных штрафов', 'Сумма оплаченных штрафов');
                       $data['UnpaidAmount'] = new ResultDataField('string', 'UnpaidAmount', $unpaid_amount, 'Количество неоплаченных штрафов', 'Количество неоплаченных штрафов');
                       $data['UnpaidSum'] = new ResultDataField('float', 'UnpaidSum', $unpaid_sum, 'Сумма неоплаченных штрафов', 'Сумма неоплаченных штрафов');
                       if (sizeof($fines_map))
                           $data['Map'] = new ResultDataField('map','Map',strtr(json_encode($fines_map,JSON_UNESCAPED_UNICODE),array("},{"=>"},\n{")),'Карта нарушений','Карта нарушений');
                       $data['Type'] = new ResultDataField('string', 'Type', 'total', 'Тип записи', 'Тип записи');
                       $resultData->addResult($data);
                    }

                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } elseif (isset($res['errorMessage']) && $res['errorMessage']) {
                    $error = trim($res['errorMessage']);
                    $rContext->setFinished();
                    $rContext->setError($error);
                } 
            } elseif (isset($res['errorMessage']) && $res['errorMessage']) {
                $error = trim($res['errorMessage']);
                $rContext->setFinished();
                $rContext->setError($error);
            } elseif($swapData['iteration']>3) {
                $rContext->setFinished();
                $rContext->setError("Некорректный ответ сервиса");
                file_put_contents('./logs/avtokod/mos_'.$checktype.'_err_'.time().'.txt',$content);
            }
        } elseif ($checktype=='history') {
            $data = array();
            $resultData = new ResultDataList();

            if (isset($res['model'])) {
                $data = array();
                $data['result'] = new ResultDataField('string', 'Result', 'VIN найден', 'Результат', 'Результат');
                $data['resultcode'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                $data['report'] = new ResultDataField('string', 'Report', 'SHORT', 'Тип отчета', 'Тип отчета');
                $data['recordtype'] = new ResultDataField('string', 'RecordType', 'result', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);

                $data = array();
                $data['model'] = new ResultDataField('string', 'Model', $res['model'], 'Модель', 'Модель');
                $data['owners'] = new ResultDataField('string', 'Owners', $res['ownersCount'], 'Кол-во владельцев', 'Кол-во владельцев');
                $data['accidents'] = new ResultDataField('string', 'Accidents', $res['trafficAccidentsCount'], 'Кол-во ДТП', 'Кол-во ДТП');
                $data['damages'] = new ResultDataField('string', 'Damages', $res['insuranceTrafficAccidentsCount'], 'Кол-во страховых случаев', 'Кол-во страховых случаев');
                $data['recordtype'] = new ResultDataField('string', 'RecordType', 'vehicle', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);

                if (isset($res['mileage'])) {
                    $data = array();
                    $data['mileage'] = new ResultDataField('string', 'Mileage', $res['mileage'], 'Пробег', 'Пробег');
                    $data['recordtype'] = new ResultDataField('string', 'RecordType', 'mileage', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }

                if (isset($res['isWanted']) || isset($res['isInPledge'])) {
                    $data = array();
                    $has_restr = 0;
                    if (isset($res['isWanted'])) {
                        $has_restr += ($res['isWanted']==false) ? 0 : 1;
                        $data["InSearch"] = new ResultDataField('string', "InSearch", $res['isWanted']?'да':'нет', "Нахождение в розыске", "Нахождение в розыске");
                    }
                    if (isset($res['isInPledge'])) {
                        $has_restr += ($res['isInPledge']==false) ? 0 : 1;
                        $data["InPledge"] = new ResultDataField('string', "InPledge", $res['isInPledge']?'да':'нет', "Нахождение в залоге", "Нахождение в залоге");
                    }
                    $data['hasrestrictions'] = new ResultDataField('string', 'HasRestrictions', $has_restr, 'Наличие ограничений', 'Наличие ограничений');
                    $data['recordtype'] = new ResultDataField('string', 'RecordType', 'restrictions', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }

                if (isset($res['wasUsedInTaxi']) || isset($res['wasUsedAsRouteVehicle']) || isset($res['wasUsedAsFreightTransport']) || isset($res['wasUsedAsSpecialTransport'])) {
                    $data = array();
                    $comm_usage = 0;
                    if (isset($res['wasUsedInTaxi'])) {
                        $comm_usage += ($res['wasUsedInTaxi']==false) ? 0 : 1;
                        $data["UsedAsTaxi"] = new ResultDataField('string', "UsedAsTaxi", $res['wasUsedInTaxi']?'да':'нет', "Использование в качестве такси", "Использование в качестве такси");
                    }
                    if (isset($res['wasUsedAsRouteVehicle'])) {
                        $comm_usage += ($res['wasUsedAsRouteVehicle']==false) ? 0 : 1;
                        $data["UsedAsBus"] = new ResultDataField('string', "UsedAsBus", $res['wasUsedAsRouteVehicle']?'да':'нет', "Использование в качестве маршрутного транспорта", "Использование в качестве маршрутного транспорта");
                    }
                    if (isset($res['wasUsedAsFreightTransport'])) {
                        $comm_usage += ($res['wasUsedAsFreightTransport']==false) ? 0 : 1;
                        $data["UsedAsTruck"] = new ResultDataField('string', "UsedAsTruck", $res['wasUsedAsFreightTransport']?'да':'нет', "Использование в качестве грузового транспорта", "Использование в качестве грузового транспорта");
                    }
                    if (isset($res['wasUsedAsSpecialTransport'])) {
                        $comm_usage += ($res['wasUsedAsSpecialTransport']==false) ? 0 : 1;
                        $data["UsedAsSpecial"] = new ResultDataField('string', "UsedAsSpecial", $res['wasUsedAsSpecialTransport']?'да':'нет', "Использование в качестве специального транспорта (городские службы, аварийные службы и прочее)", "Использование в качестве специального транспорта (городские службы, аварийные службы и прочее)");
                    }
                    $data['commercialusage'] = new ResultDataField('string', 'CommercialUsage', $comm_usage, 'Коммерческое использование', 'Коммерческое использование');
                    $data['recordtype'] = new ResultDataField('string', 'RecordType', 'commercialusage', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif (isset($res['statusCode']) && $res['statusCode']=='NotFound') {
                $data = array();
                $data['result'] = new ResultDataField('string', 'Result', 'Информация о ТС не найдена', 'Результат', 'Результат');
                $data['resultcode'] = new ResultDataField('string', 'ResultCode', 'NOT_FOUND', 'Код результата', 'Код результата');
                $data['report'] = new ResultDataField('string', 'Report', 'SHORT', 'Тип отчета', 'Тип отчета');
                $data['recordtype'] = new ResultDataField('string', 'RecordType', 'result', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif (isset($res['statusCode']) && $res['statusCode']=='Error' && isset($res['message']) && strpos($res['message'],'Fault')) {
                if ($swapData['iteration']>30) {
                    $rContext->setFinished();
                    $rContext->setError("Превышено количество попыток получения ответа");
                    $rContext->setSleep(1);
                }
                return false;
            }
            if (isset($res['commonInfo'])) {
                if (isset($res['commonInfo']['model'])) {
                    $data = array();
                    $info = $res['commonInfo'];
                    $data['result'] = new ResultDataField('string', 'Result', 'СТС действительно и соответствует VIN', 'Результат', 'Результат');
                    $data['resultcode'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                    $data['report'] = new ResultDataField('string', 'Report', 'FULL', 'Тип отчета', 'Тип отчета');
                    $data['recordtype'] = new ResultDataField('string', 'RecordType', 'result', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);

                    $data = array();
                    $data['category'] = new ResultDataField('string', 'Category', $info['vehicleCategory'], 'Категория', 'Категория');
                    $data['type'] = new ResultDataField('string', 'Type', $info['carBodyType'], 'Тип', 'Тип');
                    $data['model'] = new ResultDataField('string', 'Model', $info['model'], 'Модель', 'Модель');
                    $data['year'] = new ResultDataField('string', 'Year', $info['issueYear'], 'Год выпуска', 'Год выпуска');
                    $data['color'] = new ResultDataField('string', 'Color', $info['carBodyColor'], 'Цвет', 'Цвет');
                    $data['volume'] = new ResultDataField('string', 'Volume', $info['sweptVolume'], 'Объем двигателя, куб.см', 'Объем двигателя, куб.см');
                    $data['powerhp'] = new ResultDataField('string', 'PowerHP', $info['enginePowerHp'], 'Мощность двигателя, л.с.', 'Мощность двигателя, л.с.');
                    $data['powerkwt'] = new ResultDataField('string', 'PowerKWT', $info['enginePowerKw'], 'Мощность двигателя, кВт', 'Мощность двигателя, кВт');
                    $data['ecoclass'] = new ResultDataField('string', 'EcoClass', $info['emissionStandard'], 'Экологический класс', 'Экологический класс');
                    $data['weight'] = new ResultDataField('string', 'Weight', $info['maximumLadenWeightKg'], 'Разрешенная масса, кг', 'Разрешенная масса, кг');
                    $data['country'] = new ResultDataField('string', 'Country', $info['exportCountry'], 'Страна вывоза', 'Страна вывоза');
                    $data['owners'] = new ResultDataField('string', 'Owners', $info['numberOfOwners'], 'Кол-во владельцев', 'Кол-во владельцев');
                    if (isset($res['trafficAccidentHistory']['trafficAccidents'])) {
                        $data['accidents'] = new ResultDataField('string', 'Accidents', sizeof($res['trafficAccidentHistory']['trafficAccidents']), 'Кол-во ДТП', 'Кол-во ДТП');
                    }
                    if (isset($res['insuranceDamageInfo']['insuranceDamages'])) {
                        $data['damages'] = new ResultDataField('string', 'Damages', sizeof($res['insuranceDamageInfo']['insuranceDamages']), 'Кол-во страховых случаев', 'Кол-во страховых случаев');
                    }
                    $data['recordtype'] = new ResultDataField('string', 'RecordType', 'vehicle', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                } else {
                    $data = array();
                    $data['result'] = new ResultDataField('string', 'Result', 'Информация о ТС не найдена. На данный момент информация предоставляется по ТС, зарегистрированным на территории Москвы и Московской области, и только по действующим свидетельствам ТС.', 'Результат', 'Результат');
                    $data['resultcode'] = new ResultDataField('string', 'ResultCode', 'NOT_FOUND', 'Код результата', 'Код результата');
                    $data['report'] = new ResultDataField('string', 'Report', 'FULL', 'Тип отчета', 'Тип отчета');
                    $data['recordtype'] = new ResultDataField('string', 'RecordType', 'result', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }

                if (isset($res['ownershipHistory']['vehicleOwners'])) {
//                    $ownerstext = '';
                    $part = $res['ownershipHistory']['vehicleOwners'];
//                    $data['owners'] = new ResultDataField('string', 'Owners', sizeof($part), 'Кол-во владельцев', 'Кол-во владельцев');
                    foreach ($part as $owner) {
//                        $ownerstext .= ($ownerstext?', ':'').date('d.m.Y',strtotime($owner['ownershipBeginningPeriod'])).'-'.($owner['ownershipEndPeriod']?date('d.m.Y',strtotime($owner['ownershipEndPeriod'])):'н/в').' '.$owner['type'];
                        $data = array();
                        $data['startdate'] = new ResultDataField('string', 'StartDate', date('d.m.Y',strtotime($owner['ownershipBeginningPeriod'])), 'Дата начала владения', 'Дата начала владения');
                        $data['enddate'] = new ResultDataField('string', 'EndDate', $owner['ownershipEndPeriod']?date('d.m.Y',strtotime($owner['ownershipEndPeriod'])):'н/в', 'Дата окончания владения', 'Дата окончания владения');
                        $data['ownertype'] = new ResultDataField('string', 'OwnerType', $owner['type'], 'Тип собственника', 'Тип собственника');
                        $data['recordtype'] = new ResultDataField('string', 'RecordType', 'history', 'Тип записи', 'Тип записи');
                        $resultData->addResult($data);
                    }
//                    $data['ownerstext'] = new ResultDataField('string', 'OwnersText', $ownerstext, 'История владения', 'История владения');
//                    if (isset($res['ownershipHistory']['ownershipComment']))
//                        $data['ownerscomment'] = new ResultDataField('string', 'OwnersComment', $res['ownershipHistory']['ownershipComment'], 'Комментарий к истории владения', 'Комментарий к истории владения');
                }

                if (isset($res['inspectionInfo']['inspections'])) {
//                    $inspectionstext = '';
                    $part = $res['inspectionInfo']['inspections'];
//                    $data['inspections'] = new ResultDataField('string', 'Inspections', sizeof($part), 'Кол-во техосмотров', 'Кол-во техосмотров');
                    foreach ($part as $inspection) {
//                        $inspectionstext .= ($inspectionstext?', ':'').date('d.m.Y',strtotime($inspection['dateOfInspection']));
                        $data = array();
                        $data['startdate'] = new ResultDataField('string', 'InspectionDate', date('d.m.Y',strtotime($inspection['dateOfInspection'])), 'Дата диагностики', 'Дата диагностики');
                        if (isset($inspection['diagnosticCardExpiryDate']))
                            $data['enddate'] = new ResultDataField('string', 'InspectionEndDate', date('d.m.Y',strtotime($inspection['diagnosticCardExpiryDate'])), 'Действительно до', 'Действительно до');
//                            $inspectionstext .= '-'.date('d.m.Y',strtotime($inspection['diagnosticCardExpiryDate']));
                        if (isset($inspection['placeOfInspection']) && $inspection['placeOfInspection'])
                            $data['place'] = new ResultDataField('string', 'InspectionPlace', $inspection['placeOfInspection'], 'Место диагностики', 'Место диагностики');
//                            $inspectionstext .= ' '.$inspection['placeOfInspection'];
                        if (isset($inspection['diagnosticCardNumber']) && $inspection['diagnosticCardNumber'])
                            $data['number'] = new ResultDataField('string', 'InspectionNumber', $inspection['diagnosticCardNumber'], 'Номер диагностической карты', 'Номер диагностической карты');
//                            $inspectionstext .= ', карта № '.$inspection['diagnosticCardNumber'];
                        $data['recordtype'] = new ResultDataField('string', 'RecordType', 'inspection', 'Тип записи', 'Тип записи');
                        $resultData->addResult($data);
                    }
//                    $data['inspectionstext'] = new ResultDataField('string', 'InspectionsText', $inspectionstext, 'Техосмотры', 'Техосмотры');
                }

                if (isset($res['trafficAccidentHistory']['trafficAccidents'])) {
//                    $accidentstext = '';
                    $part = $res['trafficAccidentHistory']['trafficAccidents'];
//                    $data['accidents'] = new ResultDataField('string', 'Accidents', sizeof($part), 'Кол-во ДТП', 'Кол-во ДТП');
                    foreach ($part as $accident) {
                        $data = array();
//                        $accidentstext .= ($accidentstext?', ':'').date('d.m.Y',strtotime($accident['date'])).' '.$accident['regionCode'].' '.$accident['type'].' '.$accident['damage'];
                        $data['date'] = new ResultDataField('string', 'AccidentDate', date('d.m.Y',strtotime($accident['date'])), 'Дата ДТП', 'Дата ДТП');
                        $data['region'] = new ResultDataField('string', 'AccidentRegion', $accident['regionCode'], 'Регион ДТП', 'Регион ДТП');
                        $data['type'] = new ResultDataField('string', 'AccidentType', $accident['type'], 'Тип ДТП', 'Тип ДТП');
                        $data['damage'] = new ResultDataField('string', 'AccidentDamage', $accident['damage'], 'Повреждения при ДТП', 'Повреждения при ДТП');
                        $data['department'] = new ResultDataField('string', 'AccidentDepartment', $accident['department'], 'Подразделение ГИБДД', 'Подразделение ГИБДД');
                        $data['recordtype'] = new ResultDataField('string', 'RecordType', 'accident', 'Тип записи', 'Тип записи');
                        $resultData->addResult($data);
                    }
//                    $data['accidentstext'] = new ResultDataField('string', 'AccidentsText', $accidentstext, 'История ДТП', 'История ДТП');
//                    if (isset($res['trafficAccidentHistory']['trafficAccidentsComment']))
//                        $data['accidentcomment'] = new ResultDataField('string', 'AccidentComment', $res['trafficAccidentHistory']['trafficAccidentsComment'], 'Комментарий к истории ДТП', 'Комментарий к истории ДТП');
                }

                if (isset($res['insuranceDamageInfo']['insuranceDamages'])) {
                    $part = $res['insuranceDamageInfo']['insuranceDamages'];
//                    $data['damages'] = new ResultDataField('string', 'Damages', sizeof($part), 'Кол-во страховых случаев', 'Кол-во страховых случаев');
                    foreach ($part as $damage) {
                        $data = array();
                        $data['date'] = new ResultDataField('string', 'DamageDate', date('d.m.Y',strtotime($damage['date'])), 'Дата страхового случая', 'Дата страхового случая');
                        $data['insurancetype'] = new ResultDataField('string', 'InsuranceType', $damage['insuranceType'], 'Вид страхования', 'Вид страхования');
                        $data['accident'] = new ResultDataField('string', 'DamageAccident', $damage['accident'], 'Страховой случай', 'Страховой случай');
                        $data['damage'] = new ResultDataField('string', 'Damage', $damage['damage'], 'Полученный ущерб', 'Полученный ущерб');
                        $data['recordtype'] = new ResultDataField('string', 'RecordType', 'damage', 'Тип записи', 'Тип записи');
                        $resultData->addResult($data);
                    }
                }

                if (isset($res['mileageInfo']['mileages'])) {
//                    $mileagestext = '';
                    $part = $res['mileageInfo']['mileages'];
                    foreach ($part as $mileage) {
                        if (isset($mileage['originalMileage'])) {
                            $data = array();
//                            $mileagestext .= ($mileagestext?', ':'').date('d.m.Y',strtotime($mileage['originalMileage']['date'])).' '.$mileage['originalMileage']['value'];
                            $data['date'] = new ResultDataField('string', 'MileageDate', date('d.m.Y',strtotime($mileage['originalMileage']['date'])), 'Дата фиксации пробега', 'Дата фиксации пробега');
                            $data['mileage'] = new ResultDataField('string', 'Mileage', $mileage['originalMileage']['value'], 'Пробег', 'Пробег');
                            $data['recordtype'] = new ResultDataField('string', 'RecordType', 'mileage', 'Тип записи', 'Тип записи');
                            $resultData->addResult($data);
                        }
                    }
//                    $data['mileagestext'] = new ResultDataField('string', 'MileagesText', $mileagestext, 'Сведения о пробеге', 'Сведения о пробеге');
                }

                if (isset($res['restrictionInfo']['restrictions'])) {
                    $part = $res['restrictionInfo']['restrictions'];
                    $restr_names = array(
                        "Запрет на снятие с учета" => "StrikeOffRegisterRestricted",
                        "Запрет на регистрационные действия и прохождение ТО" => "RegisterAndRestricted",
                        "Утилизация (для транспорта не старше 5 лет)" => "Utilization",
                        "Аннулирование регистрации" => "RegistrationCancelled",
                        "Нахождение в розыске" => "InSearch",
                    );
                    $has_restr = 0;
                    $data = array();
                    foreach ($part as $restr) {
                        $has_restr += ($restr['result']==false) ? 0 : 1;
                        if (array_key_exists($restr['name'],$restr_names)) {
                          $name = $restr_names[$restr['name']];
                          $data[$name] = new ResultDataField('string', $name, $restr['result']?'да':'нет', $restr['name'], $restr['name']);
                        }
                    }
                    if (isset($res['isInPledge'])) {
                        $has_restr += ($res['isInPledge']==false) ? 0 : 1;
                        $data["InPledge"] = new ResultDataField('string', "InPledge", $res['isInPledge']?'да':'нет', "Нахождение в залоге", "Нахождение в залоге");
                    }
                    $data['hasrestrictions'] = new ResultDataField('string', 'HasRestrictions', $has_restr, 'Наличие ограничений', 'Наличие ограничений');
                    $data['recordtype'] = new ResultDataField('string', 'RecordType', 'restrictions', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }

                if (isset($res['commericalUsageInfo']['commercialUsageList'])) {
                    $part = $res['commericalUsageInfo']['commercialUsageList'];
                    $comm_names = array(
                        "Использование в качестве такси" => "UsedAsTaxi",
                        "Использование в качестве маршрутного транспорта" => "UsedAsBus",
                        "Использование в качестве грузового транспорта" => "UsedAsTruck",
                        "Использование в качестве специального транспорта (городские службы, аварийные службы и прочее)" => "UsedAsSpecial",
                        "Прочие виды" => "UsedAsOther",
                    );
                    $comm_usage = 0;
                    $data = array();
                    foreach ($part as $comm) {
                        $comm_usage += ($comm['result']==false) ? 0 : 1;
                        if (array_key_exists($comm['type'],$comm_names)) {
                          $name = $comm_names[$comm['type']];
                          $data[$name] = new ResultDataField('string', $name, $comm['result']?'да':'нет', $comm['type'], $comm['type']);
                        }
                    }
                    $data['commercialusage'] = new ResultDataField('string', 'CommercialUsage', $comm_usage, 'Коммерческое использование', 'Коммерческое использование');
                    $data['recordtype'] = new ResultDataField('string', 'RecordType', 'commercialusage', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif (isset($res['result']) && $res['result']=='IncorrectCaptcha') {
                unset($swapData['session']);
            } elseif (strpos($content,'Unauthorized')) {
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='unauthorized',endtime=now() WHERE id=".$swapData['session']->id);
                unset($swapData['session']);
            } else {
                if ($swapData['iteration']>3) {
                    $rContext->setFinished();
                    $rContext->setError("Некорректный ответ сервиса");
                    file_put_contents('./logs/avtokod/mos_'.$checktype.'_err_'.time().'.txt',$content);
                }
                return true;
            }
        } elseif ($checktype=='pts') {
            if (is_array($res) && isset($res['statuscode']) && ($res['statuscode']==200)) {
                $resultData = new ResultDataList();
                $data = array();
                foreach($res as $i => $val) {
                    if ($val && isset($this->names[$i])) {
                        $field = $this->names[$i];
                        $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $val, $field[1], $field[2]);
                    }
                }
                if (sizeof($data)) $resultData->addResult($data);
                $rContext->setFinished();
                $rContext->setResultData($resultData);
            } elseif (isset($res['message']) && $res['message']) {
                $error = trim($res['message']);
                $rContext->setFinished();
                $rContext->setError($error);
            } elseif($swapData['iteration']>3) {
                $rContext->setFinished();
                $rContext->setError("Некорректный ответ сервиса");
                file_put_contents('./logs/avtokod/mos_'.$checktype.'_err_'.time().'.txt',$content);
            }
        } elseif ($checktype=='taxi') {
            $resultData = new ResultDataList();
            if (is_array($res) && isset($res['Infos']) && is_array($res['Infos'])) {
                foreach($res['Infos'] as $info) {
                    $data = array();
                    foreach($info as $i => $val) {
                        if ($val && isset($this->names[$i])) {
                            $field = $this->names[$i];
                            $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $val, $field[1], $field[2]);
                        }
                    }
                    if (sizeof($data)) $resultData->addResult($data);
                }
                $rContext->setFinished();
                $rContext->setResultData($resultData);
            } elseif($swapData['iteration']>3) {
                $rContext->setFinished();
                $rContext->setError("Некорректный ответ сервиса");
                file_put_contents('./logs/avtokod/mos_'.$checktype.'_err_'.time().'.txt',$content);
            }
        }
        $rContext->setSwapData($swapData);

        return false;
    }
}

?>