<?php

class VINPlugin implements PluginInterface
{
    private $names = [
        'VIN-код' => ['vin', 'VIN', 'VIN'],
        //                           'В залоге до' => array('dateto', 'В залоге до', 'В залоге до'),
        //                           'Банк' => array('bank', 'Банк', 'Банк'),
        'Идентификационный номер' => ['vin', 'VIN', 'VIN'],
        'WMI' => ['wmi', 'WMI', 'WMI'],
        'Контрольный символ' => ['check_digit', 'Контрольный символ', 'Контрольный символ'],
        'Марка' => ['mark', 'Марка', 'Марка'],
        'Модель' => ['model', 'Модель', 'Модель'],
        'Код модели' => ['model_code', 'Код модели', 'Код модели'],
        'Поколение модели' => ['generation', 'Поколение модели', 'Поколение модели'],
        'Модификация' => ['modification', 'Модификация', 'Модификация'],
        'Комплектация' => ['complectation', 'Комплектация', 'Комплектация'],
        'Линия' => ['line', 'Линия', 'Линия'],
        'Тип кузова' => ['body_type', 'Тип кузова', 'Тип кузова'],
        'Название кузова' => ['body_name', 'Название кузова', 'Название кузова'],
        'Тип транспортного средства' => ['cartype', 'Тип транспортного средства', 'Тип транспортного средства'],
        'Количество дверей' => ['doors', 'Количество дверей', 'Количество дверей'],
        'Количество окон' => ['windows', 'Количество окон', 'Количество окон'],
        'Примечание' => ['note	', 'Примечание', 'Примечание'],
        'Тип КПП' => ['transmission', 'Тип КПП', 'Тип КПП'],
        'Количество передач' => ['transmission_gears', 'Количество передач', 'Количество передач'],
        'Привод' => ['wheel_drive', 'Привод', 'Привод'],
        'Тип привода' => ['wheel_drive', 'Привод', 'Привод'],
        'Название привода' => ['wheel_drive_name', 'Название привода', 'Название привода'],
        'Колёсная база' => ['wheel_base', 'Колёсная база', 'Колёсная база'],
        'Расположение рулевой колонки' => ['steering_wheel_side', 'Расположение рулевой колонки', 'Расположение рулевой колонки'],
        'Количество посадочных мест' => ['seats_count', 'Количество посадочных мест', 'Количество посадочных мест'],
        'Система пассивной безопасности' => ['passive_safety_system', 'Система пассивной безопасности', 'Система пассивной безопасности'],
        'Рынок сбыта' => ['market', 'Рынок сбыта', 'Рынок сбыта'],
        'Регион' => ['region', 'Регион', 'Регион'],
        'Дата производства' => ['manufacturing_date', 'Дата производства', 'Дата производства'],
        'Начало производства' => ['manufacturing_start', 'Начало производства', 'Начало производства'],
        'Окончание производства' => ['manufacturing_end', 'Окончание производства', 'Окончание производства'],
        'Период производства' => ['manufacturing_period', 'Период производства', 'Период производства'],
        'Период производства модели' => ['model_manufacturing_period', 'Период производства модели', 'Период производства модели'],
        'Период производства кузова' => ['body_manufacturing_period', 'Период производства кузова', 'Период производства кузова'],
        'Модельный год' => ['model_year', 'Модельный год', 'Модельный год'],
        'Сборочный завод' => ['factory', 'Сборочный завод', 'Сборочный завод'],
        'Производитель' => ['manufacturer', 'Производитель', 'Производитель'],
        'Адрес производителя' => ['manufacturer_address', 'Адрес производителя', 'Адрес производителя'],
        'Телефон производителя' => ['manufacturer_phone', 'Телефон производителя', 'Телефон производителя'],
        'Доп. информация о производителе' => ['manufacturer_info', 'Информация о производителе', 'Информация о производителе'],
        'Завод' => ['factory', 'Завод', 'Завод'],
        'Владелец марки' => ['mark_owner', 'Владелец марки', 'Владелец марки'],
        'Страна сборки' => ['factory_country', 'Страна сборки', 'Страна сборки'],
        'Страна происхождения' => ['model_country', 'Страна происхождения', 'Страна происхождения'],
        'Объем двигателя, куб.см.' => ['engine_displacement', 'Объем двигателя, куб.см.', 'Объем двигателя, куб.см.'],
        'Объём двигателя, куб.см' => ['engine_displacement', 'Объем двигателя, куб.см.', 'Объем двигателя, куб.см.'],
        'Тип двигателя' => ['engine_type', 'Тип двигателя', 'Тип двигателя'],
        'Серия двигателя' => ['engine_series', 'Серия двигателя', 'Серия двигателя'],
        'Описание двигателя' => ['engine_details', 'Описание двигателя', 'Описание двигателя'],
        'Назначение двигателя' => ['engine_purpose', 'Назначение двигателя', 'Назначение двигателя'],
        'Топливо' => ['fuel', 'Топливо', 'Топливо'],
        'Топливная система' => ['fuel_system', 'Топливная система', 'Топливная система'],
        'Расход топлива' => ['fuel_consumption', 'Расход топлива', 'Расход топлива'],
        'Тип ГРМ' => ['gdm_type', 'Тип ГРМ', 'Тип ГРМ'],
        'Форсировка' => ['reheated', 'Форсировка', 'Форсировка'],
        'Наддув' => ['supercharging', 'Наддув', 'Наддув'],
        'Степень сжатия' => ['engine_compression', 'Степень сжатия', 'Степень сжатия'],
        'Клапанов на цилиндр (угол развала)' => ['valves_on_cylinder', 'Клапанов на цилиндр (угол развала)', 'Клапанов на цилиндр (угол развала)'],
        'Серийный номер' => ['serial_number', 'Серийный номер', 'Серийный номер'],
        'Номер двигателя' => ['engine_number', 'Номер двигателя', 'Номер двигателя'],
        '№ двигателя' => ['engine_number', 'Номер двигателя', 'Номер двигателя'],
        'Конфигурация' => ['configuration', 'Конфигурация', 'Конфигурация'],
        'Мощность' => ['engine_power', 'Мощность', 'Мощность'],
        'Крутящий момент' => ['engine_torque', 'Крутящий момент', 'Крутящий момент'],
        'Стандарт определения мощности и момента' => ['engine_standard', 'Стандарт определения мощности и момента', 'Стандарт определения мощности и момента'],
        'Компоновка двигателя' => ['engine_layout', 'Компоновка двигателя', 'Компоновка двигателя'],
        'Дополнительная информация' => ['additional_info', 'Дополнительная информация', 'Дополнительная информация'],
        'Тип ТНВД' => ['lgfet_type', 'Тип ТНВД', 'Тип ТНВД'],
        'Класс (Klasse)' => ['class', 'Класс', 'Класс'],
        'Серия КПП' => ['kpp_series', 'Серия КПП', 'Серия КПП'],
        'Экологический стандарт' => ['eco_standart', 'Экологический стандарт', 'Экологический стандарт'],
        'Соответствие экологическим нормам' => ['eco_standart', 'Экологический стандарт', 'Экологический стандарт'],
        'Полная масса транспортного средства' => ['full_weight', 'Полная масса транспортного средства', 'Полная масса транспортного средства'],
        'Длина, мм' => ['length', 'Длина, мм', 'Длина, мм'],
        'Шасси' => ['chassis', 'Шасси', 'Шасси'],
        'Марка шасси' => ['chassis_mark', 'Марка шасси', 'Марка шасси'],
        'Модель шасси' => ['chassis_model', 'Модель шасси', 'Модель шасси'],
        'Назначение' => ['purpose', 'Назначение', 'Назначение'],
        'Страна производства двигателя' => ['engine_country', 'Страна производства двигателя', 'Страна производства двигателя'],
    ];

    public function getName()
    {
        return 'VIN';
    }

    public function getTitle()
    {
        return 'Расшифровка VIN';
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        if (!isset($initData['vin'])) {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (VIN)');

            return false;
        }
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        $url = 'http://pogazam.ru/vin/?steps=0;0;0;0;0;0;0;0;0;0;0;0;-1;&vin='.$initData['vin'];
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, 1);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $swapData = $rContext->getSwapData();
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        $curlError = \curl_error($rContext->getCurlHandler());
        if ($curlError && $swapData['iteration'] > 10) {
            $rContext->setFinished();
            $rContext->setError('' == $curlError ? 'Превышено количество попыток получения ответа' : $curlError);

            return false;
        }
        $rContext->setSwapData($swapData);
        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        //        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/vin/vin_'.time().'.html',$content);
        $content = \iconv('windows-1251', 'utf-8', $content);
        if ($content && \preg_match("/<table cellspacing='0'>(.*?)<\\/table>/sim", $content, $matches)) {
            $content = $matches[1];
            $resultData = new ResultDataList();
            $counter = 0;
            if (\preg_match_all('/<tr><td>([^<]+)<\\/td><td>([^<]+)<\\/td><\\/tr>/', $content, $matches)) {
                $data = [];
                foreach ($matches[1] as $key => $val) {
                    $title = \trim($val);
                    $text = \str_replace('&#039;', "'", \html_entity_decode(\trim($matches[2][$key])));
                    if ($text) {
                        if (isset($this->names[$title])) {
                            $field = $this->names[$title];
                            $data[$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $text, $field[1], $field[2]);
                        } else {
                            ++$counter;
                            $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
                            //                            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fields/vin_'.time().'_'.strtr($title,array('/'=>'_')), $title."\n".$text);
                        }
                    }
                }
                $resultData->addResult($data);
            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif ($content && \strpos($content, 'не отвечает') > 0) {
            $rContext->setFinished();
            $rContext->setError('Сервис не отвечает');

            return false;
        } else {
            if ($swapData['iteration'] > 10) {
                $rContext->setFinished();
                $rContext->setError('Некорректный ответ сервиса');
            }

            return false;
        }
    }
}
