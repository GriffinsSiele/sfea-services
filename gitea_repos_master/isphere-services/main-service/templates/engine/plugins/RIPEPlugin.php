<?php

class RIPEPlugin implements PluginInterface
{
    public function getName()
    {
        return 'RIPE';
    }

    public function getTitle()
    {
        return 'Информация о владельце IP-адреса';
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if (!isset($initData['ip'])) {
            $rContext->setFinished();
            $rContext->setError('Не задан IP-адрес');

            return false;
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'http://rest.db.ripe.net/search.json?query-string='.$initData['ip'];
        if ($ch) {
            \curl_setopt($ch, \CURLOPT_URL, $url);
            $rContext->setCurlHandler($ch);
        }

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        $rContext->setSwapData($swapData);

        $curl_error = \curl_error($rContext->getCurlHandler());
        if ($curl_error && $swapData['iteration'] > 3) {
            $rContext->setFinished();
            $rContext->setError('' == $curl_error ? 'Превышено количество попыток получения ответа' : $curl_error);

            return false;
        }

        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        //        file_put_contents('./logs/ripe/ripe_'.time().'.json',$content);
        $res = \json_decode($content, true);

        if ($res && isset($res['objects']['object'])) {
            $resultData = new ResultDataList();
            foreach ($res['objects']['object'] as $obj) {
                $data = [];
                $data['type'] = new ResultDataField('string', 'type', $obj['type'], 'Тип записи', 'Тип записи');
                if (isset($obj['attributes']['attribute'])) {
                    foreach ($obj['attributes']['attribute'] as $attr) {
                        $data[$attr['name']] = new ResultDataField(
                            'phone' == $attr['name'] ? 'phone' : 'string',
                            $attr['name'],
                            $attr['value'],
                            $attr['name'],
                            $attr['name']
                        );
                    }
                }
                $resultData->addResult($data);
            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();
        } else {
            $rContext->setError('Ошибка обработки ответа');
            $rContext->setFinished();
        }
    }
}
