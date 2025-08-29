<?php

class AvtoKodFinesPlugin implements PluginInterface
{
    public function getName()
    {
        return 'avtokod';
    }

    public function getTitle()
    {
        return 'Поиск штрафов через АвтоКод';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if( !isset($initData['ctc']) && !isset($initData['driver_number']))
        {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (св-во о регистрации ТС или водительское удостоверение)');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://i-sphere.ru/pl_asynch/avtokodFines/';
        if (!isset($swapData['fines_id'])) {
            $url .= '_init?';
            if( isset($initData['ctc'])) {
                $url .= 'ctc=' . urlencode($initData['ctc']);
            } else {
                $url .= 'vu=' . urlencode($initData['driver_number']).(isset($initData['driver_date']) ? '&vDate='.$initData['driver_date'] : '');
            }
        } else {
            $url .= '_view?id='. $swapData['fines_id'];
        }
//        print "$url\n";
        curl_setopt($ch, CURLOPT_URL, $url);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $swapData = $rContext->getSwapData();
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
//        if (!empty(trim($content))) file_put_contents('./logs/avtokod/fines_'.(isset($swapData['fines_id'])?'':'start_').time().'.txt',$content);
        $res = json_decode($content, true);

        if (!isset($swapData['fines_id'])) {
            if (isset($res['id'])) {
                $swapData['fines_id'] = $res['id'];
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
        } elseif (isset($res['StateCode']) && ($res['StateCode']=='0')) {
            $val = json_decode($res['Value'], true);
//            file_put_contents('./logs/avtokod/fines_val_'.time().'.txt',$res['Value']);
            if (isset($val['response']))
                $resp = $val['response'];
            else
                $resp = $val;

            $resultData = new ResultDataList();
            if (isset($resp['Fines'])) {
                foreach ($resp['Fines'] as $fine) {
                   $data = array();
                   $place = '';
                   foreach ($fine['ApnDetail'] as $detail) {
                       $data[$detail['AttrId']] = new ResultDataField('string', $detail['AttrId'], $detail['Value'], $detail['Name'], $detail['Name']);
                       if ($detail['AttrId']=='MestoNar') $place=$detail['Value'];
                   }
                   if (isset($fine['DAPName']))
                       $data['dap'] = new ResultDataField('string', 'DAP', $fine['DAPName'], 'Постановление', 'Постановление административного органа');
                   if (isset($fine['DAP']))
                       $data['dapnumber'] = new ResultDataField('string', 'DAPNumber', $fine['DAP'], 'Номер постановления', 'Номер постановления административного органа');
                   if (isset($fine['DateDAP']))
                       $data['dapdate'] = new ResultDataField('string', 'DAPDate', $fine['DateDAP'], 'Дата постановления', 'Дата постановления административного органа');
                   if (isset($fine['FineSum']))
                       $data['sum'] = new ResultDataField('float', 'Sum', $fine['FineSum'], 'Сумма', 'Сумма');
                   if (isset($fine['FineStatus']))
                       $data['status'] = new ResultDataField('string', 'Status', $fine['FineStatus']==1 ? 'оплачен':'не оплачен', 'Статус', 'Статус');
                   if (isset($fine['Latitude']) && isset($fine['Longitude']) && $fine['Latitude'] && $fine['Longitude']) {
                       $map = array(array('coords' => array(+strtr($fine['Latitude'],array(','=>'.')),+strtr($fine['Longitude'],array(','=>'.'))), 'text' => $place));
                       $data['location'] = new ResultDataField('map','Location',strtr(json_encode($map,JSON_UNESCAPED_UNICODE),array("},{"=>"},\n{")),'Местоположение','Местоположение');
                   }
                   if (isset($fine['HasPhoto']) && $fine['HasPhoto'] && isset($fine['UID'])) {
                   }
                   $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif (isset($resp['Message']) && $resp['Message']) {
                $error = trim($resp['Message']);
                $rContext->setFinished();
                $rContext->setError($error);
            } 
        } elseif (isset($res['Message']) && $res['Message']) {
            $error = trim($res['Message']);
            if (($error=='Данные страницы устарели') || ($error=='Время сеанса истекло.')) {
                unset($swapData['fines_id']);
                $rContext->setSwapData($swapData);
            } else {
                $rContext->setFinished();
                $rContext->setError($error);
            }
            return false;
        } elseif (isset($res['message']) && $res['message']) {
            $error = trim($res['message']);
            if ($error=='Капча введена неверно') {
                unset($swapData['fines_id']);
                $rContext->setSwapData($swapData);
            } else {
                $rContext->setFinished();
                $rContext->setError($error);
            }
            return false;
        } elseif ($res && isset($res['error']) && $res['error']) {
            unset($swapData['fines_id']);
            $rContext->setSwapData($swapData);
            return false;
        } else {
            if (empty(trim($content))) {
                $rContext->setSleep(2);
                $swapData['iteration']--;
                $rContext->setSwapData($swapData);
            } elseif(strpos($content,'Object moved to')!==false) {
                unset($swapData['fines_id']);
                $rContext->setSwapData($swapData);
            } elseif($swapData['iteration']>3) {
                $rContext->setFinished();
                $rContext->setError("Некорректный ответ сервиса");
                file_put_contents('./logs/avtokod/fines_err_'.time().'.txt',$content);
            } else {
                unset($swapData['fines_id`']);
                $rContext->setSwapData($swapData);
            }
            return false;
        }
    }
}

?>