<?php

class NBKIAutoPlugin implements PluginInterface
{
    public function getName()
    {
        return 'NBKIAuto';
    }

    public function getTitle()
    {
        return 'Поиск в НБКИ ТСЗ';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if( !isset($initData['vin']) )
        {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (VIN)');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://i-sphere.ru/pl_asynch/nbkiCollatAuto/';
        if (!isset($swapData['nbkiauto_id'])) {
            $url .= '_init?vin=' . $initData['vin'];
        } else {
            $url .= '_view?id='. $swapData['nbkiauto_id'];
        }

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
        file_put_contents('./logs/nbkiauto/nbkiauto_'.time().'.txt',$content);
        $res = json_decode($content, true);

        if (!isset($swapData['nbkiauto_id'])) {
            if (isset($res['id'])) {
                $swapData['nbkiauto_id'] = $res['id'];
                $rContext->setSwapData($swapData);
                return true;
            } elseif (strpos($content,'Значение поля')!==false) {
                $rContext->setFinished();
                $rContext->setError($content);
                return true;
            } else {
                if($swapData['iteration']>10) {
                    print($content."\r\n");
                    $rContext->setFinished();
                    $rContext->setError("Ошибка при выполнении запроса");
                    return true;
                }
                return false;
            }
        } elseif (isset($res['Result'])) {
            if (isset($res['Response'])) {
                $info = $res['Response'];
                $data['result'] = new ResultDataField('string', 'Result', 'Автомобиль находится в залоге', 'Результат', 'Результат');
                $data['resultcode'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                $data['vin'] = new ResultDataField('string', 'VIN', $info['VIN'], 'VIN', 'VIN');
                if (isset($info['Chassis']))
                    $data['chassis'] = new ResultDataField('string', 'Chassis', $info['Chassis'], 'Номер шасси', 'Номер шасси');
                if (isset($info['Body']))
                    $data['body'] = new ResultDataField('string', 'Body', $info['Body'], 'Номер кузова', 'Номер кузова');
                if (isset($info['Engine']))
                    $data['engine'] = new ResultDataField('string', 'Engine', $info['Engine'], 'Номер двигателя', 'Номер двигателя');
                if (isset($info['DocNum']))
                    $data['docnum'] = new ResultDataField('string', 'DocNum', $info['DocNum'], 'Номер свидетельства', 'Номер свидетельства');
                if (isset($info['Model']))
                    $data['model'] = new ResultDataField('string', 'Model', $info['Model'], 'Модель', 'Модель');
                if (isset($info['ManufYear']))
                    $data['year'] = new ResultDataField('string', 'Year', $info['ManufYear'], 'Год выпуска', 'Год выпуска');
                if (isset($info['Colour']))
                    $data['color'] = new ResultDataField('string', 'Color', $info['Colour'], 'Цвет', 'Цвет');
                if (isset($info['DateTo']))
                    $data['enddate'] = new ResultDataField('string', 'EndDate', $info['DateTo'], 'Дата окончания', 'Дата окончания');
            } else {
                $data['result'] = new ResultDataField('string', 'Result', 'Сведения о залоге не найдены', 'Результат', 'Результат');
                $data['resultcode'] = new ResultDataField('string', 'ResultCode', 'NOT_FOUND', 'Код результата', 'Код результата');
            }

            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
        } elseif (isset($res['Message']) && $res['Message']) {
            $error = trim($res['Message']);
            $rContext->setFinished();
            $rContext->setError($error);
            return false;
        } elseif (isset($res['error']) && $res['error']) {
            unset($swapData['nbkiauto_id']);
            $rContext->setSwapData($swapData);
            return false;
        } else {
            if (empty(trim($content)) || $content=='false') {
                $rContext->setSleep(2);
            }
            if($swapData['iteration']>30) {
                $rContext->setFinished();
                $rContext->setError("Некорректный ответ сервиса");
            }
            return false;
        }
    }
}

?>