<?php

class AutoRuPlugin implements PluginInterface
{
    private $names = array (
                           'VIN-код' => array('vin', 'VIN', 'VIN'),
                           'В залоге до' => array('dateto', 'В залоге до', 'В залоге до'),
                           'Банк' => array('bank', 'Банк', 'Банк'),
                           'Идентификационный номер' => array('vin', 'VIN', 'VIN'),
                           'WMI' => array('wmi', 'WMI', 'WMI'),
                           'Марка' => array('mark', 'Марка', 'Марка'),
                           'Модель' => array('model', 'Модель', 'Модель'),
                           'Модификация' => array('modification', 'Модификация', 'Модификация'),
                           'Линия' => array('line', 'Линия', 'Линия'),
                           'Тип кузова' => array('body_type', 'Тип кузова', 'Тип кузова'),
                           'Название кузова' => array('body_name', 'Название кузова', 'Название кузова'),
                           'Тип транспортного средства' => array('cartype', 'Тип транспортного средства', 'Тип транспортного средства'),
                           'Количество дверей' => array('doors', 'Количество дверей', 'Количество дверей'),
                           'Количество окон' => array('windows', 'Количество окон', 'Количество окон'),
                           'Примечание' => array('note	', 'Примечание', 'Примечание'),
                           'Тип КПП' => array('transmission', 'Тип КПП', 'Тип КПП'),
                           'Привод' => array('wheel_drive', 'Привод', 'Привод'),
                           'Колёсная база' => array('wheel_base', 'Колёсная база', 'Колёсная база'),
                           'Расположение рулевой колонки' => array('steering_wheel_side', 'Расположение рулевой колонки', 'Расположение рулевой колонки'),
                           'Количество посадочных мест' => array('seats_count', 'Количество посадочных мест', 'Количество посадочных мест'),
                           'Система пассивной безопасности' => array('passive_safety_system', 'Система пассивной безопасности', 'Система пассивной безопасности'),
                           'Рынок сбыта' => array('market', 'Рынок сбыта', 'Рынок сбыта'),
                           'Регион' => array('region', 'Регион', 'Регион'),
                           'Дата производства' => array('manufacturing_date', 'Дата производства', 'Дата производства'),
                           'Период производства' => array('manufacturing_period', 'Период производства', 'Период производства'),
                           'Период производства модели' => array('model_manufacturing_period', 'Период производства модели', 'Период производства модели'),
                           'Период производства кузова' => array('body_manufacturing_period', 'Период производства кузова', 'Период производства кузова'),
                           'Модельный год' => array('model_year', 'Модельный год', 'Модельный год'),
                           'Сборочный завод' => array('factory', 'Сборочный завод', 'Сборочный завод'),
                           'Завод' => array('factory', 'Завод', 'Завод'),
                           'Владелец марки' => array('mark_owner', 'Владелец марки', 'Владелец марки'),
                           'Страна сборки' => array('factory_country', 'Страна сборки', 'Страна сборки'),
                           'Страна происхождения' => array('model_country', 'Страна происхождения', 'Страна происхождения'),
                           'Объем двигателя, куб.см.' => array('engine_displacement', 'Объем двигателя, куб.см.', 'Объем двигателя, куб.см.'),
                           'Тип двигателя' => array('engine_type', 'Тип двигателя', 'Тип двигателя'),
                           'Серия двигателя' => array('engine_series', 'Серия двигателя', 'Серия двигателя'),
                           'Описание двигателя' => array('engine_details', 'Описание двигателя', 'Описание двигателя'),
                           'Назначение двигателя' => array('engine_purpose', 'Назначение двигателя', 'Назначение двигателя'),
                           'Топливо' => array('fuel', 'Топливо', 'Топливо'),
                           'Топливная система' => array('fuel_system', 'Топливная система', 'Топливная система'),
                           'Тип ГРМ' => array('gdm_type', 'Тип ГРМ', 'Тип ГРМ'),
                           'Форсировка' => array('reheated', 'Форсировка', 'Форсировка'),
                           'Наддув' => array('supercharging', 'Наддув', 'Наддув'),
                           'Клапанов на цилиндр (угол развала)' => array('valves_on_cylinder', 'Клапанов на цилиндр (угол развала)', 'Клапанов на цилиндр (угол развала)'),
                           'Серийный номер' => array('serial_number', 'Серийный номер', 'Серийный номер'),
                           'Номер двигателя' => array('engine_number', 'Номер двигателя', 'Номер двигателя'),
                           '№ двигателя' => array('engine_number', 'Номер двигателя', 'Номер двигателя'),
                           'Конфигурация' => array('configuration', 'Конфигурация', 'Конфигурация'),
                           'Мощность' => array('engine_power', 'Мощность', 'Мощность'),
                           'Тип ТНВД' => array('lgfet_type', 'Тип ТНВД', 'Тип ТНВД'),
                           'Класс (Klasse)' => array('class', 'Класс', 'Класс'),
                           'Серия КПП' => array('kpp_series', 'Серия КПП', 'Серия КПП'),
                           'Экологический стандарт' => array('eco_standart', 'Экологический стандарт', 'Экологический стандарт'),
                           'Полная масса транспортного средства' => array('full_weight', 'Полная масса транспортного средства', 'Полная масса транспортного средства'),
                           'Длина, мм' => array('length', 'Длина, мм', 'Длина, мм'),
    );

    public function getName()
    {
        return 'auto.ru';
    }

    public function getTitle()
    {
        return 'Поиск на auto.ru';
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

        if(!isset($swapData['cookies'])){
            $swapData['cookies'] = time().'_'.rand(100,1000);
            $rContext->setSwapData($swapData);
	}

        $ch = $rContext->getCurlHandler();

        $url = 'http://vin.auto.ru/';
        if(isset($swapData['resolve'])) {
            $url .= $swapData['resolve'];
        } elseif(isset($swapData['hidden']))
        {
            $params = array(
                $swapData['hidden'] => 1,
                'vin' => $initData['vin'],
                'bank' => 1,
                'decode' => 1,
            );
            $url .= 'check.html?'.http_build_query($params);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_COOKIEFILE, './logs/cookies/'.$swapData['cookies'].'_cookies.txt');
	curl_setopt($ch, CURLOPT_COOKIEJAR, './logs/cookies/'.$swapData['cookies'].'_cookies.txt');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
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
        if(!isset($swapData['hidden'])){
            if(preg_match("/<input type=\"hidden\" name=\"([^\"]+)/", $content, $matches)){
                $swapData['hidden'] = $matches[1];	
            } else{
                $error = "Ошибка выполнения запроса";
            }
        } elseif(!isset($swapData['resolve'])){
//            file_put_contents('./logs/autoru/autoru_'.time().'.html',$content);

            if(preg_match("/<div class=\"all-clear\">(.*?)<\/div>/sim", $content, $matches)){
                $result = trim(strip_tags($matches[1]));
                $data['Result'] = new ResultDataField('string','Result', $result, 'Результат проверки', 'Результат проверки');
                if(preg_match("/<a href='([^']+)'>\[Расшифровать\]<\/a>/sim", $content, $matches)){
                    $swapData['resolve'] = $matches[1];
                    $swapData['data'] = $data;
                } else {
                    $resultData = new ResultDataList();
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                }
            } elseif(preg_match("/<div class=\"attention\">(.*?)<\/div>/sim", $content, $matches)){
                $result = trim(strip_tags($matches[1]));
                $data['Result'] = new ResultDataField('string', 'Result', strpos($result,'находится в залоге') ? 'Автомобиль находится в залоге' : 'Сведения о залоге не найдены', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', strpos($result,'находится в залоге') ? 'FOUND' : 'NOT_FOUND', 'Код результата', 'Код результата');

                $counter = 0;
                if(preg_match_all("/<dt><strong>([^<]+)<\/strong><\/dt>[^<]+<dd>(.*?)<\/dd>/sim", $content, $matches)){
                    foreach( $matches[1] as $key => $val ){
                        $title = $val;
                        $text = str_replace("&#039;", "'", html_entity_decode(strip_tags($matches[2][$key])));
                        if (isset($this->names[$title])){
                            $field = $this->names[$title];
                            if ($text) $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $text, $field[1], $field[2]);
                        } else {
                            $counter++;
                            if ($text) $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
                            file_put_contents('./logs/fields/autoru_'.time().'_'.$title , $title."\n".$text);
                        }
                    }
                }

                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } else {
                $error="Некорректный ответ сервиса";
            }
        } else {
//            file_put_contents('./logs/autoru/autoru_resolve_'.time().'.html',$content);
            $data = $swapData['data'];
            $counter = 0;
            if(preg_match_all("/<dt><strong>([^<]+)<\/strong><\/dt><dd>(.*?)<\/dd>/sim", $content, $matches)){
                foreach( $matches[1] as $key => $val ){
                    $title = $val;
                    $text = trim(str_replace("&#039;", "'", html_entity_decode(strip_tags($matches[2][$key]))));
                    if (isset($this->names[$title])){
                        $field = $this->names[$title];
                        if ($text) $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $text, $field[1], $field[2]);
                    } elseif ($title!='Расшифровка') {
                        $counter++;
                        if ($text) $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
                        file_put_contents('./logs/fields/autoru_resolve_'.time().'_'.$title , $title."\n".$text);
                    }
                }
            }

            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
        }
        $rContext->setSwapData($swapData);

        if(isset($error) && ($swapData['iteration']>10)) {
            $rContext->setFinished();
            $rContext->setError($error);
            return false;
        }
        return true;
    }
}

?>