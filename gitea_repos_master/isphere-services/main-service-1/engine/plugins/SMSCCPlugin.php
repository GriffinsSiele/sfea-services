<?php

class SMSCCPlugin implements PluginInterface
{
    private $login = 'kotiphones';
    private $password = '6367863Qq';
//    private $login = 'isphere';
//    private $password = '6644283';
//    private $login = 'cartarget';
//    private $password = 'sm143960';

    public function getName()
    {
        return 'SMSCC';
    }

    public function getTitle()
    {
        return 'Проверка доступности абонента мобильной связи';
    }

    private $hlrErrors = array(
        0 => 'Доступен',
        1 => 'Не существует',
        6 => 'Не в сети',
        11 => 'Нет услуги SMS',
        13 => 'Заблокирован',
        21 => 'Не принимает SMS',
        99 => 'Неизвестная ошибка',
        248 => 'Неизвестный оператор',
        249 => 'Неверный номер',
        250 => 'Ограничен доступ',
        251 => 'Превышен лимит',
        252 => 'Номер запрещен',
        253 => 'Услуга не поддерживается',
        255 => 'Запрос отклонен',
    );

    private $hlrStatus = array(
        '-1' => 'Ожидает отправки',
        0 => 'Передано оператору',
        1 => 'Доставлено',
        3 => 'Просрочено',
        20 => 'Невозможно доставить',
        22 => 'Неверный номер',
        23 => 'Запрещено',
        24 => 'Недостаточно средств',
        25 => 'Недоступный номер',
    );

    private $hlrFields = array(
       'status' => 'код статуса',
       'last_date' => 'дата последнего изменения статуса. Формат DD.MM.YYYY hh:mm:ss.',
       'last_timestamp' => 'штамп времени последнего изменения статуса.',
       'err' => 'код HLR-ошибки или статуса абонента (список).',
       'imsi' => 'уникальный код IMSI SIM-карты абонента.',
       'msc' => 'номер сервис-центра оператора, в сети которого находится абонент.',
       'mcc' => 'числовой код страны абонента.',
       'mnc' => 'числовой код оператора абонента.',
       'cn' => 'название страны регистрации абонента.',
       'net' => 'название оператора регистрации абонента.',
       'rcn' => 'название роуминговой страны абонента при нахождении в чужой сети.',
       'rnet' => 'название роумингового оператора абонента при нахождении в чужой сети.',
       'send_date' => 'дата отправки сообщения (формат DD.MM.YYYY hh:mm:ss).',
       'send_timestamp' => 'штамп времени отправки сообщения.',
       'phone' => 'номер телефона абонента.',
       'operator' => 'название оператора абонента.',
       'region' => 'регион регистрации номера абонента.',
       'cost' => 'стоимость сообщения.',
       'sender' => 'имя отправителя.',
       'status_name' => 'название статуса.',
       'message' => 'текст сообщения.',
    );

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['phone_qwe']))
        {
            $rContext->setFinished();
            $rContext->setError('Не задан номер телефона');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

            $params = array(
                'phone' => $initData['phone_qwe'],
                'login' => $this->login,
                'psw' => $this->password,
                'id' => $initData['request_id'],
                'fmt' => 3,
                'all' => 2,
		'charset' => 'utf-8',
            );

            $url = 'http://smsc.ru/sys/status.php';

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $error = false;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $content = curl_multi_getcontent($rContext->getCurlHandler());
        if ($content) {
            $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
            $ares = json_decode($content, true);

            if (array_key_exists('status',$ares))
            {
//                $data['phone'] = new ResultDataField('string','PhoneNumber', $initData['phone'], 'Номер', 'Номер телефона');
                if ($ares['status']>0)
                {
                    foreach( $ares as $key => $val ){
                        $data[$key] = new ResultDataField('string',$key, $val, $key, $this->hlrFields[$key]);
                    }
                    $rContext->setResultData($data);
                    $rContext->setFinished();
                } else {
                    $rContext->setSleep(3);
                    $error = "Запрос в процессе обработки";
                }
            } else {
                if (array_key_exists('err', $ares)) {
                    $error = $ares['err'];
                } else {
                    $error = 'Некорректный ответ сервиса';
                }
            }
        } else {
            $error = ($swapData['iteration']>5) && curl_error($rContext->getCurlHandler());
        }
        $rContext->setSwapData($swapData);

        if(isset($swapData['iteration']) && $swapData['iteration']>3) {
            $rContext->setFinished();
            $rContext->setError($error==''?'Превышено количество попыток получения ответа':$error);

            return false;
        }

        return true;
    }
}

?>