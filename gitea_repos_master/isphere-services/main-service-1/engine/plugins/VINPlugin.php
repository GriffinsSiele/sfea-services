<?php

class VINPlugin implements PluginInterface
{
    private $names = array (
                           'VIN-код' => array('vin', 'VIN', 'VIN'),
//                           'В залоге до' => array('dateto', 'В залоге до', 'В залоге до'),
//                           'Банк' => array('bank', 'Банк', 'Банк'),
                           'Идентификационный номер' => array('vin', 'VIN', 'VIN'),
                           'WMI' => array('wmi', 'WMI', 'WMI'),
                           'Контрольный символ' => array('check_digit', 'Контрольный символ', 'Контрольный символ'),
                           'Марка' => array('mark', 'Марка', 'Марка'),
                           'Модель' => array('model', 'Модель', 'Модель'),
                           'Код модели' => array('model_code', 'Код модели', 'Код модели'),
                           'Поколение модели' => array('generation', 'Поколение модели', 'Поколение модели'),
                           'Модификация' => array('modification', 'Модификация', 'Модификация'),
                           'Комплектация' => array('complectation', 'Комплектация', 'Комплектация'),
                           'Линия' => array('line', 'Линия', 'Линия'),
                           'Тип кузова' => array('body_type', 'Тип кузова', 'Тип кузова'),
                           'Название кузова' => array('body_name', 'Название кузова', 'Название кузова'),
                           'Тип транспортного средства' => array('cartype', 'Тип транспортного средства', 'Тип транспортного средства'),
                           'Количество дверей' => array('doors', 'Количество дверей', 'Количество дверей'),
                           'Количество окон' => array('windows', 'Количество окон', 'Количество окон'),
                           'Примечание' => array('note	', 'Примечание', 'Примечание'),
                           'Тип КПП' => array('transmission', 'Тип КПП', 'Тип КПП'),
                           'Количество передач' => array('transmission_gears', 'Количество передач', 'Количество передач'),
                           'Привод' => array('wheel_drive', 'Привод', 'Привод'),
                           'Тип привода' => array('wheel_drive', 'Привод', 'Привод'),
                           'Название привода' => array('wheel_drive_name', 'Название привода', 'Название привода'),
                           'Колёсная база' => array('wheel_base', 'Колёсная база', 'Колёсная база'),
                           'Расположение рулевой колонки' => array('steering_wheel_side', 'Расположение рулевой колонки', 'Расположение рулевой колонки'),
                           'Количество посадочных мест' => array('seats_count', 'Количество посадочных мест', 'Количество посадочных мест'),
                           'Система пассивной безопасности' => array('passive_safety_system', 'Система пассивной безопасности', 'Система пассивной безопасности'),
                           'Рынок сбыта' => array('market', 'Рынок сбыта', 'Рынок сбыта'),
                           'Регион' => array('region', 'Регион', 'Регион'),
                           'Дата производства' => array('manufacturing_date', 'Дата производства', 'Дата производства'),
                           'Начало производства' => array('manufacturing_start', 'Начало производства', 'Начало производства'),
                           'Окончание производства' => array('manufacturing_end', 'Окончание производства', 'Окончание производства'),
                           'Период производства' => array('manufacturing_period', 'Период производства', 'Период производства'),
                           'Период производства модели' => array('model_manufacturing_period', 'Период производства модели', 'Период производства модели'),
                           'Период производства кузова' => array('body_manufacturing_period', 'Период производства кузова', 'Период производства кузова'),
                           'Модельный год' => array('model_year', 'Модельный год', 'Модельный год'),
                           'Сборочный завод' => array('factory', 'Сборочный завод', 'Сборочный завод'),
                           'Производитель' => array('manufacturer', 'Производитель', 'Производитель'),
                           'Адрес производителя' => array('manufacturer_address', 'Адрес производителя', 'Адрес производителя'),
                           'Телефон производителя' => array('manufacturer_phone', 'Телефон производителя', 'Телефон производителя'),
                           'Доп. информация о производителе' => array('manufacturer_info', 'Информация о производителе', 'Информация о производителе'),
                           'Завод' => array('factory', 'Завод', 'Завод'),
                           'Владелец марки' => array('mark_owner', 'Владелец марки', 'Владелец марки'),
                           'Страна сборки' => array('factory_country', 'Страна сборки', 'Страна сборки'),
                           'Страна происхождения' => array('model_country', 'Страна происхождения', 'Страна происхождения'),
                           'Объем двигателя, куб.см.' => array('engine_displacement', 'Объем двигателя, куб.см.', 'Объем двигателя, куб.см.'),
                           'Объём двигателя, куб.см' => array('engine_displacement', 'Объем двигателя, куб.см.', 'Объем двигателя, куб.см.'),
                           'Тип двигателя' => array('engine_type', 'Тип двигателя', 'Тип двигателя'),
                           'Серия двигателя' => array('engine_series', 'Серия двигателя', 'Серия двигателя'),
                           'Описание двигателя' => array('engine_details', 'Описание двигателя', 'Описание двигателя'),
                           'Назначение двигателя' => array('engine_purpose', 'Назначение двигателя', 'Назначение двигателя'),
                           'Топливо' => array('fuel', 'Топливо', 'Топливо'),
                           'Топливная система' => array('fuel_system', 'Топливная система', 'Топливная система'),
                           'Расход топлива' => array('fuel_consumption', 'Расход топлива', 'Расход топлива'),
                           'Тип ГРМ' => array('gdm_type', 'Тип ГРМ', 'Тип ГРМ'),
                           'Форсировка' => array('reheated', 'Форсировка', 'Форсировка'),
                           'Наддув' => array('supercharging', 'Наддув', 'Наддув'),
                           'Степень сжатия' => array('engine_compression', 'Степень сжатия', 'Степень сжатия'),
                           'Клапанов на цилиндр (угол развала)' => array('valves_on_cylinder', 'Клапанов на цилиндр (угол развала)', 'Клапанов на цилиндр (угол развала)'),
                           'Серийный номер' => array('serial_number', 'Серийный номер', 'Серийный номер'),
                           'Номер двигателя' => array('engine_number', 'Номер двигателя', 'Номер двигателя'),
                           '№ двигателя' => array('engine_number', 'Номер двигателя', 'Номер двигателя'),
                           'Конфигурация' => array('configuration', 'Конфигурация', 'Конфигурация'),
                           'Мощность' => array('engine_power', 'Мощность', 'Мощность'),
                           'Крутящий момент' => array('engine_torque', 'Крутящий момент', 'Крутящий момент'),
                           'Стандарт определения мощности и момента' => array('engine_standard', 'Стандарт определения мощности и момента', 'Стандарт определения мощности и момента'),
                           'Компоновка двигателя' => array('engine_layout', 'Компоновка двигателя', 'Компоновка двигателя'),
                           'Дополнительная информация' => array('additional_info', 'Дополнительная информация', 'Дополнительная информация'),
                           'Тип ТНВД' => array('lgfet_type', 'Тип ТНВД', 'Тип ТНВД'),
                           'Класс (Klasse)' => array('class', 'Класс', 'Класс'),
                           'Серия КПП' => array('kpp_series', 'Серия КПП', 'Серия КПП'),
                           'Экологический стандарт' => array('eco_standart', 'Экологический стандарт', 'Экологический стандарт'),
                           'Соответствие экологическим нормам' => array('eco_standart', 'Экологический стандарт', 'Экологический стандарт'),
                           'Полная масса транспортного средства' => array('full_weight', 'Полная масса транспортного средства', 'Полная масса транспортного средства'),
                           'Длина, мм' => array('length', 'Длина, мм', 'Длина, мм'),
                           'Шасси' => array('chassis', 'Шасси', 'Шасси'),
                           'Марка шасси' => array('chassis_mark', 'Марка шасси', 'Марка шасси'),
                           'Модель шасси' => array('chassis_model', 'Модель шасси', 'Модель шасси'),
                           'Назначение' => array('purpose', 'Назначение', 'Назначение'),
                           'Страна производства двигателя' => array('engine_country', 'Страна производства двигателя', 'Страна производства двигателя'),
    );

    public function getName()
    {
        return 'VIN';
    }

    public function getTitle()
    {
        return 'Расшифровка VIN';
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

        $ch = $rContext->getCurlHandler();

        $url = 'http://pogazam.ru/vin/?steps=0;0;0;0;0;0;0;0;0;0;0;0;-1;&vin='.$initData['vin'];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $swapData = $rContext->getSwapData();
        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $curlerror = false; //curl_error($rContext->getCurlHandler());

        if($curlError && $swapData['iteration']>10)
        {
            $rContext->setFinished();
            $rContext->setError($curlError==''?'Превышено количество попыток получения ответа':$curlError);

            return false;
        }

        $rContext->setSwapData($swapData);

        $content = curl_multi_getcontent($rContext->getCurlHandler());
//        file_put_contents('./logs/vin/vin_'.time().'.html',$content);
        $content = iconv('windows-1251','utf-8',$content);

        if($content && preg_match("/<table cellspacing='0'>(.*?)<\/table>/sim", $content, $matches)){
            $content = $matches[1];

            $resultData = new ResultDataList();
            $counter = 0;
            if(preg_match_all("/<tr><td>([^<]+)<\/td><td>([^<]+)<\/td><\/tr>/", $content, $matches)){
                $data = array();
                foreach( $matches[1] as $key => $val ){
                    $title = trim($val);
                    $text = str_replace("&#039;", "'", html_entity_decode(trim($matches[2][$key])));
                    if ($text) {
                        if (isset($this->names[$title])){
                            $field = $this->names[$title];
                            $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $text, $field[1], $field[2]);
                        } else {
                            $counter++;
                            $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
//                            file_put_contents('./logs/fields/vin_'.time().'_'.strtr($title,array('/'=>'_')), $title."\n".$text);
                        }
                    }
                }
                $resultData->addResult($data);
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif ($content && strpos($content,'не отвечает')>0) {
            $rContext->setFinished();
            $rContext->setError('Сервис не отвечает');
            return false;
        } else {
            if($swapData['iteration']>10) {
                $rContext->setFinished();
                $rContext->setError("Некорректный ответ сервиса");
            }
            return false;
        }
    }
}

?>