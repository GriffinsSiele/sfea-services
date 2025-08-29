<?php

class AvInfoPlugin implements PluginInterface
{
    private $names = array (
                           'Госномер' => array('regnum', 'Госномер', 'Госномер'),
                           'Машина' => array('car', 'Машина', 'Машина'),
                           'Год машины' => array('caryear', 'Год машины', 'Год машины'),
                           'Дата' => array('date', 'Дата', 'Дата'),
                           'VIN' => array('vin', 'VIN', 'VIN'),
                           'СТС' => array('ctc', 'СТС', 'СТС'),
                           'ПТС' => array('pts', 'ПТС', 'ПТС'),
                           'Стоимость' => array('price', 'Стоимость', 'Стоимость'),
                           'Количество владельцев' => array('owners_count', 'Количество владельцев', 'Количество владельцев'),
                           'Владелец' => array('owner', 'Владелец', 'Владелец'),
                           'Год рождения владельца' => array('owner_birthyear', 'Год рождения владельца', 'Год рождения владельца'),
                           'Телефон' => array('owner_phone', 'Телефон владельца', 'Телефон владельца','phone'),

                           'Идентификационный номер' => array('vin', 'Идентификационный номер', 'Идентификационный номер'),
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
        return 'avinfo';
    }

    public function getTitle()
    {
        return 'Поиск на avinfo';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if( !isset($initData['vin']) && !isset($initData['regnum']) && !isset($initData['phone']))
        {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (VIN, госномер или телефон)');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $params = array();
        if (isset($initData['vin'])) $params['vin']=$initData['vin'];
        if (isset($initData['regnum'])) $params['gosnomer']=$initData['regnum'];
        if (isset($initData['phone'])) {
            if (strlen($initData['phone'])==10)
                $initData['phone']='7'.$initData['phone'];
            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
                $initData['phone']='7'.substr($initData['phone'],1);
            $params['phone']=$initData['phone'];
        }
        $url = 'http://avinfo.co/info/?'.http_build_query($params);

        curl_setopt($ch, CURLOPT_URL, $url);

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
//        file_put_contents('./logs/avinfo/avinfo_'.time().'.html',$content);
        $resultData = new ResultDataList();

        $counter = 0;
        if(preg_match_all("/<tr>[^<]+<td [^>]+>([^<]+)<\/td>[^<]+<td>([^<]+)<\/td>[^<]+<\/tr>/", $content, $matches)){
            $data = array();
            foreach( $matches[1] as $key => $val ){
                $title = trim(strip_tags($val));
                $text = str_replace("&#039;", "'", html_entity_decode(strip_tags($matches[2][$key])));
                if (isset($this->names[$title])){
                    $field = $this->names[$title];
                    $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $text, $field[1], $field[2]);
                } else {
                    $counter++;
                    $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
//                    file_put_contents('./logs/fields/avinfo_'.time().'_'.$title , $title."\n".$text);
                }
            }
            $resultData->addResult($data);
        }

        if(preg_match_all("/<td valign=\"top\" width=\"200px\" nowrap>(.*?)<\/td>/", $content, $matches)){
            foreach($matches[1] as $record){
                $data = array();
                $rows = preg_split('/<br[^>]*>/', $record);

                foreach( $rows as $row ){
                    if(preg_match("/\?gosnomer=([^\"]+)/", $row, $val)){
                        $field = $this->names['Госномер'];
                        $text = str_replace("&#039;", "'", html_entity_decode(strip_tags(trim($val[1]))));
                        $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $text, $field[1], $field[2]);
                    } elseif(strpos($row, ': ')){
                        $val = explode(': ', $row);
                        $title = trim(strip_tags($val[0]));
                        $text = str_replace("&#039;", "'", html_entity_decode(strip_tags(trim($val[1]))));
                        if (isset($this->names[$title])){
                            $field = $this->names[$title];
                            $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $text, $field[1], $field[2]);
                        } else {
                            $counter++;
                            $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
                            file_put_contents('./logs/fields/avinfo_'.time().'_'.$title , $title."\n".$text);
                        }
                    }
                }
                $resultData->addResult($data);
            }
        }

        $rContext->setResultData($resultData);
        $rContext->setFinished();

        if(isset($error) && ($swapData['iteration']>10)) {
            $rContext->setFinished();
            $rContext->setError($error);
            return false;
        }
        return true;
    }
}

?>