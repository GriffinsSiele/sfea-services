<?php

class VKPlugin_new implements PluginInterface
{
    private $names = [
                           'День рождения' => ['birthday', 'День рождения', 'День рождения'],
                           'Родной город' => ['birthplace', 'Родной город', 'Родной город'],
                           'Город' => ['place', 'Город', 'Город'],
                           'Образование' => ['education', 'Образование', 'Образование'],
                           'Место учёбы' => ['school', 'Место учёбы', 'Место учёбы'],
                           'Военная служба' => ['army', 'Военная служба', 'Военная служба'],
                           'Карьера' => ['career', 'Карьера', 'Карьера'],
                           'Место работы' => ['job', 'Место работы', 'Место работы'],
                           'Места' => ['places', 'Места', 'Места'],
                           'Дом' => ['livingplace', 'Город проживания', 'Город проживания'],
                           'Языки' => ['languages', 'Языки', 'Языки'],
                           'Основная информация' => ['common', 'Основная информация', 'Основная информация', 'block'],
                           'Контактная информация' => ['contacts', 'Контакты', 'Контакты', 'block'],
                           'Моб. телефон' => ['mobile_phone', 'Мобильный телефон', 'Мобильный телефон'/* ,'phone' */],
                           'Доп. телефон' => ['other_phone', 'Дополнительный телефон', 'Дополнительный телефон'/* ,'phone' */],
                           'Skype' => ['skype', 'Skype', 'Skype', 'skype'],
                           'Веб-сайт' => ['website', 'Сайт', 'Сайт', 'url:recursive'],
                           'Сайт' => ['website', 'Сайт', 'Сайт', 'url:recursive'],
                           'Twitter' => ['twitter', 'Twitter', 'Twitter', 'url:recursive'],
                           'Facebook' => ['facebook', 'Facebook', 'Facebook', 'url:recursive'],
                           'Instagram' => ['instagram', 'Instagram', 'Instagram', 'url:recursive'],
                           'LiveJournal' => ['livejournal', 'LiveJournal', 'LiveJournal', 'url:recursive'],
                           'Личная информация' => ['personal_info', 'Личная информация', 'Личная информация', 'block'],
                           'О себе' => ['about', 'О себе', 'О себе'],
                           'Деятельность' => ['activities', 'Деятельность', 'Деятельность'],
                           'Интересы' => ['interests', 'Интересы', 'Интересы'],
                           'Любимые книги' => ['favorite_books', 'Любимые книги', 'Любимые книги'],
                           'Любимые фильмы' => ['favorite_films', 'Любимые фильмы', 'Любимые фильмы'],
                           'Любимые игры' => ['favorite_games', 'Любимые игры', 'Любимые игры'],
                           'Любимые телешоу' => ['favorite_teleshows', 'Любимые телешоу', 'Любимые телешоу'],
                           'Любимые цитаты' => ['favorite_quotes', 'Любимые цитаты', 'Любимые цитаты'],
                           'Любимая музыка' => ['favorite_music', 'Любимые музыка', 'Любимые музыка'],
                           'Группы' => ['groups', 'Группы', 'Группы'],
                           'Жизненная позиция' => ['viewpoint', 'Жизненная позиция', 'Жизненная позиция'],
                           'Семейное положение' => ['family', 'Семейное положение', 'Семейное положение'],
                           'Родители' => ['parents', 'Родители', 'Родители'],
                           'Мать' => ['mother', 'Мать', 'Мать'],
                           'Отец' => ['father', 'Отец', 'Отец'],
                           'Бабушка' => ['grandmother', 'Бабушка', 'Бабушка'],
                           'Дедушка' => ['grandfather', 'Дедушка', 'Дедушка'],
                           'Дедушки, бабушки' => ['grandparents', 'Дедушки, бабушки', 'Дедушки, бабушки'],
                           'Брат' => ['relatives', 'Родственники', 'Родственники'],
                           'Братья' => ['relatives', 'Родственники', 'Родственники'],
                           'Сестра' => ['relatives', 'Родственники', 'Родственники'],
                           'Сёстры' => ['relatives', 'Родственники', 'Родственники'],
                           'Братья, сёстры' => ['relatives', 'Родственники', 'Родственники'],
                           'Дочь' => ['children', 'Дети', 'Дети'],
                           'Сын' => ['children', 'Дети', 'Дети'],
                           'Дети' => ['children', 'Дети', 'Дети'],
                           'Внук' => ['grandchildren', 'Внуки', 'Внуки'],
                           'Внучка' => ['grandchildren', 'Внуки', 'Внуки'],
                           'Внуки' => ['grandchildren', 'Внуки', 'Внуки'],
                           'Друзья' => ['friends', 'Друзья', 'Друзья'],
                           'Друзья онлайн' => ['friends_online', 'Друзья онлайн', 'Друзья онлайн'],
                           'Подарки' => ['gifts', 'Подарки', 'Подарки'],
                           'Интересные' => ['interests', 'Интересные страницы', 'Интересные страницы'],
                           'Фотографии' => ['photos', 'Фотографии', 'Фотографии'],
                           'Фотоальбомы' => ['albums', 'Фотоальбомы', 'Фотоальбомы'],
                           'Аудиозаписи' => ['audios', 'Аудиозаписи', 'Аудиозаписи'],
                           'Видеозаписи' => ['videos', 'Видеозаписи', 'Видеозаписи'],
                           'подписчик' => ['subscribers', 'Подписчики', 'Подписчики'],
                           'подписчика' => ['subscribers', 'Подписчики', 'Подписчики'],
                           'подписчиков' => ['subscribers', 'Подписчики', 'Подписчики'],
                           'отметка' => ['marks', 'Отметки', 'Отметки'],
                           'отметки' => ['marks', 'Отметки', 'Отметки'],
                           'отметок' => ['marks', 'Отметки', 'Отметки'],
                           'друг' => ['friends', 'Друзья', 'Друзья'],
                           'друга' => ['friends', 'Друзья', 'Друзья'],
                           'друзей' => ['friends', 'Друзья', 'Друзья'],
                           'запись' => ['posts', 'Публикации', 'Публикации'],
                           'записи' => ['posts', 'Публикации', 'Публикации'],
                           'записей' => ['posts', 'Публикации', 'Публикации'],
                           'фотография' => ['photos', 'Фотографии', 'Фотографии'],
                           'фотографии' => ['photos', 'Фотографии', 'Фотографии'],
                           'фотографий' => ['photos', 'Фотографии', 'Фотографии'],
                           'видеозапись' => ['videos', 'Видеозаписи', 'Видеозаписи'],
                           'видеозаписи' => ['videos', 'Видеозаписи', 'Видеозаписи'],
                           'видеозаписей' => ['videos', 'Видеозаписи', 'Видеозаписи'],
                           'аудиозапись' => ['audios', 'Аудиозаписи', 'Аудиозаписи'],
                           'аудиозаписи' => ['audios', 'Аудиозаписи', 'Аудиозаписи'],
                           'аудиозаписей' => ['audios', 'Аудиозаписи', 'Аудиозаписи'],
    ];

    public function str_uprus($text)
    {
        $up = [
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
        ];
        if (\preg_match('/[а-я]/', $text)) {
            $text = \strtr($text, $up);
        }

        return $text;
    }

    public function str_translit($text)
    {
        $trans = [
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
        ];
        $text = $this->str_uprus($text);
        if (\preg_match('/[А-Я]/', $text)) {
            $text = \strtr($text, $trans);
        }

        return $text;
    }

    public function getName()
    {
        return 'VK';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск в VK',
            'vk_person' => 'VK - поиск профилей по имени и дате рождения',
            'vk_phone' => 'VК - поиск профилей по номеру телефона',
            'vk_email' => 'VК - поиск профилей по email',
            'vk_url' => 'VК - профиль пользователя',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в VK';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query('UPDATE isphere.session s SET request_id='.$reqId.' WHERE request_id IS NULL AND sessionstatusid=2 AND (s.proxyid IS NULL OR (SELECT status FROM proxy WHERE id=s.proxyid)=1) AND sourceid=4 AND unix_timestamp(now())-unix_timestamp(lasttime)>3 ORDER BY lasttime limit 1');
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=4 AND request_id=".$reqId);

        if ($result) {
            $row = $result->fetch_object();

            if ($row) {
                $sessionData = new \stdClass();

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;

                $mysqli->query('UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,request_id=NULL WHERE id='.$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = \substr($initData['checktype'], 3);

        if ('email' == $checktype && !isset($initData['email'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (email)');

            return false;
        }

        if ('phone' == $checktype && !isset($initData['phone'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (телефон)');

            return false;
        }

        if ('url' == $checktype && !isset($initData['url'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (ссылка)');

            return false;
        }

        if ('nick' == $checktype && !isset($initData['nick'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (псевдоним)');

            return false;
        }

        if ('person' == $checktype && (!isset($initData['last_name']) || !isset($initData['first_name']))) {
            $rContext->setFinished();
            //            $rContext->setError('Не указаны параметры для поиска (фамилия+имя)');

            return false;
        }

        if ('text' == $checktype && !isset($initData['text'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска');

            return false;
        }

        if (isset($initData['phone'])) {
            //            if (strlen($initData['phone'])==10)
            //                $initData['phone']='7'.$initData['phone'];
            //            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            //                $initData['phone']='7'.substr($initData['phone'],1);
            $swapData['phone'] = $initData['phone'];
            /*
                        if(substr($initData['phone'],0,2)!='79')
                        {
                            $rContext->setFinished();
                            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');

                            return false;
                        }
            */
        }

        if (isset($initData['email'])) {
            $swapData['email'] = $initData['email'];
        }

        if ('nick' == $checktype) {
            $swapData['path'] = 'https://vk.com/'.$initData['nick'];
        }

        if ('url' == $checktype) {
            if (false === \strpos($initData['url'], 'vk.com/')) {
                $rContext->setFinished();

                return false;
            }
            $swapData['path'] = $initData['url'];
        }
        $rContext->setSwapData($swapData);

        if ('phone' == $checktype) {
            $resultData = new ResultDataList();
            $result = $mysqli->query('SELECT DISTINCT vkId FROM vk.phones WHERE phone='.$swapData['phone'].' LIMIT 10');
            $i = 0;
            while ($result && ($row = $result->fetch_object())) {
                //                $swapData['path'] = 'https://vk.com/id'.$row->vkId;
                $data = [];
                $data['link'.($i++)] = new ResultDataField('url:recursive', 'Link', 'https://vk.com/id'.$row->vkId, 'Ссылка', 'Ссылка на профиль');
                $resultData->addResult($data);
            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        }

        if ('email' == $checktype) {
            $resultData = new ResultDataList();
            $result = $mysqli->query("SELECT DISTINCT vkId FROM vk.emails WHERE email='".$swapData['email']."' LIMIT 10");
            $i = 0;
            while ($result && ($row = $result->fetch_object())) {
                //                $swapData['path'] = 'https://vk.com/id'.$row->vkId;
                $data = [];
                $data['link'.($i++)] = new ResultDataField('url:recursive', 'Link', 'https://vk.com/id'.$row->vkId, 'Ссылка', 'Ссылка на профиль');
                $resultData->addResult($data);
            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        }
        /*
                if ($checktype!='phone' && $checktype!='email') {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                    return false;
                }
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////

        if (!isset($swapData['session']) && ('phone' != $checktype && 'email' != $checktype)) {
            $swapData['session'] = $this->getSessionData();

            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration'] >= 30)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }

                return false;
            }
            $swapData['iteration'] = 1;
            $rContext->setSwapData($swapData);
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://vk.com/';
        if ('phone' == $checktype || 'email' == $checktype) {
            $url = 'https://i-sphere.ru/';
        } elseif (!isset($swapData['path'])) {
            $params = [
                'c[per_page]' => 40,
                'c[name]' => 1,
                'c[section]' => 'people',
            ];
            if ('person' == $checktype) {
                $params['c[q]'] = $this->str_translit($initData['last_name'].' '.$initData['first_name']);
                if (isset($initData['date']) && \strtotime($initData['date'])) {
                    $initData['date'] = \date('d.m.Y', \strtotime($initData['date']));
                    $birth = \explode('.', $initData['date']);
                    $params['c[bday]'] = $birth[0];
                    $params['c[bmonth]'] = $birth[1];
                    $params['c[byear]'] = $birth[2];
                } elseif (isset($initData['date']) && \preg_match("/^[0-3]*\d\.[0-1]*\d$/", $initData['date'])) {
                    $birth = \explode('.', $initData['date']);
                    $params['c[bday]'] = $birth[0];
                    $params['c[bmonth]'] = $birth[1];
                } elseif (isset($initData['date']) && \preg_match("/^[1-2][\d]{3}$/", $initData['birth'])) {
                    $params['c[byear]'] = $initData['birth'];
                }
            } elseif ('text' == $checktype) {
                $params['c[q]'] = $this->str_translit($initData['text']);
            } elseif ('phone' == $checktype) {
                $params['c[q]'] = $initData['phone'];
            } elseif ('email' == $checktype) {
                $params['c[q]'] = $initData['email'];
            }
            $url = 'https://vk.com/search?'.\http_build_query($params);
        } elseif (!isset($swapData['uid'])) {
            $url = $swapData['path'];
        } elseif (isset($swapData['instagram_url'])) {
            $url = $swapData['instagram_url'];
        } elseif (!isset($swapData['foaf'])) {
            $url .= 'foaf.php?id='.$swapData['uid'];
        } else {
            $url .= 'al_places.php?al=1&act=photos_box&uid='.$swapData['uid'];
            /*
                        $params = array(
                            'uid'=> $swapData['uid'],
                            'al' => '1',
                            'act' => 'photos_box',
                        );
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            */
        }
        \curl_setopt($ch, \CURLOPT_URL, $url);
        //        curl_setopt($ch, CURLOPT_REFERER, $url);
        if (\strpos($url, 'vk.com/')) {
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            //            print "$url\n";
            //            print "Cookie: ".$swapData['session']->cookies."\n";
            if ($swapData['session']->proxy) {
                \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
                //                print "Proxy: ".$swapData['session']->proxy."\n";
                if ($swapData['session']->proxy_auth) {
                    \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                    \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
                }
            }
        }
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = \substr($initData['checktype'], 3);

        $error = false;
        $curl_error = \curl_error($rContext->getCurlHandler());
        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;

        if (!$curl_error) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());

            if (!isset($swapData['path'])) {
                \file_put_contents('./logs/vk/vk_search_'.\time().'.html', $content);
                $content = \iconv('windows-1251', 'utf-8//ignore', $content);

                $found = 0;
                if (\preg_match("/=\"page_block_header_count\">(.*?)<\/div>/", $content, $matches)) {
                    $found = (int) \strtr(\strip_tags($matches[1]), ['&nbsp;' => '', "\u{a0}" => '', ' ' => '']);
                }
                if ($found > 20) {
                    //                    $error = "Найдено слишком много совпадений ($found)";
                    $error = 'Найдено слишком много совпадений';
                    if (!isset($initData['date'])) {
                        $error .= '. Попробуйте указать в запросе дату рождения';
                    }
                    //                    elseif (!isset($initData['location'])) $error.=". Попробуйте указать в запросе местонахождение";
                    if (0 == $rContext->getLevel()) {
                        $rContext->setError($error);
                    }
                    $rContext->setFinished();
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    }
                } elseif (false !== \strpos($content, '<div id="results" class="search_results search_people_results page_block')) {
                    $records = \explode('<div class="people_row search_row clear_fix', $content);
                    \array_shift($records);

                    $resultData = new ResultDataList();
                    foreach ($records as $record) {
                        $data = [];
                        if (\preg_match('/<div class="labeled name"><a[ ]+href="([^"]+)"/', $record, $matches)) {
                            $swapData['path'] = 'https://vk.com'.$matches[1];
                            $data['link'] = new ResultDataField('url'.(\count($records) < 10 ? ':recursive' : ''), 'Link', $swapData['path'], 'Ссылка', 'Ссылка на профиль');
                        }
                        if (\preg_match("/<div class=\"labeled name\"><a[ ]+href=[^>]+>(.*?)<\/a>/", $record, $matches)) {
                            $data['name'] = new ResultDataField('string', 'Name', \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', \strip_tags($matches[1]))), 'Имя пользователя', 'Имя пользователя');
                        }
                        if (\preg_match('/<img class="search_item_img" src="([^"]+)"/', $record, $matches) && (false === \strpos($matches[1], 'camera_'))) {
                            $data['avatar'] = new ResultDataField('image', 'Avatar', (false === \strpos($matches[1], '://') ? 'https://vk.com' : '').$matches[1], 'Аватар', 'Аватар');
                        }
                        if (\preg_match("/<div class=\"labeled \">([^<]+)<\/div>/", $record, $matches)) {
                            //                            $data['place'] = new ResultDataField('string','Place',strip_tags($matches[1]),'Город','Город');
                        }
                        /*
                                                if(preg_match("/data-id=\"([^\"]+)\"/",$record, $matches)){
                                                    $uid = $matches[1];
                                                    $data['uid'] = new ResultDataField('string','uid',$uid,'ID пользователя','ID пользователя');

                                                    $result = $mysqli->query("SELECT DISTINCT phone FROM vk.phones WHERE vkId=".$uid." LIMIT 10");
                                                    $i = 0;
                                                    while ($result && ($row = $result->fetch_object())) {
                                                        if ($phone_number = preg_replace("/\D/","",$row->phone)) {
                                                            if ((strlen($phone_number)==11) && (substr($phone_number,0,1)=='8'))
                                                                $phone_number = substr($phone_number,1);
                                                            if (strlen($phone_number)==10)
                                                                $phone_number = '7'.$phone_number;
                                                            $data['phone'.($i++)] = new ResultDataField('phone','Phone',$phone_number,'Телефон','Телефон');
                                                        }
                                                    }

                                                    $result = $mysqli->query("SELECT DISTINCT email FROM vk.emails WHERE vkId=".$uid." LIMIT 10");
                                                    $i = 0;
                                                    while ($result && ($row = $result->fetch_object())) {
                                                        $data['email'.($i++)] = new ResultDataField('email','Email',$row->email,'E-mail','E-mail');
                                                    }
                                                }
                        */
                        $swapData['data'] = $data;
                        $resultData->addResult($data);
                    }
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    }
                } elseif (!$content) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='empty' WHERE used-ifnull(success,0)>=10 AND id=".$swapData['session']->id);
                    }
                    unset($swapData['session']);
                    $rContext->setSwapData($swapData);

                    if ($swapData['iteration'] > 3) {
                        $error = 'Невозможно обработать ответ';
                    }
                } else {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='invalidanswer' WHERE id=".$swapData['session']->id);
                    }
                    unset($swapData['session']);
                    $rContext->setSwapData($swapData);

                    if ($swapData['iteration'] > 3) {
                        $error = 'Невозможно обработать ответ';
                    }
                    \file_put_contents('./logs/vk/vk_search_err_'.\time().'.html', $content);
                }
            } elseif (!isset($swapData['uid'])) {
                //                $data = $swapData['data'];
                $data = [];
                $data['link'] = new ResultDataField('url:recursive', 'Link', $swapData['path'], 'Ссылка', 'Ссылка на профиль');

                \file_put_contents('./logs/vk/vk'.\time().'.html', $content);
                $content = \iconv('windows-1251', 'utf-8//ignore', $content);

                if (empty(\trim($content))) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='empty' WHERE used-ifnull(success,0)>=10 AND id=".$swapData['session']->id);
                    }
                    unset($swapData['session']);
                    $rContext->setSwapData($swapData);

                    return false;
                }

                if (\preg_match('/<div class="login_blocked_about">/', $content)) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 year),sessionstatusid=6,statuscode='blocked' WHERE id=".$swapData['session']->id);
                    }
                    unset($swapData['session']);
                    $rContext->setSwapData($swapData);

                    return false;
                }

                if (\preg_match('/<title>404 Not Found/', $content)) {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    }

                    return true;
                }

                if (\preg_match('/<h2 class="page_name">([^<]+)/', $content, $matches)) {
                    $data['name'] = new ResultDataField('string', 'Name', \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', \html_entity_decode(\strtr($matches[1], ['  ' => ' '])))), 'Имя пользователя', 'Имя пользователя');
                }
                if (\preg_match("/<div class=\"page_current_info\" id=\"page_current_info\">(.*?)<\/div>/", $content, $matches)) {
                    $data['info'] = new ResultDataField('string', 'Info', \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', \html_entity_decode(\strip_tags($matches[1])))), 'Информация', 'Информация');
                }
                $swapData['uid'] = '_';
                if (\preg_match("/\{\"user_id\":([^,]+),/", $content, $matches) || \preg_match("/\"loc\":\"\?id=([^\"]+)\"/", $content, $matches)) {
                    $matches[1] = (int) $matches[1];
                    $data['uid'] = new ResultDataField('integer', 'uid', $matches[1], 'ID пользователя', 'ID пользователя');
                    $swapData['uid'] = $matches[1];
                    if (!isset($initData['phone'])) {
                        $result = $mysqli->query('SELECT DISTINCT phone FROM vk.phones WHERE vkId='.$swapData['uid'].' LIMIT 10');
                        $i = 0;
                        while ($result && ($row = $result->fetch_object())) {
                            if ($phone_number = \preg_replace("/\D/", '', $row->phone)) {
                                if ((11 == \strlen($phone_number)) && ('8' == \substr($phone_number, 0, 1))) {
                                    $phone_number = \substr($phone_number, 1);
                                }
                                if (10 == \strlen($phone_number)) {
                                    $phone_number = '7'.$phone_number;
                                }
                                $data['phone'.($i++)] = new ResultDataField('phone', 'Phone', $phone_number, 'Телефон', 'Телефон');
                            }
                        }
                    }
                    if (!isset($initData['email'])) {
                        $result = $mysqli->query('SELECT DISTINCT email FROM vk.emails WHERE vkId='.$swapData['uid'].' LIMIT 10');
                        $i = 0;
                        while ($result && ($row = $result->fetch_object())) {
                            $data['email'.($i++)] = new ResultDataField('email', 'Email', $row->email, 'E-mail', 'E-mail');
                        }
                    }
                } else {
                    $swapData['foaf'] = true;
                }
                //                if(preg_match("/<a id=\"profile_photo_link\"[^>]+><img .*? src=\"([^\"]+)\"/", $content, $matches)){
                if (\preg_match("/<a id=\"profile_photo_link\"[^{]+\{[^{]+(\{[^}]+\})/", $content, $matches)) {
                    $a = \json_decode(\html_entity_decode($matches[1]), true);
                    if (\is_array($a) && isset($a['x'])) {
                        $img = isset($a['w']) ? $a['w'] : (isset($a['z']) ? $a['z'] : (isset($a['y']) ? $a['y'] : $a['x'])); // array_pop($a)[0];
                        $data['photo'] = new ResultDataField('image', 'Photo', (false === \strpos($img, '://') ? (isset($a['base']) && \strpos($a['base'], '://') ? $a['base'] : 'https://vk.com') : '').$img, 'Фото профиля', 'Фото профиля');
                    }
                }
                if (\preg_match('/<div class="profile_online_lv">([^<]+)/', $content, $matches)) {
                    $data['last_visited'] = new ResultDataField('string', 'last_visited', \strip_tags($matches[1]), 'Время последнего посещения', 'Время последнего посещения');
                }
                if (\preg_match('/<b class="mob_onl profile_mob_onl"/', $content)) {
                    $data['presence'] = new ResultDataField('string', 'presence', 'mobile', 'Присутствие', 'Присутствие');
                }
                if (\preg_match('/<h5 class="profile_blocked/', $content, $matches)) {
                    $data['blocked'] = new ResultDataField('string', 'blocked', 'true', 'Заблокирован', 'Заблокирован');
                }
                if (\preg_match('/<input type="hidden" id="page_wall_count_all" value="([^"]+)/', $content, $matches)) {
                    $data['posts'] = new ResultDataField('integer', 'posts', \strip_tags($matches[1]), 'Публикации', 'Публикации');
                }
                if (\preg_match('/<input type="hidden" id="page_wall_count_own" value="([^"]+)/', $content, $matches)) {
                    $data['own_posts'] = new ResultDataField('integer', 'own_posts', \strip_tags($matches[1]), 'Собственные публикации', 'Собственные публикации');
                }
                if (\preg_match('/<span class="rel_date">([^<]+)/', $content, $matches)) {
                    $data['last_posted'] = new ResultDataField('string', 'last_posted', \strip_tags($matches[1]), 'Время последней записи', 'Время последней записи');
                }
                $short_info = '';
                $info = '';
                if (\preg_match('/<div class="profile_info profile_info_short" id="profile_short">(.*?)<div class="profile_more_info">/sim', $content, $matches)) {
                    $short_info = $matches[1];
                } elseif (\preg_match('/<div class="profile_info profile_info_short" id="profile_short">(.*?)<div class="profile_info profile_info_full" id="profile_full">/sim', $content, $matches)) {
                    $short_info = $matches[1];
                }
                if (\preg_match("/<div class=\"profile_info profile_info_full\" id=\"profile_full\">(.*?)<\/div><div class=\"counts_module\">/sim", $content, $matches)) {
                    $info = $matches[1];
                }
                $counter = 0;
                if ($short_info) {
                    if (\preg_match_all("/<div class=\"label fl_l\">(.*?)<\/div>.*?<div class=\"labeled\">(.*?)<\/div>/sim", $short_info, $matches)) {
                        foreach ($matches[1] as $key => $val) {
                            $title = \str_replace(':', '', $val);
                            $text = \str_replace('&#039;', "'", \html_entity_decode(\strip_tags($matches[2][$key])));
                            $text = \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $text));
                            $urls = [];
                            if (\preg_match_all("/<a href=\"\/away.php\?to=[^\&]+\&[^>]+>([^<]+)</sim", $matches[2][$key], $url)) {
                                foreach ($url[1] as $u) {
                                    $urls[] = \urldecode($u);
                                }
                            }
                            if (\preg_match_all('/<a class="mem_link" href="([^"]+)"/sim', $matches[2][$key], $url)) {
                                foreach ($url[1] as $u) {
                                    $urls[] = 'https://vk.com'.$u;
                                }
                            }

                            if (isset($this->names[$title])) {
                                $field = $this->names[$title];
                                if ('birthday' == $field[0]) {
                                    $repl = [' г.' => '', ' ' => '.',
                                        'января' => '01', 'февраля' => '02', 'марта' => '03', 'апреля' => '04', 'мая' => '05', 'июня' => '06',
                                        'июля' => '07', 'августа' => '08', 'сентября' => '09', 'октября' => '10', 'ноября' => '11', 'декабря' => '12'];
                                    if (1 == \strpos($text, ' ')) {
                                        $text = '0'.$text;
                                    }
                                    $text = \strtr($text, $repl);
                                }
                                if (isset($field[3]) && ('url' == $field[3] || 'url:recursive' == $field[3])) {
                                    foreach ($urls as $i => $url) {
                                        $data[$field[0].($i ?: '')] = new ResultDataField($field[3], $field[0], $url, $field[1], $field[2]);
                                    }
                                } else {
                                    $data[$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $text, $field[1], $field[2]);
                                    foreach ($urls as $i => $url) {
                                        $data[$field[0].'_link'.($i ?: '')] = new ResultDataField('url', $field[0].'_link', $url, $field[1], $field[2]);
                                    }
                                }
                            } else {
                                ++$counter;
                                $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
                                //                                                 file_put_contents('./logs/fields/vk'.time().'_'.$title , $title."\n".$text);
                            }
                        }
                    }
                }
                if ($info) {
                    $infoarr = \explode('<div class="profile_info_block clear_fix">', $info);
                    foreach ($infoarr as $val) {
                        if (\preg_match('/<span class="profile_info_header">([^<]+)/', $val, $matches)) {
                            $title = \str_replace(':', '', $matches[1]);
                            $rows = [];
                            $textrows = [];
                            if (\preg_match_all("/<div class=\"label fl_l\">(.*?)<\/div>.*?<div class=\"labeled\">(.*?)<\/div>/sim", $val, $matches)) {
                                foreach ($matches[1] as $k => $v) {
                                    $v = \strip_tags(\str_replace(':', '', $v));
                                    $t = \str_replace('&#039;', "'", \html_entity_decode(\strip_tags(\str_replace('<br>', ', ', $matches[2][$k]))));
                                    $t = \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $t));
                                    $u = [];

                                    if (\preg_match_all('/<a href="([^"]+)"/sim', $matches[2][$k], $urls)) {
                                        foreach ($urls[1] as $url) {
                                            if (0 === \strpos($url, 'http')) {
                                                $u[] = $url;
                                            }
                                        }
                                    }

                                    if (\preg_match_all('/<a class="mem_link" href="([^"]+)"/sim', $matches[2][$k], $urls)) {
                                        foreach ($urls[1] as $url) {
                                            $u[] = 'https://vk.com'.$url;
                                        }
                                    }
                                    $textrows[] = $v.': '.$t;
                                    $rows[] = [$v, $t, $u];
                                }
                            }
                            $text = \implode(";\n", $textrows);
                            if (isset($this->names[$title])) {
                                $field = $this->names[$title];
                                if (isset($field[3]) && ('block' == $field[3])) {
                                    foreach ($rows as $row) {
                                        $rowtitle = $row[0];
                                        $rowtext = $row[1];
                                        $rowurl = $row[2];
                                        if (isset($this->names[$rowtitle])) {
                                            $rowfield = $this->names[$rowtitle];
                                            $f = $rowfield[0];
                                            if (isset($data[$f])) {
                                                for ($i = 1; isset($data[$f.$i]); ++$i) {
                                                }
                                                $f .= $i;
                                            }
                                            if (isset($rowfield[3]) && ('url' == $rowfield[3] || 'url:recursive' == $rowfield[3])) {
                                                foreach ($rowurl as $i => $url) {
                                                    $data[$f.($i ?: '')] = new ResultDataField($rowfield[3], $rowfield[0], $url, $rowfield[1], $rowfield[2]);
                                                }
                                            } else {
                                                $rowval = $rowtext;
                                                if (isset($rowfield[3]) && ('phone' == $rowfield[3]) && $rowval = \preg_replace("/\D/", '', $rowval)) {
                                                    if ((11 == \strlen($rowval)) && ('8' == \substr($rowval, 0, 1))) {
                                                        $rowval = \substr($rowval, 1);
                                                    }
                                                    if (10 == \strlen($rowval)) {
                                                        $rowval = '7'.$rowval;
                                                    }
                                                }
                                                if ($rowval && ('Информация скрыта' != $rowval) && ('Информация отсутствует' != $rowval)) {
                                                    $data[$f] = new ResultDataField(isset($rowfield[3]) ? $rowfield[3] : 'string', $rowfield[0], $rowval, $rowfield[1], $rowfield[2]);
                                                }
                                                foreach ($rowurl as $i => $url) {
                                                    $data[$f.'_link'.($i ?: '')] = new ResultDataField('url', $rowfield[0].'_link', $url, $rowfield[1], $rowfield[2]);
                                                }
                                            }
                                        } elseif ($rowtext && ('Информация скрыта' != $rowtext) && ('Информация отсутствует' != $rowtext)) {
                                            ++$counter;
                                            $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $rowtext, $rowtitle, $rowtitle);
                                            \file_put_contents('./logs/fields/vk'.\time().'_'.$rowtitle, $rowtitle."\n".$rowtext);
                                        }
                                    }
                                } elseif ($text && ('Информация скрыта' != $text) && ('Информация отсутствует' != $text)) {
                                    $data[$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $text, $field[1], $field[2]);
                                }
                            } elseif ($text && ('Информация скрыта' != $text) && ('Информация отсутствует' != $text)) {
                                ++$counter;
                                $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
                                \file_put_contents('./logs/fields/vk'.\time().'_'.$title, $title."\n".$text);
                            }
                        }
                    }
                }
                if (\preg_match_all("/<span class=\"right_link fl_r\"[^<]+<\/span>[^<]+<span class=\"header_label fl_l\">([^<]+)<\/span>[^<]+<span class=\"header_count fl_l\">([^<]+)<\/span>/", $content, $matches)) {
                    foreach ($matches[1] as $key => $val) {
                        $title = \trim(\strip_tags($val));
                        if (\strpos($title, ' ')) {
                            $title = \substr($title, 0, \strpos($title, ' '));
                        }
                        $text = \str_replace('&#039;', "'", \html_entity_decode(\trim(\strip_tags($matches[2][$key]))));
                        //                                        if (strpos($text,' ')) $text = substr($text,0,strpos($text,' '));
                        if (isset($this->names[$title])) {
                            $field = $this->names[$title];
                            $data[$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $text, $field[1], $field[2]);
                        } elseif ($text && ('Информация скрыта' != $text) && ('Информация отсутствует' != $text)) {
                            ++$counter;
                            $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
                            \file_put_contents('./logs/fields/vk'.\time().'_'.$title, $title."\n".$text);
                        }
                    }
                }
                if (\preg_match_all("/<div class=\"count\">([^<]+)<\/div>[^<]+<div class=\"label\">([^<]+)<\/div>/", $content, $matches)) {
                    foreach ($matches[1] as $key => $val) {
                        $title = \str_replace('&#039;', "'", \html_entity_decode(\trim(\strip_tags($matches[2][$key]))));
                        $text = \trim(\strip_tags($val));
                        //                                        if (strpos($text,' ')) $text = substr($text,0,strpos($text,' '));
                        if (isset($this->names[$title])) {
                            $field = $this->names[$title];
                            $data[$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $text, $field[1], $field[2]);
                        }
                        /*
                                                                else{
                                                                         $counter++;
                                                                         $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
                                                                         file_put_contents('./logs/fields/vk'.time().'_'.$title , $title."\n".$text);
                                                                }
                        */
                    }
                }
                if (\preg_match("/postTooltip\(this, \'".$swapData['uid']."_[^\)]+\)\" href=\"\/away.php\?to=([^\"]+)\"/", $content, $matches)) {
                    $swapData['instagram_url'] = \urldecode($matches[1]);
                }

                $swapData['data'] = $data;
            } elseif (isset($swapData['instagram_url'])) {
                $data = $swapData['data'];
                //                file_put_contents('./logs/vk/vkinstagram'.time().'.html',$content);
                if (\preg_match("/\"owner\": (\{[^\}]+\})/", $content, $matches)) {
                    $owner = \json_decode($matches[1], true);
                    if (isset($owner['name'])/* && !isset($data['instagram']) */) {
                        $data['instagram'] = new ResultDataField('url:recursive', 'instagram', 'https://instagram.com/'.$owner['name'], 'Instagram', 'Instagram');
                    }
                    if (isset($owner['profile_pic_url'])) {
                        $data['instagram_photo'] = new ResultDataField('image', 'instagram_photo', $owner['profile_pic_url'], 'Аватар Instagram', 'Аватар Instagram');
                    }
                    if (isset($owner['full_name'])) {
                        $data['instagram_name'] = new ResultDataField('string', 'instagram_name', $owner['full_name'], 'Имя Instagram', 'Имя Instagram');
                    }
                }
                unset($swapData['instagram_url']);
                $swapData['data'] = $data;
            } elseif (!isset($swapData['foaf'])) {
                $data = $swapData['data'];
                \file_put_contents('./logs/vk/vkfoaf_'.\time().'.xml', $content);
                try {
                    $foaf = '<?xml' == \substr($content, 0, 5) ? \simplexml_load_string(\strtr($content, ['&' => '&amp;'])) : false;
                } catch (Exception $e) {
                    $foaf = false;
                }
                if (false !== $foaf) {
                    if (isset($foaf->xpath('//foaf:name')[0]) && !isset($data['name'])) {
                        $data['name'] = new ResultDataField('string', 'Name', \trim(\html_entity_decode($foaf->xpath('//foaf:name')[0])), 'Имя пользователя', 'Имя пользователя');
                    }
                    if (isset($foaf->xpath('//ya:firstName')[0])) {
                        $data['firstname'] = new ResultDataField('string', 'FirstName', \trim(\html_entity_decode(\iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $foaf->xpath('//ya:firstName')[0])))), 'Имя', 'Имя');
                    }
                    if (isset($foaf->xpath('//ya:secondName')[0])) {
                        $data['lastname'] = new ResultDataField('string', 'LastName', \trim(\html_entity_decode(\iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $foaf->xpath('//ya:secondName')[0])))), 'Фамилия', 'Фамилия');
                    }
                    if (isset($foaf->xpath('//foaf:nick')[0])) {
                        $data['nick'] = new ResultDataField('nick', 'NickName', \html_entity_decode($foaf->xpath('//foaf:nick')[0]), 'Псевдоним', 'Псевдоним');
                    }

                    if (isset($foaf->xpath('//ya:created/@dc:date')[0])) {
                        $data['created'] = new ResultDataField('datetime', 'Created', $foaf->xpath('//ya:created/@dc:date')[0], 'Зарегистрирован', 'Зарегистрирован');
                    }
                    if (isset($foaf->xpath('//ya:lastLoggedIn/@dc:date')[0])) {
                        $data['logged'] = new ResultDataField('datetime', 'Logged', $foaf->xpath('//ya:lastLoggedIn/@dc:date')[0], 'Последний вход', 'Последний вход');
                    }
                    if (isset($foaf->xpath('//ya:modified/@dc:date')[0])) {
                        $data['modified'] = new ResultDataField('datetime', 'Modified', $foaf->xpath('//ya:modified/@dc:date')[0], 'Последнее изменение', 'Последнее изменение');
                    }

                    if (isset($foaf->xpath('//ya:publicAccess')[0])) {
                        $data['publicaccess'] = new ResultDataField('string', 'PublicAccess', $foaf->xpath('//ya:publicAccess')[0], 'Публичный доступ', 'Публичный доступ');
                    }
                    if (isset($foaf->xpath('//ya:profileState')[0])) {
                        $data['state'] = new ResultDataField('string', 'State', $foaf->xpath('//ya:profileState')[0], 'Статус', 'Статус');
                    }

                    if (isset($foaf->xpath('//foaf:dateOfBirth')[0])) {
                        $data['birthdate'] = new ResultDataField('string', 'BirthDate', \date('d.m.Y', \strtotime($foaf->xpath('//foaf:dateOfBirth')[0])), 'Дата рождения', 'Дата рождения');
                    } elseif (isset($foaf->xpath('//foaf:birthday')[0]) && !isset($data['birthday'])) {
                        $data['birthday'] = new ResultDataField('string', 'BirthDay', $foaf->xpath('//foaf:birthday')[0], 'День рождения', 'День рождения');
                    }
                    if (isset($foaf->xpath('//foaf:gender')[0])) {
                        $data['gender'] = new ResultDataField('string', 'Gender', $foaf->xpath('//foaf:gender')[0], 'Пол', 'Пол');
                    }
                    if (isset($foaf->xpath('//foaf:weblog/@dc:title')[0]) && \strlen($foaf->xpath('//foaf:weblog/@dc:title')[0])) {
                        $data['title'] = new ResultDataField('string', 'Title', \html_entity_decode(\iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $foaf->xpath('//foaf:weblog/@dc:title')[0]))), 'Подпись', 'Подпись');
                    }
                    if (isset($foaf->xpath('//ya:bio')[0]) && \strlen($foaf->xpath('//ya:bio')[0]) && !isset($data['about'])) {
                        $data['about'] = new ResultDataField('string', 'about', \html_entity_decode(\iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $foaf->xpath('//ya:bio')[0]))), 'О себе', 'О себе');
                    }

                    if (isset($foaf->xpath('//foaf:img/foaf:Image/@rdf:about')[0])) {
                        $img = $foaf->xpath('//foaf:img/foaf:Image/@rdf:about')[0];
                        $data['avatar'] = new ResultDataField('image', 'Avatar', (false === \strpos($img, '://') ? 'https://vk.com' : '').$img, 'Аватар', 'Аватар');
                    }
                } else {
                    \file_put_contents('./logs/vk/vkfoaf_err_'.\time().'.xml', $content);
                }
                if (false !== $foaf || $swapData['iteration'] >= 3) {
                    $swapData['foaf'] = true;
                    $swapData['data'] = $data;
                }
            } else {
                $data = $swapData['data'];
                \file_put_contents('./logs/vk/vkplaces'.\time().'.html', $content);
                $content = \iconv('windows-1251', 'utf-8//ignore', $content);
                $places = [];

                if (\preg_match_all("/onclick=\"Places.showPhotoPlace\(([^,]+), ([^,]+), event\);\" onmouseover=\"Places.showPlaceTT\(this, \'([^\']+)\'\)\"/sim", $content, $matches)) {
                    foreach ($matches[1] as $k => $v) {
                        $lat = \round((float) $v, 3);
                        $long = \round((float) $matches[2][$k], 3);
                        $geo = $lat.','.$long;
                        if ($lat && $long) {
                            $places[$geo] = \str_replace('&#039;', "'", \html_entity_decode(\trim(\strip_tags($matches[3][$k]))));
                            $places_count[$geo] = (isset($places_count[$geo]) ? $places_count[$geo] : 0) + 1;
                        }
                    }
                    $map = [];
                    $k = 0;
                    foreach ($places as $geo => $name) {
                        ++$k;
                        //                        $data['photo_place_'.$k] = new ResultDataField('string', 'photo_place['.$k.']', $name, 'Место фотографии '.$k, 'Место фотографии '.$k);
                        //                        $data['photo_place_count_'.$k] = new ResultDataField('string', 'photo_place_count['.$k.']', $places_count[$geo], 'Кол-во фотографий '.$k, 'Кол-во фотографий '.$k);
                        //                        $data['photo_place_geo_'.$k] = new ResultDataField('string', 'photo_place_geo['.$k.']', $geo, 'Координаты фотографий '.$k, 'Координаты фотографий '.$k);
                        $coords = \explode(',', $geo);
                        $coords[0] = +$coords[0];
                        $coords[1] = +$coords[1];
                        $map[] = ['coords' => $coords, 'text' => $name.' ('.$places_count[$geo].')'];
                    }
                    $data['photo_places'] = new ResultDataField('map', 'photo_places', \strtr(\json_encode($map, \JSON_UNESCAPED_UNICODE), ['},{' => "},\n{"]), 'Места фотографий', 'Места фотографий');
                }

                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                if (isset($swapData['session'])) {
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                }
            }
            $rContext->setSwapData($swapData);
        }

        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] > 5) {
            if (isset($swapData['uid'])) {
                $data = $swapData['data'];
                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } else {
                $error = 'Превышено количество попыток получения ответа';
            }
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        return true;
    }
}
