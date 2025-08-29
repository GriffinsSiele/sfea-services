<?php

class YaPeoplePlugin implements PluginInterface
{
    private $names = array (
                           'Дата рождения' => array('birthdate', 'Дата рождения', 'Дата рождения'),
                           'Город' => array('place', 'Город', 'Город'),
                           'Вуз' => array('education', 'Образование', 'Образование'),
                           'Школа' => array('school', 'Школа', 'Школа'),
                           'Работа' => array('job', 'Работа', 'Работа'),
                           'Друзей' => array('friends', 'Друзей', 'Друзей'),
                           'Подписчиков' => array('subscribers', 'Подписчиков', 'Подписчиков'),
                           'О себе' => array('about', 'О себе', 'О себе'),
    );

    private $regions = array(
        '01' => 'Адыгея',
        '02' => 'Башкортостан',
        '03' => 'Бурятия',
        '04' => 'Алтай',
        '05' => 'Дагестан',
        '06' => 'Ингушетия',
        '07' => 'Кабардино-Балкарская Республика',
        '08' => 'Калмыкия',
        '09' => 'Карачаево-Черкесская Республика',
        '10' => 'Карелия',
        '11' => 'Коми',
        '12' => 'Марий Эл',
        '13' => 'Мордовия',
        '14' => 'Якутия',
        '15' => 'Северная Осетия',
        '16' => 'Татарстан',
        '17' => 'Тыва',
        '18' => 'Удмуртская Республика',
        '19' => 'Хакасия',
        '20' => 'Чеченская Республика',
        '21' => 'Чувашская Республика',
        '22' => 'Алтайский край',
        '23' => 'Краснодарский край',
        '24' => 'Красноярский край',
        '25' => 'Приморский край',
        '26' => 'Ставропольский край',
        '27' => 'Хабаровский край',
        '28' => 'Амурская область',
        '29' => 'Архангельская область',
        '30' => 'Астраханская область',
        '31' => 'Белгородская область',
        '32' => 'Брянская область',
        '33' => 'Владимирская область',
        '34' => 'Волгоградская область',
        '35' => 'Вологодская область',
        '36' => 'Воронежская область',
        '37' => 'Ивановская область',
        '38' => 'Иркутская область',
        '39' => 'Калининградская область',
        '40' => 'Калужская область',
        '41' => 'Камчатская область',
        '42' => 'Кемеровская область',
        '43' => 'Кировская область',
        '44' => 'Костромская область',
        '45' => 'Курганская область',
        '46' => 'Курская область',
        '47' => 'Ленинградская область',
        '48' => 'Липецкая область',
        '49' => 'Магаданская область',
        '50' => 'Московская область',
        '51' => 'Мурманская область',
        '52' => 'Нижегородская область',
        '53' => 'Новгородская область',
        '54' => 'Новосибирская область',
        '55' => 'Омская область',
        '56' => 'Оренбургская область',
        '57' => 'Орловская область',
        '58' => 'Пензенская область',
        '59' => 'Пермский край',
        '60' => 'Псковская область',
        '61' => 'Ростовская область',
        '62' => 'Рязанская область',
        '63' => 'Самарская область',
        '64' => 'Саратовская область',
        '65' => 'Сахалинская область',
        '66' => 'Свердловская область',
        '67' => 'Смоленская область',
        '68' => 'Тамбовская область',
        '69' => 'Тверская область',
        '70' => 'Томская область',
        '71' => 'Тульская область',
        '72' => 'Тюменская область',
        '73' => 'Ульяновская область',
        '74' => 'Челябинская область',
        '75' => 'Читинская область',
        '76' => 'Ярославская область',
        '77' => 'Москва',
        '78' => 'Санкт-Петербург',
        '79' => 'Еврейская автономная область',
        '80' => 'Бурятский автономный округ',
        '81' => 'Коми-Пермяцкий автономный округ',
        '82' => 'Корякский автономный округ',
        '83' => 'Ненецкий автономный округ',
        '84' => 'Таймырский автономный округ',
        '85' => 'Усть-Ордынский автономный округ',
        '86' => 'Ханты-Мансийский автономный округ',
        '87' => 'Чукотский автономный округ',
        '88' => 'Эвенкийский автономный округ',
        '89' => 'Ямало-Ненецкий автономный округ',
    );

    public function str_uprus($text) {
        $up = array(
                'а' => 'А',
                'б' => 'Б',
                'в' => 'В',
                'г' => 'Г',
                'д' => 'Д',
                'е' => 'Е',
                'ё' => 'Ё',
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
                'ә' => 'Ә',
                'ғ' => 'Ғ',
                'қ' => 'Қ',
                'ң' => 'Ң',
                'ө' => 'Ө',
                'ұ' => 'Ұ',
                'ү' => 'Ү',
                'һ' => 'Һ',
                'і' => 'І',
        );
        if (preg_match("/[а-я]/", $text))
            $text = strtr($text, $up);
        return $text;
    }
    public function str_translit($text) {
        $trans = array(
                'Ә' => 'А',
                'Ғ' => 'Г',
                'Қ' => 'К',
                'Ң' => 'Н',
                'Ө' => 'О',
                'Ұ' => 'У',
                'Ү' => 'У',
                'Һ' => 'Х',
                'І' => 'И',
/*
                'ЕВГЕНИ' => 'YEVGENI',
                'КС' => 'X',
                'А' => 'A',
                'Б' => 'B',
                'В' => 'V',
                'Г' => 'G',
                'Д' => 'D',
                'Е' => 'E',
                'Ё' => 'E',
                'Ж' => 'ZH',
                'З' => 'Z',
                'И' => 'I',
                'Й' => 'Y',
                'К' => 'K',
                'Л' => 'L',
                'М' => 'M',
                'Н' => 'N',
                'О' => 'O',
                'П' => 'P',
                'Р' => 'R',
                'С' => 'S',
                'Т' => 'T',
                'У' => 'U',
                'Ф' => 'F',
                'Х' => 'H',
                'Ц' => 'TS',
                'Ч' => 'CH',
                'Ш' => 'SH',
                'Щ' => 'SH',
                'ЬЕ' => 'YE',
                'ЬЁ' => 'YO',
                'Ь' => '',
                'Ы' => 'Y',
                'Ъ' => '',
                'Э' => 'E',
                'Ю' => 'YU',
                'Я' => 'YA',
*/
        );
        $text = $this->str_uprus($text);
        if (preg_match("/[А-Я]/", $text))
            $text = strtr($text, $trans);
        return $text;
    }

    public function getName()
    {
        return 'people';
    }

    public function getTitle()
    {
         return 'Яндекс - поиск в социальных сетях';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=15 AND unix_timestamp(now())-unix_timestamp(lasttime)>1 ORDER BY lasttime limit 1");

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used>=10 AND id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        //$initData['region_id'] = -1;

        if(!isset($initData['last_name']) || !isset($initData['first_name'])) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (фамилия, имя)');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $swapData['session'] = $this->getSessionData();
//        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $rContext->setSwapData($swapData);

        if(!$swapData['session'])
        {
//            $rContext->setFinished();
//            $rContext->setError('Нет актуальных сессий');
            $rContext->setSleep(3);
            return false;
        }

        /////////////////////////////////////////////////////////////////////////
	
        $ch = $rContext->getCurlHandler();

        $fio = '"'.$this->str_translit($initData['last_name'] . ' ' . $initData['first_name'] . (isset($initData['patronymic']) ? ' ' . $initData['patronymic'] : '')).'"';
	
	$rContext->setSwapData($swapData);
	
        $url = 'https://yandex.ru/people?ajax='.urlencode('{"main":{}}').'&noreask=1&text='.urlencode($fio).
            (isset($initData['date']) ? '&ps_age='.urlencode(date('d.m.Y',strtotime($initData['date']))) : '').
            (isset($initData['region_id']) && ($initData['region_id']>0) ? '&ps_geo='.urlencode($this->regions[$initData['region_id']]) : '');
        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=UTF-8',
            'Accept: application/json, text/javascript, */*; q=0.01',
            'X-Requested-With: XMLHttpRequest'));
        curl_setopt($ch, CURLOPT_REFERER, 'https://yandex.ru/people');

        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        if ($swapData['session']->proxy) {
            curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
            }
        }

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = curl_error($rContext->getCurlHandler());
        if(!$error && $swapData['iteration']>10) {
             $rContext->setFinished();
             $rContext->setError($error==''?'Unknown Error':$error);
             return false;
        }
	
        $content = curl_multi_getcontent($rContext->getCurlHandler());
//        file_put_contents('./logs/people/people'.time().'.json',$content);

        $res = json_decode($content, true);
        if ($res && isset($res['main']['params']['html'])) {
            $content = $res['main']['params']['html'];
//            file_put_contents('./logs/people/people'.time().'.html',$content);
        }

/*
        if(strpos($content, '<div class=misspell__message>') !== false)  // empty
        {
//	    $error='Не найден';
            $resultData = new ResultDataList();
            $rContext->setResultData($resultData);
	    $rContext->setFinished();
	    return false;
        }
*/
        $found = 0;
        if(preg_match("/<div class=serp-adv__found>[^\s]+\s(.*?)результат/",$content,$matches)) {
            $found = intval(strtr($matches[1],array('&nbsp;'=>'',' '=>'','тыс.'=>'000','млн'=>'000000')));
        }
        if ($found > 10) {
            $error = "Найдено слишком много совпадений ($found). Попробуйте указать в запросе дату рождения, место учебы, работы или жительства.";
            if ($rContext->getLevel()==0)
                $rContext->setError($error);
            $rContext->setFinished();
        }elseif( strpos($content, '<ul class="serp-list serp-list_left_yes') !== false){
//            $records = explode('<li class="serp-item__wrap clearfix">', $content);
            $records = explode('<li class=serp-item', $content);
            array_shift($records);

            $resultData = new ResultDataList();

            foreach($records as $record){
                $data = array();
                if(preg_match("/<a class=\"link link_theme_normal organic__url [^>]+>(.*?)<\/a>/",$record, $matches)){
                    $title = explode(' – ',strip_tags($matches[1]));
                    $data['network'] = new ResultDataField('string','Network',$title[sizeof($title)-1],'Социальная сеть','Социальная сеть');
                    $data['name'] = new ResultDataField('string','Name',iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',$title[0])),'Имя','Имя в соцсети');
                }
//                if(preg_match("/<a class=\"link link_theme_normal organic__url [^h]+href=\"([^\"]+)\"/", $record, $matches)){
                if(preg_match("/<a class=\"link link_theme_outer path__item [^>]+>(.*?)<\/a>/",$record, $matches)){
                    $matches[1] = 'https://'.strip_tags($matches[1]);
//                    if (preg_match("/^\/\//",$matches[1])) $matches[1]='https:'.$matches[1];
                    $matches[1] = strtr($matches[1],array('://odnoklassniki.ru'=>'://ok.ru'));
                    $data['link'] = new ResultDataField('url'.(sizeof($records)<10?':recursive':''),'Link',str_replace('http://','https://',$matches[1]),'Ссылка','Ссылка на страницу в соцсети');
                    if (preg_match("/my.mail.ru\/([^\/]+)\/(.*?)$/",$matches[1],$matches2)) {
                        $data['email'] = new ResultDataField('email','Email',$matches2[2].'@'.$matches2[1].'.ru','Email','Email');
                    }                        
                }
                if(preg_match("/<div class=\"image image_type_cover[^\"]+\" style=\"[^;]+[^:]+:url\(([^\)]+)/",$record, $matches)){
                    $start=0; //strpos($matches[1],'http%3A');
                    $data['piclink'] = new ResultDataField('image','Photo','' . $matches[1],'Фото','Фотография пользователя');
                }

/*
                if(preg_match("/<div class=\"people__birth\">(.*?)<\/div>/", $record, $matches)){
                    $birth = preg_split('/<span[^>]*><\/span>/', $matches[1]);
                    $data['region'] = new ResultDataField('string','Region',$birth[0],'Регион','Регион');
                    if(sizeof($birth)>1) {
                        $repl = array(' '=>'.',
                            'января'=>'01','февраля'=>'02','марта'=>'03','апреля'=>'04','мая'=>'05','июня'=>'06',
                            'июля'=>'07','августа'=>'08','сентября'=>'09','октября'=>'10','ноября'=>'11','декабря'=>'12');
                        if(strpos($birth[1],' ')==1) $birth[1] = '0' . $birth[1];
                        $birthdate = strtr($birth[1],$repl);
                        $data['birthdate'] = new ResultDataField('string','BirthDate',$birthdate,'Дата рождения','Дата рождения');
                    }
                }
*/
                $record = strtr(html_entity_decode($record),array('\"'=>'"','&apos;'=>"'"));
                if(preg_match_all("/<div class=paragraph><b>([^<]+)<\/b>: ([^<]+)<\/div>/",$record,$matches)){
                    foreach($matches[1] as $key => $type ) {
                        $val = iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',trim(html_entity_decode(html_entity_decode($matches[2][$key])))));
                        if (array_key_exists($type,$this->names)) {
                            $field = $this->names[$type];
                            if($field[0]=='birthdate') {
                                $repl = array(' '=>'.',
                                    'янв'=>'01','фев'=>'02','мар'=>'03','апр'=>'04','мая'=>'05','июн'=>'06',
                                    'июл'=>'07','авг'=>'08','сен'=>'09','окт'=>'10','ноя'=>'11','дек'=>'12');
                                if(strpos($val,' ')==1) $val='0'.$val;
                                $val = strtr($val,$repl);
                            }
                            $data[$field[0]] = new ResultDataField('string',$field[0],$val,$field[1],$field[2]);
                        } else {
                            $data['info'.$key] = new ResultDataField('string','info'.$key,$val,$type,$type);
                        }
                    }
                }
/*
                if (preg_match_all("/<div class=\"avatar-list__description\"><a class=\"link i-bem\" [^h]+href=\"([^\"]+)\"/",$record,$matches)) {
                    foreach($matches[1] as $i => $url)
                        if ($i) {
                            if (sizeof($records)<10) $data['link'.$i] = new ResultDataField('url','Link'.$i,'https:'.$url,'Ссылка '.$i,'Ссылка на страницу в соцсети '.$i);
                            if (preg_match("/my.mail.ru\/([^\/]+)\/(.*?)$/",$url,$matches2)) {
                                $data['email'.$i] = new ResultDataField('email','Email'.$i,$matches2[2].'@'.$matches2[1].'.ru','Email '.$i,'Email '.$i);
                            }                        
                        }
                }
*/
                $resultData->addResult($data);
            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();
        } else {
	    if(!$error && $swapData['iteration']>3){
                     $error = 'Невозможно обработать ответ';
                     file_put_contents('./logs/people/people_err_' . time() . '.html', $content);
	    }
	}
        $rContext->setSwapData($swapData);

        if(!$error && $swapData['iteration']>10)
            $error='Превышено количество попыток получения ответа';

        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        return true;
    }
}

?>