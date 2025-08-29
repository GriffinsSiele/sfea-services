<?php

class AvtoKodPlugin implements PluginInterface
{
    public function getName()
    {
        return 'avtokod';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Проверка через АвтоКод',
            'avtokod_driver' => 'Автокод - проверка водительского удостоверения',
            'avtokod_history' => 'Автокод - история автомобиля',
            'avtokod_fines' => 'Автокод - поиск штрафов',
            'avtokod_status' => 'Автокод - статус регистрации',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Проверка через АвтоКод';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],8);

        if($checktype=='history' && !isset($initData['vin'])) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (VIN)');

            return false;
        }

        if($checktype=='driver' && (!isset($initData['driver_number']) || !isset($initData['driver_date']))) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (водительское удостоверение и дата выдачи)');

            return false;
        }

        if($checktype=='fines' && !isset($initData['ctc']) && !isset($initData['driver_number']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (св-во о регистрации ТС или водительское удостоверение)');

            return false;
        }

        if($checktype=='status' && !isset($initData['ctc']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (св-во о регистрации ТС)');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $pl = array(
            'history'=>'avtokod',
            'fines'=>'avtokodFines',
            'driver'=>'avtokodVu',
            'status'=>'avtokodSR',
        );
        $swapData['pl'] = $pl[$checktype];
        $rContext->setSwapData($swapData);

        $url = 'https://i-sphere.ru/pl_asynch/'.$pl[$checktype].'/';

        if (!isset($swapData['avtokod_id'])) {
            $url .= '_init?';
            if($checktype=='history') {
                $url .= 'vin=' . $initData['vin'] . (isset($initData['ctc']) ? '&ctc=' . urlencode($initData['ctc']) : '');
            }
            if($checktype=='fines') {
                if( isset($initData['ctc'])) {
                    $url .= 'ctc=' . urlencode($initData['ctc']);
                } else {
                    $url .= 'vu=' . urlencode($initData['driver_number']); //.(isset($initData['driver_date']) ? '&vDate='.$initData['driver_date'] : '');
                }
            }
            if($checktype=='driver') {
                $url .= 'vu=' . urlencode($initData['driver_number']) . '&vDate=' . date('d.m.Y',strtotime($initData['driver_date']));
            }
            if($checktype=='status') {
                $url .= 'ctc=' . urlencode($initData['ctc']);
            }
        } else {
            $url .= '_view?id='. $swapData['avtokod_id'];
        }
//        print "$url\n";
        curl_setopt($ch, CURLOPT_URL, $url);

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

        if($curlError && $swapData['iteration']>5)
        {
            $rContext->setFinished();
            $rContext->setError($curlError==''?'Превышено количество попыток получения ответа':$curlError);

            return false;
        }

        $rContext->setSwapData($swapData);

        $content = curl_multi_getcontent($rContext->getCurlHandler());
//        if (!empty(trim($content))) file_put_contents('./logs/avtokod/avtokod_'.(isset($swapData['avtokod_id'])?'':'start_').time().'.txt',$content);
        $res = json_decode($content, true);

        if (!isset($swapData['avtokod_id'])) {
            if (isset($res['id'])) {
                $swapData['avtokod_id'] = $res['id'];
                $rContext->setSwapData($swapData);
                $rContext->setSleep(10);
                return true;
            } elseif (strpos($content,'Значение поля')!==false || strpos($content,'обязательное поле')!==false) {
                $rContext->setFinished();
                $rContext->setError($content);
                return true;
            } else {
                if($swapData['iteration']>10) {
//                    print($content."\r\n");
                    $rContext->setFinished();
                    $rContext->setError("Ошибка при выполнении запроса");
                    return true;
                }
                return false;
            }
        } elseif ($res && isset($res['StateCode']) && ($res['StateCode']=='0')) {
            $val = json_decode($res['Value'], true);
//            file_put_contents('./logs/avtokod/avtokod_val_'.time().'.txt',$res['Value']);
            if ($val && isset($val['response']))
                $resp = $val['response'];
            else
                $resp = $val;

            $resultData = new ResultDataList();
            $data = array();

            if ($checktype=='history' && array_key_exists('statusCode',$resp) && $resp['statusCode']) {
                $data['result'] = new ResultDataField('string', 'Result', 'Информация о ТС не найдена. На данный момент информация предоставляется по ТС, зарегистрированным на территории Москвы и Московской области, и только по действующим свидетельствам ТС.', 'Результат', 'Результат');
                $data['resultcode'] = new ResultDataField('string', 'ResultCode', 'NOT_FOUND', 'Код результата', 'Код результата');
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            }

            if ($checktype=='history' && array_key_exists('statusCode',$res) && $res['statusCode']) {
                $data['result'] = new ResultDataField('string', 'Result', 'Информация о ТС не найдена. На данный момент информация предоставляется по ТС, зарегистрированным на территории Москвы и Московской области, и только по действующим свидетельствам ТС.', 'Результат', 'Результат');
                $data['resultcode'] = new ResultDataField('string', 'ResultCode', 'NOT_FOUND', 'Код результата', 'Код результата');
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            }

            if ($checktype=='history' && isset($resp['commonInfo'])) {
                if (isset($resp['commonInfo']['model'])) {
                    $info = $resp['commonInfo'];
                    $data['result'] = new ResultDataField('string', 'Result', 'СТС действительно', 'Результат', 'Результат');
                    $data['resultcode'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                    $data['report'] = new ResultDataField('string', 'Report', 'FULL', 'Тип отчета', 'Тип отчета');
                    global $serviceurl;
//                    $data['pdf'] = new ResultDataField('url', 'PDF', $serviceurl.'logs/avtokod/'.$swapData['avtokod_id'].'.pdf', 'PDF', 'PDF');
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
                } else {
                    $data['result'] = new ResultDataField('string', 'Result', 'Информация о ТС не найдена. На данный момент информация предоставляется по ТС, зарегистрированным на территории Москвы и Московской области, и только по действующим свидетельствам ТС.', 'Результат', 'Результат');
                    $data['resultcode'] = new ResultDataField('string', 'ResultCode', 'NOT_FOUND', 'Код результата', 'Код результата');
                    $data['report'] = new ResultDataField('string', 'Report', 'FULL', 'Тип отчета', 'Тип отчета');
                }
            } elseif ($checktype=='history' && isset($resp['mark'])) {
                $data['report'] = new ResultDataField('string', 'Report', 'SHORT', 'Тип отчета', 'Тип отчета');
                $data['model'] = new ResultDataField('string', 'Model', $resp['mark'], 'Модель', 'Модель');
                if (isset($resp['year']))
                    $data['year'] = new ResultDataField('string', 'Year', $resp['year'], 'Год выпуска', 'Год выпуска');
                if (isset($resp['displacement']))
                    $data['volume'] = new ResultDataField('string', 'Volume', $resp['displacement'], 'Объем двигателя, куб.см', 'Объем двигателя, куб.см');
                if (isset($resp['horsepower']))
                    $data['powerhp'] = new ResultDataField('string', 'PowerHP', $resp['horsepower'], 'Мощность двигателя, л.с.', 'Мощность двигателя, л.с.');
                if (isset($resp['ownersCount']))
                    $data['owners'] = new ResultDataField('string', 'Owners', $resp['ownersCount'], 'Кол-во владельцев', 'Кол-во владельцев');
                if (isset($resp['crashCount']))
                    $data['accidents'] = new ResultDataField('string', 'Accidents', $resp['crashCount'], 'Кол-во ДТП', 'Кол-во ДТП');
                if (isset($resp['pledge']))
                    $data['pledge'] = new ResultDataField('string', 'Pledge', $resp['pledge']?'да':'нет', 'Залог', 'Залог');
                if (isset($resp['stealing']))
                    $data['stealing'] = new ResultDataField('string', 'Stealing', $resp['stealing']?'да':'нет', 'Угон', 'Угон');
                if (isset($resp['juristic']))
                    $data['juristic'] = new ResultDataField('string', 'Juristic', $resp['wasJuristicOwners']?'да':'нет', 'Собственность юр.лица', 'Собственность юр.лица');
            } elseif (isset($resp['statusCode']) && ($resp['statusCode']==2)) {

            } elseif ($checktype=='fines' && isset($resp['Fines'])) {
                $fines_map = array();
                $paid_amount = 0;
                $unpaid_amount = 0;
                $paid_sum = 0;
                $unpaid_sum = 0;
                foreach ($resp['Fines'] as $fine) {
                   $data = array();
                   $place = '';
                   $date = '';
                   foreach ($fine['ApnDetail'] as $detail) {
                       $data[$detail['AttrId']] = new ResultDataField('string', $detail['AttrId'], $detail['Value'], $detail['Name'], $detail['Name']);
                       if ($detail['AttrId']=='MestoNar') $place=$detail['Value'];
                       if ($detail['AttrId']=='DatNar') $date=$detail['Value'];
                   }
                   if (isset($fine['DAPName']))
                       $data['dap'] = new ResultDataField('string', 'DAP', $fine['DAPName'], 'Постановление', 'Постановление административного органа');
                   if (isset($fine['DAP']))
                       $data['dapnumber'] = new ResultDataField('string', 'DAPNumber', $fine['DAP'], 'Номер постановления', 'Номер постановления административного органа');
                   if (isset($fine['DateDAP']))
                       $data['dapdate'] = new ResultDataField('string', 'DAPDate', $fine['DateDAP'], 'Дата постановления', 'Дата постановления административного органа');
                   if (isset($fine['FineSum']))
                       $data['sum'] = new ResultDataField('float', 'Sum', $fine['FineSum'], 'Сумма', 'Сумма');
                   if (isset($fine['FineStatus'])) {
                       $data['status'] = new ResultDataField('string', 'Status', $fine['FineStatus']==1 ? 'оплачен':'не оплачен', 'Статус', 'Статус');
                       if ($fine['FineStatus']==1) {
                           $paid_amount++;
                           $paid_sum += $fine['FineSum'];
                       } else {
                           $unpaid_amount++;
                           $unpaid_sum += $fine['FineSum'];
                       }
                   }
                   if (isset($fine['Latitude']) && isset($fine['Longitude']) && $fine['Latitude'] && $fine['Longitude']) {
                       $map = array(array('coords' => array(+strtr($fine['Latitude'],array(','=>'.')),+strtr($fine['Longitude'],array(','=>'.'))), 'text' => trim($date.' '.$place)));
                       $fines_map = array_merge($fines_map,$map);
//                       $data['location'] = new ResultDataField('map','Location',strtr(json_encode($map,JSON_UNESCAPED_UNICODE),array("},{"=>"},\n{")),'Местоположение','Местоположение');
                   }
                   if (isset($fine['HasPhoto']) && $fine['HasPhoto'] && isset($fine['UID'])) {
                   }
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

                $data = array();
            } elseif (isset($resp['Message'])) {
                $data['result'] = new ResultDataField('string', 'Result', $resp['Message'], 'Результат', 'Результат');
            } elseif (isset($resp['ResponceServiceData'])) {
                foreach ($resp['ResponceServiceData'] as $r) {
                    $servdata = array();
                    foreach ($r['Items'] as $item) {
                        $servdata[$item['AttrID']] = new ResultDataField('string', $item['AttrID'], $item['Val'], $item['Name'], $item['Name']);
                    }
                    if (sizeof($servdata)) {
                        $resultData->addResult($servdata);
                    } elseif ($r['Message']) {
                    }
                }
            } else {
                $rContext->setFinished();
                $rContext->setError("Некорректный ответ сервиса (краткий отчет)");
                file_put_contents('./logs/avtokod/avtokod_'.$checktype.'_err_'.time().'.txt',$content);
            }

            if ($checktype=='history' && sizeof($data) && isset($resp['ownershipHistory']['vehicleOwners'])) {
                $ownerstext = '';
                $part = $resp['ownershipHistory']['vehicleOwners'];
                $data['owners'] = new ResultDataField('string', 'Owners', sizeof($part), 'Кол-во владельцев', 'Кол-во владельцев');
                foreach ($part as $owner) {
                    $ownerstext .= ($ownerstext?', ':'').date('d.m.Y',strtotime($owner['ownershipBeginningPeriod'])).'-'.($owner['ownershipEndPeriod']?date('d.m.Y',strtotime($owner['ownershipEndPeriod'])):'н/в').' '.$owner['type'];
                }
                $data['ownerstext'] = new ResultDataField('string', 'OwnersText', $ownerstext, 'История владения', 'История владения');
//                if (isset($resp['ownershipHistory']['ownershipComment']))
//                    $data['ownerscomment'] = new ResultDataField('string', 'OwnersComment', $resp['ownershipHistory']['ownershipComment'], 'Комментарий к истории владения', 'Комментарий к истории владения');
            }

            if ($checktype=='history' && sizeof($data) && isset($resp['inspectionInfo']['inspections'])) {
                $inspectionstext = '';
                $part = $resp['inspectionInfo']['inspections'];
                $data['inspections'] = new ResultDataField('string', 'Inspections', sizeof($part), 'Кол-во техосмотров', 'Кол-во техосмотров');
                foreach ($part as $inspection) {
                    $inspectionstext .= ($inspectionstext?', ':'').date('d.m.Y',strtotime($inspection['dateOfInspection']));
                    if (isset($inspection['diagnosticCardExpiryDate']))
                        $inspectionstext .= '-'.date('d.m.Y',strtotime($inspection['diagnosticCardExpiryDate']));
                    if (isset($inspection['placeOfInspection']) && $inspection['placeOfInspection'])
                        $inspectionstext .= ' '.$inspection['placeOfInspection'];
                    if (isset($inspection['diagnosticCardNumber']) && $inspection['diagnosticCardNumber'])
                        $inspectionstext .= ', карта № '.$inspection['diagnosticCardNumber'];
                }
                $data['inspectionstext'] = new ResultDataField('string', 'InspectionsText', $inspectionstext, 'Техосмотры', 'Техосмотры');
            }

            if ($checktype=='history' && sizeof($data) && isset($resp['trafficAccidentHistory']['trafficAccidents'])) {
                $accidentstext = '';
                $part = $resp['trafficAccidentHistory']['trafficAccidents'];
                $data['accidents'] = new ResultDataField('string', 'Accidents', sizeof($part), 'Кол-во ДТП', 'Кол-во ДТП');
                foreach ($part as $accident) {
                    $accidentstext .= ($accidentstext?', ':'').date('d.m.Y',strtotime($accident['date'])).' '.$accident['regionCode'].' '.$accident['type'].' '.$accident['damage'];
                }
                $data['accidentstext'] = new ResultDataField('string', 'AccidentsText', $accidentstext, 'История ДТП', 'История ДТП');
//                if (isset($resp['trafficAccidentHistory']['trafficAccidentsComment']))
//                    $data['accidentcomment'] = new ResultDataField('string', 'AccidentComment', $resp['trafficAccidentHistory']['trafficAccidentsComment'], 'Комментарий к истории ДТП', 'Комментарий к истории ДТП');
            }

            if ($checktype=='history' && sizeof($data) && isset($resp['insuranceDamageInfo']['insuranceDamages'])) {
                $part = $resp['insuranceDamageInfo']['insuranceDamages'];
                $data['damages'] = new ResultDataField('string', 'Damages', sizeof($part), 'Кол-во страховых случаев', 'Кол-во страховых случаев');
            }

            if ($checktype=='history' && sizeof($data) && isset($resp['mileageInfo']['mileages'])) {
                $mileagestext = '';
                $part = $resp['mileageInfo']['mileages'];
                foreach ($part as $mileage) {
                    if (isset($mileage['originalMileage'])) {
                        $mileagestext .= ($mileagestext?', ':'').date('d.m.Y',strtotime($mileage['originalMileage']['date'])).' '.$mileage['originalMileage']['value'];
                    }
                }
                $data['mileagestext'] = new ResultDataField('string', 'MileagesText', $mileagestext, 'Сведения о пробеге', 'Сведения о пробеге');
            }

            if ($checktype=='history' && sizeof($data) && isset($resp['restrictionInfo']['restrictions'])) {
                $part = $resp['restrictionInfo']['restrictions'];
                $restr_names = array(
                    "Запрет на снятие с учета" => "StrikeOffRegisterRestricted",
                    "Запрет на регистрационные действия и прохождение ТО" => "RegisterAndRestricted",
                    "Утилизация (для транспорта не старше 5 лет)" => "Utilization",
                    "Аннулирование регистрации" => "RegistrationCancelled",
                    "Нахождение в розыске" => "InSearch",
                );
                $has_restr = 0;
                foreach ($part as $restr) {
                    $has_restr += ($restr['result']==false) ? 0 : 1;
                    if (array_key_exists($restr['name'],$restr_names)) {
                      $name = $restr_names[$restr['name']];
                      $data[$name] = new ResultDataField('string', $name, $restr['result']?'да':'нет', $restr['name'], $restr['name']);
                    }
                }
                $data['hasrestrictions'] = new ResultDataField('string', 'HasRestrictions', $has_restr, 'Наличие ограничений', 'Наличие ограничений');
            }

            if ($checktype=='history' && sizeof($data) && isset($resp['commericalUsageInfo']['commercialUsageList'])) {
                $part = $resp['commericalUsageInfo']['commercialUsageList'];
                $comm_names = array(
                    "Использование в качестве такси" => "UsedAsTaxi",
                    "Использование в качестве маршрутного транспорта" => "UsedAsBus",
                    "Использование в качестве грузового транспорта" => "UsedAsTruck",
                    "Использование в качестве специального транспорта (городские службы, аварийные службы и прочее)" => "UsedAsSpecial",
                    "Прочие виды" => "UsedAsOther",
                );
                $comm_usage = 0;
                foreach ($part as $comm) {
                    $comm_usage += ($comm['result']==false) ? 0 : 1;
                    if (array_key_exists($comm['type'],$comm_names)) {
                      $name = $comm_names[$comm['type']];
                      $data[$name] = new ResultDataField('string', $name, $comm['result']?'да':'нет', $comm['type'], $comm['type']);
                    }
                }
                $data['commercialusage'] = new ResultDataField('string', 'CommercialUsage', $comm_usage, 'Коммерческое использование', 'Коммерческое использование');
            }

            if (sizeof($data)) {
                $resultData->addResult($data);
            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();
        } elseif ($res && isset($res['Message']) && $res['Message']) {
            file_put_contents('./logs/avtokod/avtokod_'.$checktype.'_err_'.time().'.txt',$content);
            $error = trim($res['Message']);
            if (strpos($error,'устарели') || strpos($error,'истекло') || strpos($error,'непредвиденная')) {
                unset($swapData['avtokod_id']);
                $rContext->setSwapData($swapData);
            } else {
                $rContext->setFinished();
                $rContext->setError($error);
            }
            return false;
        } elseif ($res && isset($res['message']) && $res['message']) {
            $error = trim($res['message']);
            if ($error=='Капча введена неверно') {
                unset($swapData['avtokod_id']);
                $rContext->setSwapData($swapData);
            } else {
                $rContext->setFinished();
                $rContext->setError($error);
            }
            return false;
        } elseif ($res && isset($res['error']) && $res['error']) {
            unset($swapData['avtokod_id']);
            $rContext->setSwapData($swapData);
            return false;
        } else {
            if (empty(trim($content))) {
                $rContext->setSleep(2);
                $swapData['iteration']--;
                $rContext->setSwapData($swapData);
            } elseif(strpos($content,'Object moved to')!==false) {
                unset($swapData['avtokod_id']);
                $rContext->setSwapData($swapData);
            } elseif($swapData['iteration']>3) {
                $rContext->setFinished();
                $rContext->setError("Некорректный ответ сервиса");
                file_put_contents('./logs/avtokod/avtokod_'.$checktype.'_err_'.time().'.txt',$content);
            } else {
                unset($swapData['avtokod_id']);
                $rContext->setSwapData($swapData);
            }
            return false;
        }
    }
}

?>