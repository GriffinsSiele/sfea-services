<?php

class SBPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Sberbank';
    }

    public function getTitle()
    {
        return 'Поиск в Сбербанк Онлайн';
    }

     public function str_uprus($text) {
        $up = array(
                'а' => 'А',
                'б' => 'Б',
                'в' => 'В',
                'г' => 'Г',
                'д' => 'Д',
                'е' => 'Е',
                'ё' => 'Е',
                'ж' => 'Ж',
                'з' => 'З',
                'и' => 'И',
                'й' => 'Й',
                'к' => 'К',
                'л' => 'Л',
                'м' => 'М',
                'н' => 'Н',
                'о' => 'О',
                'п' => 'П',
                'р' => 'Р',
                'с' => 'С',
                'т' => 'Т',
                'у' => 'У',
                'ф' => 'Ф',
                'х' => 'Х',
                'ц' => 'Ц',
                'ч' => 'Ч',
                'ш' => 'Ш',
                'щ' => 'Щ',
                'ъ' => 'Ъ',
                'ы' => 'Ы',
                'ь' => 'Ь',
                'э' => 'Э',
                'ю' => 'Ю',
                'я' => 'Я',
        );
        if (preg_match("/[а-яё]/", $text))
            $text = strtr($text, $up);
        return $text;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['card']))
        {
            $rContext->setFinished();
            $rContext->setError('Не задан номер карты');

            return false;
        }


        if(strlen($initData['card'])<15 || strlen($initData['card'])>19 || strlen($initData['card'])==17 || preg_match("/\D/",$initData['card']))
        {
            $rContext->setFinished();
            $rContext->setError('Номер карты должен содержать 15,16,18 или 19 цифр');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $card = $initData['card'];
        $url = 'https://src1.i-sphere.ru/sb/?cardNum='.$card;
        curl_setopt($ch, CURLOPT_URL, $url);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $error = false;
        $content = curl_multi_getcontent($rContext->getCurlHandler());
        file_put_contents('./logs/sberbank/sberbank_'.time().'.txt',$content);

        if (!$content) {
            $error = /*($swapData['iteration']>3) && */ curl_error($rContext->getCurlHandler());
            $rContext->setSleep(1);
        } else {
            file_put_contents('./logs/sberbank/sberbank_'.time().'.txt',$content);
            $res = json_decode($content, true);
            if($res && isset($res['status']) && $res['status']=='success'){
                $resultData = new ResultDataList();
                $data = array();
                if(isset($res['cardExists']) && $res['cardExists']!='0'){
                    if ($res['cardExists']==1 && isset($res['fio']) && $res['fio']!='undefined') {
                        $data['name'] = new ResultDataField('string','name',$res['fio'],'Имя','Имя');
                        $data['status'] = new ResultDataField('string','status','Активна','Статус карты','Статус карты');
                    } elseif ($res['cardExists']=='blocked') {
                        $data['status'] = new ResultDataField('string','status','Заблокирована','Статус карты','Статус карты');
                    }

                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif($res && isset($res['data']) && $res['data']=='in queue already') {
//            } elseif($res && isset($res['status']) && $res['status']=='error'){
//                file_put_contents('./logs/sberbank/sberbank_err_'.time().'.txt',$content);
            } elseif($res && isset($res['data']) && $res['data']=='lame cardNum') {
                $error = "Некорректный номер карты";
            } else {
                file_put_contents('./logs/sberbank/sberbank_err_'.time().'.txt',$content);
                if ($swapData['iteration']>=3) {
                    if (strpos($content,'nginx')) {
                        $error = "Сервис временно недоступен";
                    } else {
                        $error = "Некорректный ответ";
                    }
                }
            }
        }
        $rContext->setSwapData($swapData);

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>=20) {
            $error='Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        $rContext->setSleep(5);

        return true;
    }
}

?>