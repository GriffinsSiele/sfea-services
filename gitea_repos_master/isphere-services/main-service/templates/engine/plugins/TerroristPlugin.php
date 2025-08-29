<?php

class TerroristPlugin implements PluginInterface
{
    private $titles = ['lastname' => ['Фамилия', 'Фамилия'], 'firstname' => ['Имя', 'Имя'], 'middlename' => ['Отчество', 'Отчество'], 'birthdate' => ['Дата рождения', 'Дата рождения'], 'birthplace' => ['Место рождения', 'Место рождения']];

    public function __construct()
    {
    }

    public function getName()
    {
        return 'Terrorist';
    }

    public function getTitle()
    {
        return 'Проверка по списку террористов Росфинмониторинга';
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        if (!isset($initData['last_name']) || !isset($initData['first_name'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (фамилия, имя)');
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
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        /** @var \Doctrine\DBAL\Connection $fedsfmConnection */
        $fedsfmConnection = $params['_fedsfmConnection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $resultData = new ResultDataList();
        $last_name = isset($initData['last_name']) ? $initData['last_name'] : '';
        $first_name = isset($initData['first_name']) ? $initData['first_name'] : '';
        $middle_name = isset($initData['patronymic']) ? $initData['patronymic'] : '';
        $birth_date = isset($initData['date']) ? \date('d.m.Y', \strtotime($initData['date'])) : '';
        $result = $fedsfmConnection->executeQuery("SELECT * FROM fedsfm WHERE lastname='".$last_name."' and firstname='".$first_name."'".($middle_name ? " and middlename='".$middle_name."'" : '').($birth_date ? " and (birthdate='' or birthdate='".$birth_date."')" : ''));
        if ($result) {
            while ($row = $result->fetchAssociative()) {
                foreach ($row as $key => $val) {
                    if ($val && isset($this->titles[$key][0])) {
                        $data[$key] = new ResultDataField('string', $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                    }
                }
                $resultData->addResult($data);
            }
        }
        $rContext->setResultData($resultData);
        $rContext->setFinished();

        return true;
    }
}
