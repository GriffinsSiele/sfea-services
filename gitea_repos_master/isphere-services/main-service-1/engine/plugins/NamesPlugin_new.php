<?php

class NamesPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Names';
    }

    public function getTitle($checktype = '')
    {
        return 'Поиск возможных имен в приложениях-определителях номеров';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],10);

        if(!isset($initData['phone'])) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (телефон)');

            return false;
        }

//        if (strlen($initData['phone'])==10)
//            $initData['phone']='7'.$initData['phone'];
//        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
//            $initData['phone']='7'.substr($initData['phone'],1);

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $phone = $initData['phone'];
        $url = 'http://global.d0o.ru/api/numbuster?token=94def889184bb7b8de213422790e40f7&phone='.$phone;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_TIMEOUT,10);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = false; //$swapData['iteration']>2 ? curl_error($rContext->getCurlHandler()) : false;

        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
            file_put_contents('./logs/names/names_'.time().'.txt',$content);

            $res = json_decode($content, true);               
            if($res && isset($res['data'])){
                $resultData = new ResultDataList();
                foreach($res['data'] as $row) {
                    $data = array();
                    $name = '';
                    if (isset($row['firstName'])) {
                        $data['firstname'] = new ResultDataField('string','FirstName',iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',strtr($row['firstName'],array(chr(0)=>' ')))),'Имя','Имя');
                        $name = iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',strtr($row['firstName'],array(chr(0)=>' '))));
                    }
                    if (isset($row['lastName'])) {
                        $data['lastname'] = new ResultDataField('string','LastName',iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',strtr($row['lastName'],array(chr(0)=>' ')))),'Фамилия','Фамилия');
                        $name = trim($name . ' ' . iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',strtr($row['lastName'],array(chr(0)=>' ')))));
                    }
                    if ($name)
                        $data['name'] = new ResultDataField('string','Name',$name,'Полное имя','Полное имя');
                    if (isset($row['count']))
                        $data['count'] = new ResultDataField('string','Count',$row['count'],'Количество записей','Количество записей');

                    if (sizeof($data))
                        $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif($res && isset($res['message'])) {
                $error = $res['message'];
                if (strpos($error,'SQLSTATE')!==false) $error='Внутренняя ошибка источника';
            } else {
                if ($res) $error = "Некорректный ответ";
            }
        }
        $rContext->setSwapData($swapData);

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>=2)
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