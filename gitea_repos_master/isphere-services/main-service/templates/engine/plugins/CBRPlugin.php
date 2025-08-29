<?php

use Doctrine\DBAL\Connection;

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

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();

        if (!isset($initData['inn'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (ИНН)');
            return false;
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();
        $url = 'https://i-sphere.ru';
        \curl_setopt($ch, \CURLOPT_URL, $url);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        $connection = $params['_connection'];
        \assert($connection instanceof Connection);
        $cbrConnection = $params['_cbrConnection'];
        \assert($cbrConnection instanceof Connection);
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;

        $rContext->setSwapData($swapData);
        $content = \curl_multi_getcontent($rContext->getCurlHandler());

        //        if ($content) {
        $resultData = new ResultDataList();

        $result = $cbrConnection->executeQuery("SELECT inn,name,prim FROM cbr WHERE inn='".$initData['inn']."' GROUP BY 1,2,3");
        if ($result) {
            while ($row = $result->fetchAssociative()) {
                $data['inn'] = new ResultDataField('string', 'inn', $row['inn'], 'ИНН', 'ИНН');
                $data['reason'] = new ResultDataField('string', 'reason', $row['name'], 'Причина отказа', 'Причина отказа');
                $data['note'] = new ResultDataField('string', 'comment', $row['prim'], 'Примечание', 'Примечание');
                $resultData->addResult($data);
            }
        }

        $rContext->setResultData($resultData);
        $rContext->setFinished();

        return true;
        //        }

        if ($swapData['iteration'] > 5) {
            $rContext->setFinished();
            $rContext->setError('' == $error ? 'Превышено количество попыток получения ответа' : $error);

            return false;
        }

        return true;
    }
}
