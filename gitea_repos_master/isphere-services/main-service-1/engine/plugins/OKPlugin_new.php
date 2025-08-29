<?php

class OKPlugin implements PluginInterface
{
    private $names = array (
                           'Родился' => array('birthday', 'День рождения', 'День рождения'),
                           'Родилась' => array('birthday', 'День рождения', 'День рождения'),
                           'Живет в' => array('living', 'Место жительства', 'Место жительства'),
                           'Место рождения' => array('birthplace', 'Место рождения', 'Место рождения'),
                           'В браке' => array('spouse', 'Супруг', 'Супруг'),
                           'В отношениях с' => array('mate', 'Партнер', 'Партнер'),
                           'Семейное положение' => array('family', 'Семейное положение', 'Семейное положение'),
//                           'Вчера праздновал' => array('celebrated', 'Праздник', 'Праздник'),
//                           'Вчера праздновала' => array('celebrated', 'Праздник', 'Праздник'),
//                           'Через 2&nbsp;дня празднует' => array(),
//                           'Через 3&nbsp;дня празднует' => array(),
                           'Окончил вуз' => array('education', 'ВУЗ', 'ВУЗ'),
                           'Окончила вуз' => array('education', 'ВУЗ', 'ВУЗ'),
                           'Учится в вузе' => array('education', 'ВУЗ', 'ВУЗ'),
                           'Окончил школу' => array('school', 'Школа', 'Школа'),
                           'Окончила школу' => array('school', 'Школа', 'Школа'),
                           'Учится в школе' => array('school', 'Школа', 'Школа'),
                           'Окончил колледж' => array('collage', 'Колледж', 'Колледж'),
                           'Окончила колледж' => array('collage', 'Колледж', 'Колледж'),
                           'Учится в колледже' => array('collage', 'Колледж', 'Колледж'),
                           'Служил в воинской части' => array('army', 'Военная служба', 'Военная служба'),
                           'Служила в воинской части' => array('army', 'Военная служба', 'Военная служба'),
                           'Служит в воинской части' => array('army', 'Военная служба', 'Военная служба'),
                           'Работает в' => array('job', 'Место работы', 'Место работы'),
                           'Работал в' => array('previous_job', 'Прошлое место работы', 'Прошлое место работы'),
                           'Работала в' => array('previous_job', 'Прошлое место работы', 'Прошлое место работы'),
                           'Подписчики' => array('subscribers', 'Подписчики', 'Подписчики'),
                           'Друзья' => array('friends', 'Друзья', 'Друзья'),
                           'Фото' => array('photos', 'Фото', 'Фото'),
                           'Группы' => array('groups', 'Группы', 'Группы'),
                           'Игры' => array('games', 'Игры', 'Игры'),
                           'Заметки' => array('notes', 'Заметки', 'Заметки'),
                           'Видео' => array('videos', 'Видео', 'Видео'),
                           'Товары' => array('products', 'Товары', 'Товары'),
                           'мама' => array('mother', 'Мать', 'Мать'),
                           'папа' => array('father', 'Отец', 'Отец'),
                           'дочь' => array('daughter', 'Дочь', 'Дочь'),
                           'сын' => array('son', 'Сын', 'Сын'),
                           'бабушка' => array('grandmother', 'Бабушка', 'Бабушка'),
                           'дедушка' => array('grandfather', 'Дедушка', 'Дедушка'),
                           'внучка' => array('granddaughter', 'Внучка', 'Внучка'),
                           'внук' => array('grandson', 'Внук', 'Внук'),
                           'крёстная' => array('godmother', 'Крёстная', 'Крёстная'),
                           'крёстный' => array('godfather', 'Крёстный', 'Крёстный'),
                           'крестница' => array('goddaughter', 'Крестница', 'Крестница'),
                           'крестник' => array('godson', 'Крестник', 'Крестник'),
                           'сестра' => array('sister', 'Сестра', 'Сестра'),
                           'брат' => array('brother', 'Брат', 'Брат'),
                           'тётя' => array('aunt', 'Тётя', 'Тётя'),
                           'дядя' => array('uncle', 'Дядя', 'Дядя'),
                           'племянница' => array('aunt', 'Племянница', 'Племянница'),
                           'племянник' => array('uncle', 'Племянник', 'Племянник'),
                           'тёща' => array('motherinlaw', 'Тёща', 'Тёща'),
                           'тесть' => array('fatherinlaw', 'Тесть', 'Тесть'),
                           'свекровь' => array('motherinlaw_s', 'Свекровь', 'Свекровь'),
                           'свёкор' => array('fatherinlaw_s', 'Свёкор', 'Свёкор'),
                           'невестка' => array('daughterinlaw', 'Невестка', 'Невестка'),
                           'зять' => array('soninlaw', 'Зять', 'Зять'),
                           'родственник' => array('relative', 'Родственник', 'Родственник'),
                           'родственница' => array('relative', 'Родственник', 'Родственник'),
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
        return 'OK';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск в ОК',
            'ok_person' => 'ОК - поиск профилей по имени и дате рождения',
            'ok_phone' => 'ОК - поиск по номеру телефона',
            'ok_email' => 'ОК - поиск по email',
            'ok_url' => 'ОК - профиль пользователя',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск в OK';
    }

    public function getSessionData($sourceid = 6)
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=$sourceid AND lasttime<DATE_SUB(now(), INTERVAL 1 SECOND) ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=$sourceid AND request_id=$reqId ORDER BY lasttime limit 1");

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

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used>=10 AND id=".$sessionData->id);

//                echo "Using session {$row->id} for source $sourceid\n";

                if (!$row->proxyid) {
//                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND country='ru' AND rotation>0 ORDER BY lasttime limit 1");
                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND country='ru' ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetch_object();
                        if ($row) {
                            $sessionData->proxyid = $row->proxyid;
                            $sessionData->proxy = $row->proxy;
                            $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

//                            $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
                            $mysqli->query("UPDATE isphere.session SET proxyid=".$row->proxyid." WHERE id=".$sessionData->id);
                        }
                    }
                }

//                if ($sessionData->proxyid)
//                    $mysqli->query("UPDATE isphere.proxy SET lasttime=now(),used=used+1 WHERE id=".$sessionData->proxyid);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],3);

        if($checktype=='email' && !isset($initData['email'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (email)');

            return false;
        }

        if($checktype=='phone' && !isset($initData['phone'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (телефон)');

            return false;
        }

        if($checktype=='nick' && !isset($initData['nick'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (псевдоним)');

            return false;
        }

        if($checktype=='url' && !isset($initData['url'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (ссылка)');

            return false;
        }

        if($checktype=='person' && (!isset($initData['last_name']) || !isset($initData['first_name']))) {
            $rContext->setFinished();
//            $rContext->setError('Не указаны параметры для поиска (фамилия+имя)');

            return false;
        }

        if($checktype=='text' && !isset($initData['text'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска');

            return false;
        }

        if ($checktype=='nick') {
            $initData['url'] = 'https://ok.ru/'.$initData['nick'];
        }

        if ($checktype=='url') {
            if (strpos($initData['url'],'ok.ru/')===false) {
                $rContext->setFinished();
                return false;
            }
        }

        if (($checktype=='nick' || $checktype=='url') && !isset($swapData['found'])) {
            $swapData['found'] = true;
            $swapData['data'] = array();
        }

        $rContext->setSwapData($swapData);

        $ch = $rContext->getCurlHandler();
/*
        if ($checktype=='person') {
            $rContext->setFinished();
            $rContext->setError('Сервис временно недоступен');
            return false;
        }
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        if(!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData($checktype=='person' || (($checktype=='nick' || $checktype=='url') && isset($swapData['found']))?6:14);

            if(!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration']>=30)) {
                    if (($checktype=='nick' || $checktype=='url') && !isset($swapData['data'])) {
                        $swapData['found'] = true;
                        $swapData['data'] = array();
                        $rContext->setSwapData($swapData);
                        return false;
                    } else {
                        $rContext->setFinished();
                        $rContext->setError('Сервис временно недоступен');
                        return false;
                    }
                } else {
                    (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                    return false;
                }
            } elseif ($swapData['session']->token) {
                $swapData['hash'] = $swapData['session']->token;
            }
            $swapData['iteration'] = 1;
            $rContext->setSwapData($swapData);
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $url = 'https://ok.ru/dk';
        $params = array();
        $params['st.cmd'] = 'anonymPasswordRecoveryNew';
        $header = array();
        if (!isset($swapData['found']) && !isset($swapData['captcha']) && !isset($swapData['locked'])) {
            if ($checktype=='nick' || $checktype=='url') {
                $params['st.registrationAction'] = 'ValidateLink';
                $params['st.recoveryMethod'] = 'Link';
            } elseif ($checktype=='phone' || $checktype=='email') {
                $params['st.cmd'] = 'anonymMain';
                $params['st.accRecovery'] = 'on';
                $params['st.error'] = 'errors.password.wrong';
                $params['st.email'] = ($checktype=='phone')?$initData['phone']:$initData['email'];
                $swapData['session']->cookies = '';
            } elseif ($checktype=='phone') {
                $params['st.registrationAction'] = 'ValidatePhoneNumber';
                $params['st.recoveryMethod'] = 'Phone';
            } elseif ($checktype=='email') {
                $params['st.registrationAction'] = 'ValidateEmail';
                $params['st.recoveryMethod'] = 'Email';
            } elseif ($checktype=='text') {
                $url = 'https://ok.ru/search';
                $params = array();
                $params['st.mode']='Users';
                $params['st.grmode']='Groups';
                $params['st.posted']='set';
                $params['st.query']=$this->str_translit($initData['text']);
            } elseif ($checktype=='person') {
                $url = 'https://ok.ru/search';
                $params = array();
                $params['st.mode']='Users';
                $params['st.grmode']='Groups';
                $params['st.posted']='set';
                $params['st.query']=$this->str_translit(preg_replace("/^(.*?)\s.[\*]+$/ui","$1",trim($initData['last_name'].' '.$initData['first_name'])));
                if (isset($initData['date']) && strtotime($initData['date'])) {
                    $initData['date'] = date('d.m.Y',strtotime($initData['date']));
                    $birth = explode('.',$initData['date']);
                    $params['st.bthDay']=$birth[0];
                    $params['st.bthMonth']=$birth[1]-1;
                    $params['st.bthYear']=$birth[2];
                } elseif (isset($initData['date']) && preg_match("/^[0-3]*\d\.[0-1]*\d$/",$initData['date'])) {
                    $birth = explode('.',$initData['date']);
                    $params['st.bthDay']=$birth[0];
                    $params['st.bthMonth']=$birth[1]-1;
                } elseif (isset($initData['date']) && preg_match("/^[1-2][\d]{3}$/",$initData['date'])) {
                    $params['st.bthYear']=$initData['date'];
                }
                if (isset($initData['age'])) {
                    $params['st.fromAge']=$initData['age'];
                    $params['st.tillAge']=$initData['age'];
                }
                if (isset($initData['location'])) {
                    $params['st.location']=$initData['location'];
                    $params['st.city']=$initData['location'];
                }
                curl_setopt($ch, CURLOPT_POST, false);
            }
        }
        if(/*($checktype=='nick' || $checktype=='url') && !isset($swapData['found']) && !isset($swapData['locked']) && !isset($swapData['captcha']) && */!isset($swapData['hash'])) {
//        if(/*$checktype!='person' && ($checktype!='url' || !isset($swapData['found'])) && */!isset($swapData['hash'])) {
            $url .= '?'.http_build_query($params);
        } elseif(($checktype=='nick' || $checktype=='url') && !isset($swapData['found']) && !isset($swapData['locked']) && !isset($swapData['captcha'])) {
//            $params['st.countryId'] = '10414533690';
//            $params['st.countryCode'] = '';
            if ($checktype=='nick' || $checktype=='url') {
                $params['st.recoveryData'] = $initData['url'];
            } elseif ($checktype=='phone') {
                $params['st.phone'] = $initData['phone'];
            } elseif ($checktype=='email') {
                $params['st.recoveryData'] = $initData['email'];
            }
//            if ($swapData['needcaptcha'])
//                $params['st.ccode'] = $swapData['session']->code;
//            $params['st.ccode'] = '';
            $params['st.ccode'] = $swapData['session']->code;
            $params['cmd'] = 'AnonymPasswordRecoveryNew';
            $params['gwt.requested'] = $swapData['hash'];
            $header[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
            $header[] = 'X-Requested-With: XMLHttpRequest';
            $header[] = 'TKN: undefined';
            $header[] = 'DNT: 1';
            $header[] = 'Referer: https://ok.ru/';
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
//            print "POST: ".http_build_query($params)."\n";
        } elseif(isset($swapData['locked'])) {
            $params['st.cmd'] = 'anonymAccountRecoveryFlow';
            $params['st.recStep'] = 'PrePhoneCaptcha';
            $params['st.accountAction'] = 'Init';
            $url .= '?'.http_build_query($params);
            curl_setopt($ch, CURLOPT_POST, false);
        } elseif(isset($swapData['captcha'])) {
            $params['st.cmd'] = 'captcha';
            $url  = 'https://ok.ru/captcha';
            $url .= '?'.http_build_query($params);
        } elseif (($checktype=='nick' || $checktype=='url') && isset($swapData['data'])) {
            $url = $initData['url'];
            if (isset($swapData['about'])) $url .= '/about';
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, false);
        } elseif(!isset($swapData['found'])) {
            $url .= '?'.http_build_query($params);
        } elseif(isset($swapData['found']) && ($checktype=='phone' || $checktype=='email')) {
            $params['st.cmd'] = 'anonymRecoveryAfterFailedLogin';
            $params['st._aid'] = 'LeftColumn_Login_ForgotPassword';
            $url .= '?'.http_build_query($params);
        } elseif (isset($swapData['found'])) {
            $params['st.registrationAction'] = 'ChooseCodeDestination';
//            $params['st.countryId'] = '10414533690';
/*
            if (isset($initData['url'])) {
                $params['st._aid'] = 'AnonymPasswordRecoveryNew_RecoveryMethod_EnterLink';
            } elseif (isset($initData['phone'])) {
                $params['st._aid'] = 'AnonymPasswordRecoveryNew_RecoveryMethod_EnterPhone';
            } elseif (isset($initData['email'])) {
                $params['st._aid'] = 'AnonymPasswordRecoveryNew_RecoveryMethod_EnterEmail';
            }
*/
            $url .= '?'.http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
//        print "URL: $url\n";
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//        curl_setopt($ch, CURLOPT_HEADER, true);
//        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_REFERER, 'https://ok.ru/');
/*
        if ((isset($initData['url']) || isset($initData['phone']) || isset($initData['email'])) && isset($swapData['session'])) {
*/
            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
            curl_setopt($ch, CURLOPT_COOKIEFILE, '');
//            print "Cookie: ".$swapData['session']->cookies."\n";
            if ($swapData['session']->proxy) {
                curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
//                print "Proxy: ".$swapData['session']->proxy."\n";
                if ($swapData['session']->proxy_auth) {
                    curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                    curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
                }
            }
/*
        } else {
            $cookies = 'AUTHCODE=' . $this->ok_authcode . '; JSESSIONID=' . $this->ok_jsessionid;
            curl_setopt($ch, CURLOPT_COOKIE, $cookies);
//            print "Cookie: ".$cookies."\n";
        }
*/
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],3);

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $error = false;
        $content = curl_multi_getcontent($rContext->getCurlHandler());

        file_put_contents('./logs/ok/'.$initData['checktype'].'_'.time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
        if (!$content/* && ($swapData['iteration']>3)*/) {
            $curlError = curl_error($rContext->getCurlHandler());
            if($curlError){
//                $rContext->setError($curlError);
                if (strpos($curlError,'timed out') || strpos($curlError,'refused') || strpos($curlError,'reset by peer')) {
                    if (isset($swapData['session']) && $swapData['session']->id) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='connectionerror' WHERE id=" . $swapData['session']->id);
//                        $mysqli->query("UPDATE proxy SET status=0 WHERE id=" . $swapData['session']->proxyid);
                        unset($swapData['session']);
                        $rContext->setSwapData($swapData);
                    }
                }
            }
            file_put_contents('./logs/ok/'.$initData['checktype'].'_err_'.time().'.html',$curlError."\n".curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            if (isset($swapData['session']) && $swapData['session']->id) {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 15 minute),sessionstatusid=6,statuscode='empty' WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
            }
            return false;
        }

        if ($content) {
            if(($checktype=='person' || $checktype=='text' || $checktype=='nick' || $checktype=='url') && preg_match("/<a href=\"\/dk\?st\.cmd=anonymMain\"/",$content)) {
                if (isset($swapData['session'])) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='notlogged' WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                }
            }elseif($checktype=='person' || $checktype=='text') {
                $resultData = new ResultDataList();
                $res = false;
                file_put_contents('./logs/ok/ok_search_'.time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                if (preg_match("/ results=\"({[^\"]+)\"/",$content,$matches)) {
                    file_put_contents('./logs/ok/ok_search_'.time().'.txt',html_entity_decode($matches[1]));
                    $res = json_decode(html_entity_decode($matches[1]),true);
                    if($res && isset($res['users']['values']))
                        $res = $res['users']['values'];
                }
                $found = -1;
                if($res && isset($res['totalCount'])) {
                    $found = $res['totalCount'];
                }
                if($found==0) {
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    return true;
                }elseif (intval($found)>20 
//                         || (intval($found)>10 && !preg_match("/^(.*?)\s.[\*]+$/ui",trim($initData['last_name'].' '.$initData['first_name'])))
                    ) {
//                    $error = "Найдено слишком много совпадений ($found)";
                    $error = "Найдено слишком много совпадений";
                    if (!isset($initData['date'])) $error.=". Попробуйте указать в запросе дату рождения";
//                    elseif (!isset($initData['location'])) $error.=". Попробуйте указать в запросе местонахождение";
                    if ($rContext->getLevel()==0)
                        $rContext->setError($error);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    return false;
                }elseif($found>0) {
                    $found = 0;
                    foreach($res['results'] as $result) {
                        $data = array();
                        if (isset($result['user']['info'])) {
                            $user = $result['user']['info'];
                            $name = html_entity_decode(strip_tags(iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',$user['name']))));
                            if (!preg_match("/^(.*?)\s[^\*]*[\*]+$/ui",trim($initData['last_name'].' '.$initData['first_name'])) || preg_match("/^".strtr(trim($initData['last_name'].' '.$initData['first_name']),array('*'=>'.','?'=>'.','.'=>'[^\s]+'))."$/ui",$name)) {
                                $data['name'] = new ResultDataField('string','Name',$name,'Имя пользователя','Имя пользователя');
                                $found++;

                                if (isset($user['shortLink'])) {
                                    $data['link'] = new ResultDataField('url'.($found<10?':recursive':''),'Link','https://ok.ru'.$user['shortLink'],'Ссылка','Ссылка на страницу в соцсети');
                                }
                                if (isset($user['imgUrl'])) {
                                   if (preg_match("/^\/\//",$user['imgUrl'])) $user['imgUrl']=(substr($user['imgUrl'],0,6)==='https:'?'':'https:').$user['imgUrl'];
                                   $data['piclink'] = new ResultDataField('image','Photo',$user['imgUrl'],'Фото','Фотография пользователя');
                                }
                                if (isset($user['city'])) {
                                    $data['location'] = new ResultDataField('string','Location',html_entity_decode(strip_tags(iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',$user['city'])))),'Местоположение','Местоположение');
                                }
                            }
                        }
                        if (sizeof($data)) $resultData->addResult($data);
                    }
                    if ($found<10)
                        $rContext->setResultData($resultData);
                    if (!$error) {
                        $rContext->setFinished();
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                        return true;
                    }
                } elseif(!preg_match("/PopLayerLogoffUserModal/",$content)) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='nologoff' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    }
                } else {
                    file_put_contents('./logs/ok/ok_search_err_'.time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                    $error = 'Не удалось выполнить поиск';
                    if (isset($swapData['session'])) {
//                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='searcherror' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    }
                }
            } elseif(!isset($swapData['hash'])) {
//                file_put_contents('./logs/ok/ok_start_'.time().'.html',$content);
                if (preg_match("/<img src=\"\/captcha/",$content)) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    }
//                    $rContext->setSleep(1);
//                    $error = 'Не удалось выполнить поиск';
//                    $swapData['needcaptcha'] = 1;
//                    $rContext->setSwapData($swapData);
//                    return true;
                }elseif(preg_match("/,gwtHash:\"([^\"]+)\",/",$content,$matches)){
                    $swapData['hash'] = $matches[1];
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET token='" . $swapData['hash'] . "' WHERE id=" . $swapData['session']->id);
                    }
                } else {
                    $error = 'Не удалось подключиться к сервису';
                }
            } elseif(!isset($swapData['found']) && ($checktype=='phone' || $checktype=='email')) {
                file_put_contents('./logs/ok/ok_recovery_'.time().'.html',$content);
                if (strpos($content,'Восстановить профиль')) {
                    $cookies = str_cookies($swapData['session']->cookies);
                    foreach (curl_getinfo($rContext->getCurlHandler(),CURLINFO_COOKIELIST) as $cookie) {
                        $arr = explode("	",$cookie);
//                        if ($arr[0]=='.ok.ru') {
                            $cookies[$arr[5]] = $arr[6];
//                        }
                    }
                    $new_cookies = cookies_str($cookies);
                    $swapData['session']->cookies = $new_cookies;

                    $swapData['found'] = true;
                }
            } elseif(!isset($swapData['found']) && !isset($swapData['locked']) && !isset($swapData['captcha'])) {
                file_put_contents('./logs/ok/ok_header_'.time().'.html',$content);
                if(strpos($content,'ChooseCodeDestination')){
                    $swapData['found'] = true;
                } elseif(strpos($content,'NotSubject')) {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    return true;
                } elseif(strpos($content,'RecoveryImpossible')) {
                    if ($checktype=='nick' || $checktype=='url') {
                        $swapData['found'] = true;
                        $swapData['data'] = array();
                    } else {
                        $data['result'] = new ResultDataField('string','Result','Найден','Результат','Результат');
                        $data['result_code'] = new ResultDataField('string','ResultCode','FOUND','Код результата','Код результата');

                        $resultData = new ResultDataList();
                        $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                        return true;
                    }
                } elseif(strpos($content,'anonymAccountRecoveryFlow') || strpos($content,'anonymBlockedByAdmin')) {
                    $swapData['locked'] = 1;
                } elseif(strpos($content,'Неправильно') || strpos($content,'Введите код')) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 6 hour),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
//                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    }
//                    $error = 'Не удалось выполнить поиск';
                } elseif(strpos($content,'anonymPasswordRecoveryNew')) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='recoveryerror' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    }
//                    $error = 'Не удалось выполнить поиск';
                } elseif(preg_match("/<span class=\"input-e[^>]+>([^<]+)<\/span>/",$content,$matches)) {
                    $error = $matches[1];
                } else {
                    file_put_contents('./logs/ok/ok_err_'.time().'.html',$content);
                    if ($swapData['iteration']>10)
                        $error = 'Некорректный ответ сервиса';
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='invalidanswer' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    }
                }
            } elseif(isset($swapData['locked'])) {
//                file_put_contents('./logs/ok/ok_locked_'.time().'.html',$content);
//                $swapData['captcha']++;
                $resultData = new ResultDataList();
                if (isset($initData['phone'])) {
                    $data['phone'] = new ResultDataField('string','Phone',$initData['phone'],'Телефон','Телефон');
                } elseif(preg_match("/телефону ([^\,]+)/",$content,$matches)){
                    $data['phone'] = new ResultDataField('string','Phone',strip_tags($matches[1]),'Телефон','Телефон');
                }
                if (isset($initData['email'])) {
                    $data['email'] = new ResultDataField('string','Email',$initData['email'],'E-mail','E-mail');
                }
                $data['result'] = new ResultDataField('string','Result','Найден','Результат','Результат');
                $data['result_code'] = new ResultDataField('string','ResultCode','FOUND','Код результата','Код результата');
                $data['blocked'] = new ResultDataField('string','blocked','true','Заблокирован','Заблокирован');
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                return true;
            } elseif(isset($swapData['captcha'])) {
//                file_put_contents('./logs/ok/okcaptcha_'.time().'.jpg',$content);
                $error = 'Ошибка распознавания капчи';
            } elseif (($checktype=='nick' || $checktype=='url') && isset($swapData['data']) && !isset($swapData['about'])) {
                file_put_contents('./logs/ok/ok_profile_'.time().'.html',$content);
                $data = $swapData['data'];

                if(preg_match("/\"AuthLoginPopup\"/", $content) && !preg_match("/st\._aid=TD_Logout/", $content)) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='loggedout' WHERE sourceid=6 AND id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    }
                } else {
                    if(preg_match("/tsid=\"page-not-found\"/", $content, $matches)){
                        $resultData = new ResultDataList();
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                        return true;
                    }

                    if ($checktype=='nick') {
                        $initData['url'] = 'https://ok.ru/'.$initData['nick'];
                    }
                    $data['link'] = new ResultDataField('url:recursive','Link',$initData['url'],'Ссылка','Ссылка на профиль');
                    if(preg_match("/<h1[^>]*>([^<]+)/", $content, $matches)){
                        $matches[1] = iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',$matches[1]));
                        $data['name'] = new ResultDataField('string','Name', $matches[1], 'Имя', 'Имя');
                    }
                    if(preg_match("/__l\" data-id=\"([^\"]+)\">/", $content, $matches)){
                        $data['uid'] = new ResultDataField('string','uid',$matches[1],'ID пользователя','ID пользователя');
                    }
                    if(preg_match("/<span[^>]*>Последний визит: ([^<]+)/", $content, $matches)){
                        $data['last_visited'] = new ResultDataField('string','last_visited',strip_tags($matches[1]),'Время последнего посещения','Время последнего посещения');
                    }
/*
                    if(preg_match_all("/<a href=\"[^\"]+\" class=\"mctc_navMenuSec\">([^<]+)<span class=\"navMenuCount\">([^<]+)<\/span>/", $content, $matches)){
                        foreach( $matches[1] as $key => $val ){
                            $title = trim(str_replace('&nbsp;', '', $val));
                            $text = html_entity_decode(strip_tags($matches[2][$key]));

                            if(isset($this->names[$title])){
                                $field = $this->names[$title];
                                $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $text, $field[1], $field[2]);
                            } else {
                                $counter++;
                                $data['other'.$counter['other']] = new ResultDataField('string', 'other'.$counter['other'], $text, $title, $title);
                                file_put_contents('./logs/fields/ok'.time().'_'.$title , $title."\n".$text);
                            }
                        }
                    }
*/
                    if(preg_match("/<div class=\"lcTc_avatar __l\"><img [^\/]+([^\s]+)/", $content, $matches)){
                        $data['photo'] = new ResultDataField('image','photo',(substr(html_entity_decode($matches[1]),0,6)==='https:'?'':'https:').html_entity_decode($matches[1]).'_2x','Фото профиля','Фото профиля');
                    } elseif(preg_match("/<div class=\"lcTc_avatar __l\"><a href=[^>]+><img [^\/]+(^\s]+)/", $content, $matches)){
                        $data['photo'] = new ResultDataField('image','photo',(substr(html_entity_decode($matches[1]),0,6)==='https:'?'':'https:').html_entity_decode($matches[1]).'_2x','Фото профиля','Фото профиля');
                    } elseif(preg_match("/<img srcset=\"[^\s]+ 1x, ([^\s]+) 2x\"/", $content, $matches)) {
                        $data['photo'] = new ResultDataField('image','photo',(substr(html_entity_decode($matches[1]),0,6)==='https:'?'':'https:').html_entity_decode($matches[1]),'Фото профиля','Фото профиля');
                    } elseif(preg_match("/<img width=\"288\" height=\"288\" src=\"([^\"]+)/", $content, $matches)) {
                        $data['photo'] = new ResultDataField('image','photo',(substr(html_entity_decode($matches[1]),0,6)==='https:'?'':'https:').html_entity_decode($matches[1]),'Фото профиля','Фото профиля');
                    }

                    $swapData['data'] = $data;
                    $swapData['about'] = 1;
                }
            } elseif (($checktype=='nick' || $checktype=='url') && isset($swapData['data'])) {
                file_put_contents('./logs/ok/ok_about_'.time().'.html',$content);
                $data = $swapData['data'];
                $counter = array('other'=>0,'relative'=>0);

                if(preg_match_all("/<a class=\"mctc_navMenuSec[^>]+>([^<]+)<span class=\"navMenuCount\">([^<]+)<\/span><\/a>/", $content, $matches)){
                    foreach( $matches[1] as $key => $val ){
                        $title = trim(str_replace('&nbsp;', '', $val));
                        $text = trim(str_replace('&nbsp;', '', $matches[2][$key]));
                        if(isset($this->names[$title])){
                            $field = $this->names[$title];
                            $counter[$field[0]] = isset($counter[$field[0]]) ? $counter[$field[0]]+1 : 0;
                            $data[$field[0].($counter[$field[0]]?$counter[$field[0]]:'')] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $text, $field[1], $field[2]);
                        } elseif (strpos($title,'праздн')===false) {
                            $counter++;
                            $data['other'.$counter['other']] = new ResultDataField('string', 'other'.$counter['other'], $text, $title, $title);
                            file_put_contents('./logs/fields/ok'.time().'_'.$title , $title."\n".$text);
                        }
                    }
                }

                if(//preg_match_all("/<span class=\"user-profile_i_t_inner\">([^<]+)<\/span><\/div><\/div><div class=\"user-profile_i_val[^>]+>(.*?)<\/div>.*?<span class=\"darkgray\">([^<]+)<\/span>/", $content, $matches) ||
                   preg_match_all("/<span class=\"user-profile_i_t_inner\">([^<]+)<\/span><\/div><\/div><div class=\"user-profile_i_val[^>]+>(.*?)<\/div>/", $content, $matches)){
                    foreach( $matches[1] as $key => $val ){
                        $title = trim(str_replace(':', '', $val));
                        $text = trim(str_replace("&#039;", "'", html_entity_decode(strip_tags($matches[2][$key].(isset($matches[3][$key])?', '.$matches[3][$key]:'')))));
                        $text = iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',$text));

                        if(isset($this->names[$title])){
                            $field = $this->names[$title];
                            $counter[$field[0]] = isset($counter[$field[0]]) ? $counter[$field[0]]+1 : 0;
                            if ($field[0]=='birthday') {
                                $repl = array(' '=>'.',
                                    'января'=>'01','февраля'=>'02','марта'=>'03','апреля'=>'04','мая'=>'05','июня'=>'06',
                                    'июля'=>'07','августа'=>'08','сентября'=>'09','октября'=>'10','ноября'=>'11','декабря'=>'12');
                                if(strpos($text,'(')) $text = trim(substr($text,0,strpos($text,'(')));
                                if(strpos($text,' ')==1) $text = '0' . $text;
                                $text = strtr($text,$repl);
                            }
                            $data[$field[0].($counter[$field[0]]?$counter[$field[0]]:'')] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $text, $field[1], $field[2]);
                            if (preg_match('/<a class=\"o user-profile_i_relation-t\" href=\"([^\?]+)[^\"]+\">/',$matches[2][$key],$link)) {
                                $url = "https://ok.ru".$link[1];
                                $data[$field[0].($counter[$field[0]]?$counter[$field[0]]:'').'_link'] = new ResultDataField('url', $field[0].'_link', $url, $field[1], $field[2]);
                            } 
                        } elseif (strpos($title,'праздн')===false) {
                            $counter++;
                            $data['other'.$counter['other']] = new ResultDataField('string', 'other'.$counter['other'], $text, $title, $title);
                            file_put_contents('./logs/fields/ok'.time().'_'.$title , $title."\n".$text);
                        }
                    }
                }

                if(preg_match_all("/<a class=\"o user-profile_i_relation-t ellip\" href=\"([^\?]+)[^>]+><span>([^<]+)<\/span><div [^>]+><\/div><\/a><div class=\"ellip lstp-t\">([^<]+)<\/div>/", $content, $matches)){
                    foreach( $matches[1] as $key => $val ){
                        $text = trim(str_replace("&#039;", "'", html_entity_decode(strip_tags($matches[2][$key]))));
                        $text = iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',$text));
                        $title = trim(str_replace("&#039;", "'", html_entity_decode(strip_tags($matches[3][$key]))));
                        $url = "https://ok.ru".$val;

                        if(isset($this->names[$title])){
                            $field = $this->names[$title];
                            $counter[$field[0]] = isset($counter[$field[0]]) ? $counter[$field[0]]+1 : 0;
                            $data[$field[0].($counter[$field[0]]?$counter[$field[0]]:'')] = new ResultDataField('string', $field[0], $text, $field[1], $field[2]);
                            $data[$field[0].$counter[$field[0]].'_link'] = new ResultDataField('url', $field[0].'_link', $url, $field[1], $field[2]);
                        } else {
                            $counter['']++;
                            $data['relative'.$counter['relative']] = new ResultDataField('string', 'relative'.$counter['relative'], $text, $title, $title);
                            $data['relative'.$counter[''].'_link'] = new ResultDataField('url', 'relative_link', $url, $title, $title);
                            file_put_contents('./logs/fields/ok'.time().'_rel_'.$title , $title."\n".$text);
                        }
                    }
                }

                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                return true;
            } elseif(($checktype=='phone' || $checktype=='email')) {
                file_put_contents('./logs/ok/ok_login_'.time().'.html',$content);
                $resultData = new ResultDataList();
                if(preg_match("/восстановления/",$content) || preg_match("/можно восстановить/",$content) || preg_match("/можно было бы восстановить/",$content)) {
                    $data = array();
                    if (isset($initData['phone'])) {
                        $data['phone'] = new ResultDataField('string','Phone',$initData['phone'],'Телефон','Телефон');
                    } elseif(preg_match("/<div class=\"ext-registration_stub_small_header\">([^<]+)<\/div><div class=\"ext-registration_stub_small_text\">Телефон/",$content,$matches)){
                        $data['phone'] = new ResultDataField('string','Phone',strip_tags($matches[1]),'Телефон','Телефон');
                    }
                    if (isset($initData['email'])) {
                        $data['email'] = new ResultDataField('string','Email',$initData['email'],'E-mail','E-mail');
                    } elseif(preg_match("/<div class=\"ext-registration_stub_small_header\">([^<]+)<\/div><div class=\"ext-registration_stub_small_text\">Почта/",$content,$matches)){
                        $data['email'] = new ResultDataField('string','Email',strtr(strip_tags($matches[1]),array('X'=>'*')),'E-mail','E-mail');
                    }

                    if(preg_match("/<div class=\"ext-registration_username_header\">([^<]+)<\/div>/",$content,$matches)){
//                        $name = iconv('windows-1251','utf-8',html_entity_decode(iconv('utf-8','windows-1251//IGNORE',strip_tags($matches[1]))));
                        $name = trim(preg_replace("/[^А-Яа-яЁёA-Za-z\s\-\.\*]/ui"," ",html_entity_decode(strip_tags($matches[1]))));
                        $data['name'] = new ResultDataField('string','Name',$name,'Имя','Имя');

                        if (isset($initData['first_name'])) {
                            $split_name = explode(' ',$this->str_uprus($name));
                            $first_name='';
                            for($i=0; $i<sizeof($split_name)-1; $i++)
                                $first_name = trim($first_name.' '.$split_name[$i]);
                            $last_name = strtr($split_name[sizeof($split_name)-1],array('*'=>''));

                            if ($this->str_uprus($initData['first_name'])==$first_name) {
                               if (isset($initData['last_name']) && ($this->str_uprus(mb_substr($initData['last_name'],0,1))==mb_substr($last_name,0,1))) {
                                   $match_code = 'MATCHED';
                                } else {
                                    $match_code = 'MATCHED_NAME_ONLY';
                                }
                            } else {
                                $match_code = 'NOT_MATCHED';
                            }
                            $data['match_code'] = new ResultDataField('string','match_code', $match_code, 'Результат сравнения имени', 'Результат сравнения имени');
                        }
                    }

                    if(preg_match_all("/<div class=\"lstp-t\">([^<]+)<\/div>/",$content,$matches)){
                        foreach($matches[1] as $key => $text) {
                            if(preg_match("/Профиль создан/",$text)) {
                                $text = mb_substr($text,15);
                                $repl = array(' '=>'.',
                                    'января'=>'01','февраля'=>'02','марта'=>'03','апреля'=>'04','мая'=>'05','июня'=>'06',
                                    'июля'=>'07','августа'=>'08','сентября'=>'09','октября'=>'10','ноября'=>'11','декабря'=>'12');
                                $text = strtr($text,$repl);
                                if(strlen($text)==9) $text = '0'.$text;
                                $data['created'] = new ResultDataField('string','Created', $text,'Зарегистрирован','Зарегистрирован');
                            } else {
                                $text = explode(',',$text);
                                $data['age'] = new ResultDataField('string','Age', trim($text[0]), 'Возраст', 'Возраст');
                                if (isset($text[1]) && trim($text[1]))
                                    $data['location'] = new ResultDataField('string','Location', strtr(trim(html_entity_decode(strip_tags($text[1]))),array('–'=>'-')), 'Местоположение', 'Местоположение');
                            }
                        }
                    }

                    $data['result'] = new ResultDataField('string','Result','Найден','Результат','Результат');
                    $data['result_code'] = new ResultDataField('string','ResultCode','FOUND','Код результата','Код результата');
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    return true;
                } elseif (preg_match("/вы помните/",$content)) {
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    return true;
                } elseif (!$content) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='empty' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    }
                } else {
                    file_put_contents('./logs/ok/ok_login_err_'.time().'.html',$content);
                    $error = "Некорректный ответ сервиса";
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='loginerror' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    }
                }
            } else {
                file_put_contents('./logs/ok/ok_result_'.time().'.html',$content);
                if(!preg_match("/anonymPasswordRecoveryNew/",$content)){
                    if (($checktype=='nick' || $checktype=='url') && $swapData['iteration']>10) {
                        $swapData['data'] = array();
                        unset($swapData['session']);
                    } else {
                        if (isset($swapData['session'])) {
                            $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='recoveryerror' WHERE id=" . $swapData['session']->id);
                            unset($swapData['session']);
                        }
                        unset($swapData['found']);
//                        $rContext->setSleep(1);
//                        $error = 'Не удалось получить ответ';
//                        $rContext->setSwapData($swapData);
//                        return true;
                    }
                } else {
                    $data = array();
                    if (isset($initData['phone'])) {
                        $data['phone'] = new ResultDataField('string','Phone',$initData['phone'],'Телефон','Телефон');
                    } elseif(preg_match("/номер ([^<]+)<\/div>/",$content,$matches)){
                        $data['phone'] = new ResultDataField('string','Phone',strip_tags($matches[1]),'Телефон','Телефон');
                    }
                    if (isset($initData['email'])) {
                        $data['email'] = new ResultDataField('string','Email',$initData['email'],'E-mail','E-mail');
                    } elseif(preg_match("/почту ([^<]+)<\/div>/",$content,$matches)){
                        $data['email'] = new ResultDataField('string','Email',strtr(strip_tags($matches[1]),array('X'=>'*')),'E-mail','E-mail');
                    }
                    if ($checktype=='nick' || $checktype=='url') {
                        $swapData['data'] = $data;
                        unset($swapData['session']);
                    } else {
                        if(preg_match("/<div class=\"recovery_profile\">([^<]+)<\/div>/",$content,$matches)){
//                            $name = iconv('windows-1251','utf-8',html_entity_decode(iconv('utf-8','windows-1251//IGNORE',strip_tags($matches[1]))));
                            $name = trim(preg_replace("/[^А-Яа-яЁёA-Za-z\s\-\.\*]/ui"," ",html_entity_decode(strip_tags($matches[1]))));
                            $data['name'] = new ResultDataField('string','Name',$name,'Имя','Имя');

                            if (isset($initData['first_name'])) {
                                $split_name = explode(' ',$this->str_uprus($name));
                                $first_name='';
                                for($i=0; $i<sizeof($split_name)-1; $i++)
                                    $first_name = trim($first_name.' '.$split_name[$i]);
                                $last_name = strtr($split_name[sizeof($split_name)-1],array('*'=>''));

                                if ($this->str_uprus($initData['first_name'])==$first_name) {
                                    if (isset($initData['last_name']) && ($this->str_uprus(mb_substr($initData['last_name'],0,1))==mb_substr($last_name,0,1))) {
                                        $match_code = 'MATCHED';
                                    } else {
                                        $match_code = 'MATCHED_NAME_ONLY';
                                    }
                                } else {
                                    $match_code = 'NOT_MATCHED';
                                }
                                $data['match_code'] = new ResultDataField('string','match_code', $match_code, 'Результат сравнения имени', 'Результат сравнения имени');
                            }
                        }
                        $data['result'] = new ResultDataField('string','Result','Найден','Результат','Результат');
                        $data['result_code'] = new ResultDataField('string','ResultCode','FOUND','Код результата','Код результата');

                        $resultData = new ResultDataList();
                        $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                        return true;
                    }
                }
            }
            $rContext->setSwapData($swapData);
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>20)
            $error='Превышено количество попыток получения ответа';

        if ($error && isset($swapData['iteration']) && $swapData['iteration']>2) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        $rContext->setSleep(1);
        return true;
    }
}

?>