<?php

class CBRPlugin implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'CBR';
    }

    public function getTitle()
    {
        return 'Проверка по списку отказов ЦБ РФ';
    }

    public function prepareRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();

        if(!isset($initData['inn'])){
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ИНН)');
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////
/*
        $ch = $rContext->getCurlHandler();
        $url = 'https://i-sphere.ru';
        curl_setopt($ch, CURLOPT_URL, $url);
        $rContext->setCurlHandler($ch);
        return true;
*/

        $resultData = new ResultDataList();

        $result = $mysqli->query("SELECT inn,name,prim FROM cbr.cbr WHERE inn='".$initData['inn']."' GROUP BY 1,2,3");
        if ($result) {
            while($row = $result->fetch_assoc()){
                $data['inn'] = new ResultDataField('string','inn',$row['inn'],'ИНН','ИНН');
                $data['reason'] = new ResultDataField('string','reason',$row['name'],'Причина отказа','Причина отказа');
                $data['note'] = new ResultDataField('string','comment',$row['prim'],'Примечание','Примечание');
                $resultData->addResult($data);
            }
            $result->close();
            $rContext->setResultData($resultData);
        } else {
            $rContext->setError('Внутренняя ошибка');
        }

        $rContext->setFinished();
        return false;
    }

    public function computeRequest(&$rContext)
    {
/*
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $rContext->setSwapData($swapData);
        $content = curl_multi_getcontent($rContext->getCurlHandler());

//        if ($content) {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,name,prim FROM cbr.cbr WHERE inn='".$initData['inn']."' GROUP BY 1,2,3");
            if ($result) {
                while($row = $result->fetch_assoc()){
                    $data['inn'] = new ResultDataField('string','inn',$row['inn'],'ИНН','ИНН');
                    $data['reason'] = new ResultDataField('string','reason',$row['name'],'Причина отказа','Причина отказа');
                    $data['note'] = new ResultDataField('string','comment',$row['prim'],'Примечание','Примечание');
                    $resultData->addResult($data);
                }
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
//        }

        if($swapData['iteration']>5) {
            $rContext->setFinished();
            $rContext->setError($error==''?'Превышено количество попыток получения ответа':$error);
            return false;
        }

        return true;
*/
    }
}

?>