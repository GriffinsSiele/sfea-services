<?php

class DNSPlugin implements PluginInterface
{
    public function getName()
    {
        return 'DNS';
    }

    public function getTitle()
    {
        return 'Определение имени по IP';
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

        $name = \gethostbyaddr($initData['ip']);
        $resultData = new ResultDataList();
        if ($name) {
            if ($name != $initData['ip']) {
                $data['name'] = new ResultDataField('string', 'name', $name, 'Имя хоста', 'Имя хоста');

                $hosts = \gethostbynamel($name);
                if ($hosts) {
                    $data['hosts'] = new ResultDataField('string', 'hosts', \implode(',', $hosts), 'Другие IP-адреса хоста', 'Другие IP-адреса хоста');
                }

                $resultData->addResult($data);
            }
        }
        $rContext->setResultData($resultData);
        $rContext->setFinished();

        return false;
    }

    public function computeRequest(array $params, &$rContext): void
    {
    }
}
