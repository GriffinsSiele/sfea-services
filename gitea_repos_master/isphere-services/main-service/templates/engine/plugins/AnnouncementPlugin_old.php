<?php

class AnnouncementPlugin_old implements PluginInterface
{
    private $names = [
           'ID' => ['ID', 'ID'],
       'URL объявления' => ['url', 'URL'],
       'Категория' => ['cat', 'Категория'],
       'Подкатегория' => ['subcat', 'Подкатегория'],
       'Регион' => ['region', 'Регион'],
       'Город' => ['city', 'Город'],
       'Метро' => ['metro', 'Метро'],
       'Адрес' => ['address', 'Адрес'],
       'Статус' => ['status', 'Статус'],
       'Компания' => ['company', 'Компания'],
       'Продавец' => ['seller', 'Продавец'],
       'Контактное лицо' => ['contact_name', 'Контактное лицо'],
       'Телефон' => ['phone', 'Телефон'],
       'Оператор' => ['operator', 'Оператор'],
       'Регионы обслуживания' => ['operator_region', 'Регион оператора'],
       'Дата публикации' => ['date', 'Дата'],
       'Время публикации' => ['time', 'Время'],
       'Заголовок' => ['title', 'Заголовок'],
       'Параметры' => ['parameters', 'Параметры'],
       'Текст' => ['text', 'Текст'],
       'Цена' => ['price', 'Цена', 'Цена', 'float'],
       'Широта' => ['latitude', 'Широта'],
       'Долгота' => ['longtitude', 'Долгота'],
    ];

    public function __construct()
    {
    }

    public function getName()
    {
        return 'Announcement';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск по архиву объявлений',
            'announcement_phone' => 'Архив объявлений - поиск по телефону',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск по архиву объявлений';
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if (!isset($initData['phone'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (телефон)');

            return false;
        }

        if (10 == \strlen($initData['phone'])) {
            $initData['phone'] = '7'.$initData['phone'];
        }
        if ((11 == \strlen($initData['phone'])) && ('8' == \substr($initData['phone'], 0, 1))) {
            $initData['phone'] = '7'.\substr($initData['phone'], 1);
        }

        if ('7' != \substr($initData['phone'], 0, 1)) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только по российским телефонам');
            return false;
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        //        $url = 'https://dev.i-sphere.ru/avito/search.php?phone='.substr($initData['phone'],1);
        $url = 'http://172.16.12.9/avito/search.php?phone='.\substr($initData['phone'], 1);
        \curl_setopt($ch, \CURLOPT_URL, $url);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $error = false;
        $content = \curl_multi_getcontent($rContext->getCurlHandler());

        if (!$content) {
            $error = ($swapData['iteration'] > 5) && \curl_error($rContext->getCurlHandler());
        } else {
            //            file_put_contents('./logs/announcement/'.time().'.txt',$content);
            $res = \json_decode($content, true);
            if ($res && isset($res['status']) && 'error' != $res['status']) {
                $resultData = new ResultDataList();
                if (isset($res['data']) && \is_array($res['data'])) {
                    foreach ($res['data'] as $row) {
                        if (\is_array($row)) {
                            $data = [];
                            foreach ($row as $title => $text) {
                                if (isset($this->names[$title])) {
                                    $field = $this->names[$title];
                                    if ($text) {
                                        $data[$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $text, $field[1], $field[1]);
                                    }
                                }
                            }
                            $resultData->addResult($data);
                        }
                    }
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } else {
                \file_put_contents('./logs/announcement/err_'.\time().'.txt', $content);
                $error = 'Ошибка при выполнении запроса';
            }
        }

        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] > 2) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        return true;
    }
}
