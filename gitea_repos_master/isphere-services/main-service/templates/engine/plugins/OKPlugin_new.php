<?php

class OKPlugin_new implements PluginInterface
{
    private $names = [
                           'Родился' => ['birthday', 'День рождения', 'День рождения'],
                           'Родилась' => ['birthday', 'День рождения', 'День рождения'],
                           'Живет в' => ['living', 'Место жительства', 'Место жительства'],
                           'Место рождения' => ['birthplace', 'Место рождения', 'Место рождения'],
                           'В браке' => ['spouse', 'Супруг', 'Супруг'],
                           'В отношениях с' => ['mate', 'Партнер', 'Партнер'],
                           'Семейное положение' => ['family', 'Семейное положение', 'Семейное положение'],
//                           'Вчера праздновал' => array('celebrated', 'Праздник', 'Праздник'),
//                           'Вчера праздновала' => array('celebrated', 'Праздник', 'Праздник'),
//                           'Через 2&nbsp;дня празднует' => array(),
//                           'Через 3&nbsp;дня празднует' => array(),
                           'Окончил вуз' => ['education', 'ВУЗ', 'ВУЗ'],
                           'Окончила вуз' => ['education', 'ВУЗ', 'ВУЗ'],
                           'Учится в вузе' => ['education', 'ВУЗ', 'ВУЗ'],
                           'Окончил школу' => ['school', 'Школа', 'Школа'],
                           'Окончила школу' => ['school', 'Школа', 'Школа'],
                           'Учится в школе' => ['school', 'Школа', 'Школа'],
                           'Окончил колледж' => ['collage', 'Колледж', 'Колледж'],
                           'Окончила колледж' => ['collage', 'Колледж', 'Колледж'],
                           'Учится в колледже' => ['collage', 'Колледж', 'Колледж'],
                           'Служил в воинской части' => ['army', 'Военная служба', 'Военная служба'],
                           'Служила в воинской части' => ['army', 'Военная служба', 'Военная служба'],
                           'Служит в воинской части' => ['army', 'Военная служба', 'Военная служба'],
                           'Работает в' => ['job', 'Место работы', 'Место работы'],
                           'Работал в' => ['previous_job', 'Прошлое место работы', 'Прошлое место работы'],
                           'Работала в' => ['previous_job', 'Прошлое место работы', 'Прошлое место работы'],
                           'Подписчики' => ['subscribers', 'Подписчики', 'Подписчики'],
                           'Друзья' => ['friends', 'Друзья', 'Друзья'],
                           'Фото' => ['photos', 'Фото', 'Фото'],
                           'Группы' => ['groups', 'Группы', 'Группы'],
                           'Игры' => ['games', 'Игры', 'Игры'],
                           'Заметки' => ['notes', 'Заметки', 'Заметки'],
                           'Видео' => ['videos', 'Видео', 'Видео'],
                           'Товары' => ['products', 'Товары', 'Товары'],
                           'мама' => ['mother', 'Мать', 'Мать'],
                           'папа' => ['father', 'Отец', 'Отец'],
                           'дочь' => ['daughter', 'Дочь', 'Дочь'],
                           'сын' => ['son', 'Сын', 'Сын'],
                           'бабушка' => ['grandmother', 'Бабушка', 'Бабушка'],
                           'дедушка' => ['grandfather', 'Дедушка', 'Дедушка'],
                           'внучка' => ['granddaughter', 'Внучка', 'Внучка'],
                           'внук' => ['grandson', 'Внук', 'Внук'],
                           'крёстная' => ['godmother', 'Крёстная', 'Крёстная'],
                           'крёстный' => ['godfather', 'Крёстный', 'Крёстный'],
                           'крестница' => ['goddaughter', 'Крестница', 'Крестница'],
                           'крестник' => ['godson', 'Крестник', 'Крестник'],
                           'сестра' => ['sister', 'Сестра', 'Сестра'],
                           'брат' => ['brother', 'Брат', 'Брат'],
                           'тётя' => ['aunt', 'Тётя', 'Тётя'],
                           'дядя' => ['uncle', 'Дядя', 'Дядя'],
                           'племянница' => ['aunt', 'Племянница', 'Племянница'],
                           'племянник' => ['uncle', 'Племянник', 'Племянник'],
                           'тёща' => ['motherinlaw', 'Тёща', 'Тёща'],
                           'тесть' => ['fatherinlaw', 'Тесть', 'Тесть'],
                           'свекровь' => ['motherinlaw_s', 'Свекровь', 'Свекровь'],
                           'свёкор' => ['fatherinlaw_s', 'Свёкор', 'Свёкор'],
                           'невестка' => ['daughterinlaw', 'Невестка', 'Невестка'],
                           'зять' => ['soninlaw', 'Зять', 'Зять'],
                           'родственник' => ['relative', 'Родственник', 'Родственник'],
                           'родственница' => ['relative', 'Родственник', 'Родственник'],
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
        return 'OK';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск в ОК',
            'ok_person' => 'ОК - поиск профилей по имени и дате рождения',
            'ok_phone' => 'ОК - поиск по номеру телефона',
            'ok_email' => 'ОК - поиск по email',
            'ok_url' => 'ОК - профиль пользователя',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в OK';
    }

    public function getSessionData($sourceid = 6)
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query('UPDATE isphere.session s SET request_id='.$reqId." WHERE request_id IS NULL AND sessionstatusid=2 AND (s.proxyid IS NULL OR (SELECT status FROM proxy WHERE id=s.proxyid)=1) AND sourceid=$sourceid AND unix_timestamp(now())-unix_timestamp(lasttime)>2 ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=$sourceid AND request_id=$reqId ORDER BY lasttime limit 1");

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

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL WHERE id=".$sessionData->id);
                //                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used>=10 AND id=".$sessionData->id);

                //                echo "Using session {$row->id} for source $sourceid\n";
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
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

        if ('nick' == $checktype && !isset($initData['nick'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (псевдоним)');

            return false;
        }

        if ('url' == $checktype && !isset($initData['url'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (ссылка)');

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

        if ('nick' == $checktype) {
            $initData['url'] = 'https://ok.ru/'.$initData['nick'];
        }

        if ('url' == $checktype) {
            if (false === \strpos($initData['url'], 'ok.ru/')) {
                $rContext->setFinished();

                return false;
            }
        }

        if (('nick' == $checktype || 'url' == $checktype) && !isset($swapData['found'])) {
            $swapData['found'] = true;
            $swapData['data'] = [];
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
        // //////////////////////////////////////////////////////////////////////////////////////////////////

        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData('person' == $checktype || (('nick' == $checktype || 'url' == $checktype) && isset($swapData['found'])) ? 6 : 14);

            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration'] >= 30)) {
                    if (('nick' == $checktype || 'url' == $checktype) && !isset($swapData['data'])) {
                        $swapData['found'] = true;
                        $swapData['data'] = [];
                        $rContext->setSwapData($swapData);

                        return false;
                    } else {
                        $rContext->setFinished();
                        $rContext->setError('Сервис временно недоступен');

                        return false;
                    }
                } else {
                    (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
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

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $url = 'https://ok.ru/dk';
        $params = [];
        $params['st.cmd'] = 'anonymPasswordRecoveryNew';
        $header = [];
        if (!isset($swapData['found']) && !isset($swapData['captcha']) && !isset($swapData['locked'])) {
            if ('nick' == $checktype || 'url' == $checktype) {
                $params['st.registrationAction'] = 'ValidateLink';
                $params['st.recoveryMethod'] = 'Link';
            } elseif ('phone' == $checktype || 'email' == $checktype) {
                $params['st.cmd'] = 'anonymMain';
                $params['st.accRecovery'] = 'on';
                $params['st.error'] = 'errors.password.wrong';
                $params['st.email'] = ('phone' == $checktype) ? $initData['phone'] : $initData['email'];
                $swapData['session']->cookies = '';
            } elseif ('phone' == $checktype) {
                $params['st.registrationAction'] = 'ValidatePhoneNumber';
                $params['st.recoveryMethod'] = 'Phone';
            } elseif ('email' == $checktype) {
                $params['st.registrationAction'] = 'ValidateEmail';
                $params['st.recoveryMethod'] = 'Email';
            } elseif ('text' == $checktype) {
                $url = 'https://ok.ru/search';
                $params = [];
                $params['st.mode'] = 'Users';
                $params['st.grmode'] = 'Groups';
                $params['st.posted'] = 'set';
                $params['st.query'] = $this->str_translit($initData['text']);
            } elseif ('person' == $checktype) {
                $url = 'https://ok.ru/search';
                $params = [];
                $params['st.mode'] = 'Users';
                $params['st.grmode'] = 'Groups';
                $params['st.posted'] = 'set';
                $params['st.query'] = $this->str_translit(\preg_replace("/^(.*?)\s.[\*]+$/ui", '$1', \trim($initData['last_name'].' '.$initData['first_name'])));
                if (isset($initData['date']) && \strtotime($initData['date'])) {
                    $initData['date'] = \date('d.m.Y', \strtotime($initData['date']));
                    $birth = \explode('.', $initData['date']);
                    $params['st.bthDay'] = $birth[0];
                    $params['st.bthMonth'] = $birth[1] - 1;
                    $params['st.bthYear'] = $birth[2];
                } elseif (isset($initData['date']) && \preg_match("/^[0-3]*\d\.[0-1]*\d$/", $initData['date'])) {
                    $birth = \explode('.', $initData['date']);
                    $params['st.bthDay'] = $birth[0];
                    $params['st.bthMonth'] = $birth[1] - 1;
                } elseif (isset($initData['date']) && \preg_match("/^[1-2][\d]{3}$/", $initData['birth'])) {
                    $params['st.bthYear'] = $initData['birth'];
                }
                if (isset($initData['age'])) {
                    $params['st.fromAge'] = $initData['age'];
                    $params['st.tillAge'] = $initData['age'];
                }
                if (isset($initData['location'])) {
                    $params['st.location'] = $initData['location'];
                    $params['st.city'] = $initData['location'];
                }
                \curl_setopt($ch, \CURLOPT_POST, false);
            }
        }
        if (/* $checktype!='person' && ($checktype!='url' || !isset($swapData['found'])) && */ !isset($swapData['hash'])) {
            $url .= '?'.\http_build_query($params);
        } elseif (('nick' == $checktype || 'url' == $checktype) && !isset($swapData['found']) && !isset($swapData['locked']) && !isset($swapData['captcha'])) {
            //            $params['st.countryId'] = '10414533690';
            //            $params['st.countryCode'] = '';
            if ('nick' == $checktype || 'url' == $checktype) {
                $params['st.recoveryData'] = $initData['url'];
            } elseif ('phone' == $checktype) {
                $params['st.phone'] = $initData['phone'];
            } elseif ('email' == $checktype) {
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
            \curl_setopt($ch, \CURLOPT_HEADER, true);
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($params));
        //            print "POST: ".http_build_query($params)."\n";
        } elseif (isset($swapData['locked'])) {
            $params['st.cmd'] = 'anonymAccountRecoveryFlow';
            $params['st.recStep'] = 'PrePhoneCaptcha';
            $params['st.accountAction'] = 'Init';
            $url .= '?'.\http_build_query($params);
            \curl_setopt($ch, \CURLOPT_POST, false);
        } elseif (isset($swapData['captcha'])) {
            $params['st.cmd'] = 'captcha';
            $url = 'https://ok.ru/captcha';
            $url .= '?'.\http_build_query($params);
        } elseif (('nick' == $checktype || 'url' == $checktype) && isset($swapData['data'])) {
            $url = $initData['url'];
            if (isset($swapData['about'])) {
                $url .= '/about';
            }
            \curl_setopt($ch, \CURLOPT_HEADER, false);
            \curl_setopt($ch, \CURLOPT_POST, false);
        } elseif (!isset($swapData['found'])) {
            $url .= '?'.\http_build_query($params);
        } elseif (isset($swapData['found']) && ('phone' == $checktype || 'email' == $checktype)) {
            $params['st.cmd'] = 'anonymRecoveryAfterFailedLogin';
            $params['st._aid'] = 'LeftColumn_Login_ForgotPassword';
            $url .= '?'.\http_build_query($params);
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
            $url .= '?'.\http_build_query($params);
        }
        \curl_setopt($ch, \CURLOPT_URL, $url);
        //        print "URL: $url\n";
        \curl_setopt($ch, \CURLOPT_TIMEOUT, 10);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
        //        curl_setopt($ch, CURLOPT_HEADER, true);
        //        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        \curl_setopt($ch, \CURLOPT_REFERER, 'https://ok.ru/');
        /*
                if ((isset($initData['url']) || isset($initData['phone']) || isset($initData['email'])) && isset($swapData['session'])) {
        */
        \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
        \curl_setopt($ch, \CURLOPT_COOKIEFILE, '');
        //            print "Cookie: ".$swapData['session']->cookies."\n";
        if ($swapData['session']->proxy) {
            \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
            //                print "Proxy: ".$swapData['session']->proxy."\n";
            if ($swapData['session']->proxy_auth) {
                \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
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

    public function computeRequest(array $params, &$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = \substr($initData['checktype'], 3);

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $error = false;
        $content = \curl_multi_getcontent($rContext->getCurlHandler());

        \file_put_contents('./logs/ok/'.$initData['checktype'].'_'.\time().'.html', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
        if (!$content/* && ($swapData['iteration']>3) */) {
            $curlError = \curl_error($rContext->getCurlHandler());
            if ($curlError) {
                //                $rContext->setError($curlError);
                if (\strpos($curlError, 'timed out') || \strpos($curlError, 'refused') || \strpos($curlError, 'reset by peer')) {
                    if (isset($swapData['session']) && $swapData['session']->id) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='connectionerror' WHERE id=".$swapData['session']->id);
                        //                        $mysqli->query("UPDATE proxy SET status=0 WHERE id=" . $swapData['session']->proxyid);
                        unset($swapData['session']);
                        $rContext->setSwapData($swapData);
                    }
                }
            }
            \file_put_contents('./logs/ok/'.$initData['checktype'].'_err_'.\time().'.html', $curlError."\n".\curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
            if (isset($swapData['session']) && $swapData['session']->id) {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 15 minute),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
            }

            return false;
        }

        if ($content) {
            if (('person' == $checktype || 'text' == $checktype || 'nick' == $checktype || 'url' == $checktype) && \preg_match("/<a href=\"\/dk\?st\.cmd=anonymMain\"/", $content)) {
                if (isset($swapData['session'])) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='notlogged' WHERE id=".$swapData['session']->id);
                    unset($swapData['session']);
                }
            } elseif ('person' == $checktype || 'text' == $checktype) {
                $resultData = new ResultDataList();
                $res = false;
                \file_put_contents('./logs/ok/ok_search_'.\time().'.html', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                if (\preg_match('/ results="({[^"]+)"/', $content, $matches)) {
                    \file_put_contents('./logs/ok/ok_search_'.\time().'.txt', \html_entity_decode($matches[1]));
                    $res = \json_decode(\html_entity_decode($matches[1]), true);
                    if ($res && isset($res['users']['values'])) {
                        $res = $res['users']['values'];
                    }
                }
                $found = -1;
                if ($res && isset($res['totalCount'])) {
                    $found = $res['totalCount'];
                }
                if (0 == $found) {
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);

                    return true;
                } elseif ((int) $found > 20
                    //                         || (intval($found)>10 && !preg_match("/^(.*?)\s.[\*]+$/ui",trim($initData['last_name'].' '.$initData['first_name'])))
                ) {
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
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);

                    return false;
                } elseif ($found > 0) {
                    $found = 0;
                    foreach ($res['results'] as $result) {
                        $data = [];
                        if (isset($result['user']['info'])) {
                            $user = $result['user']['info'];
                            $name = \html_entity_decode(\strip_tags(\iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $user['name']))));
                            if (!\preg_match("/^(.*?)\s[^\*]*[\*]+$/ui", \trim($initData['last_name'].' '.$initData['first_name'])) || \preg_match('/^'.\strtr(\trim($initData['last_name'].' '.$initData['first_name']), ['*' => '.', '?' => '.', '.' => '[^\s]+']).'$/ui', $name)) {
                                $data['name'] = new ResultDataField('string', 'Name', $name, 'Имя пользователя', 'Имя пользователя');
                                ++$found;

                                if (isset($user['shortLink'])) {
                                    $data['link'] = new ResultDataField('url'.($found < 10 ? ':recursive' : ''), 'Link', 'https://ok.ru'.$user['shortLink'], 'Ссылка', 'Ссылка на страницу в соцсети');
                                }
                                if (isset($user['imgUrl'])) {
                                    if (\preg_match("/^\/\//", $user['imgUrl'])) {
                                        $user['imgUrl'] = 'https:'.$user['imgUrl'];
                                    }
                                    $data['piclink'] = new ResultDataField('image', 'Photo', $user['imgUrl'], 'Фото', 'Фотография пользователя');
                                }
                                if (isset($user['city'])) {
                                    $data['location'] = new ResultDataField('string', 'Location', \html_entity_decode(\strip_tags(\iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $user['city'])))), 'Местоположение', 'Местоположение');
                                }
                            }
                        }
                        if (\count($data)) {
                            $resultData->addResult($data);
                        }
                    }
                    if ($found < 10) {
                        $rContext->setResultData($resultData);
                    }
                    if (!$error) {
                        $rContext->setFinished();
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);

                        return true;
                    }
                } elseif (!\preg_match('/PopLayerLogoffUserModal/', $content)) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='notlogged' WHERE id=".$swapData['session']->id);
                        unset($swapData['session']);
                    }
                } else {
                    \file_put_contents('./logs/ok/ok_search_err_'.\time().'.html', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                    $error = 'Не удалось выполнить поиск';
                    if (isset($swapData['session'])) {
                        //                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='searcherror' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    }
                }
            } elseif (!isset($swapData['hash'])) {
                //                file_put_contents('./logs/ok/ok_start_'.time().'.html',$content);
                if (\preg_match("/<img src=\"\/captcha/", $content)) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=".$swapData['session']->id);
                        unset($swapData['session']);
                    }
                //                    $rContext->setSleep(1);
                //                    $error = 'Не удалось выполнить поиск';
                //                    $swapData['needcaptcha'] = 1;
                //                    $rContext->setSwapData($swapData);
                //                    return true;
                } elseif (\preg_match('/,gwtHash:"([^"]+)",/', $content, $matches)) {
                    $swapData['hash'] = $matches[1];
                } else {
                    $error = 'Не удалось подключиться к сервису';
                }
            } elseif (!isset($swapData['found']) && ('phone' == $checktype || 'email' == $checktype)) {
                \file_put_contents('./logs/ok/ok_recovery_'.\time().'.html', $content);
                if (\strpos($content, 'Восстановить профиль')) {
                    $cookies = str_cookies($swapData['session']->cookies);
                    foreach (\curl_getinfo($rContext->getCurlHandler(), \CURLINFO_COOKIELIST) as $cookie) {
                        $arr = \explode('	', $cookie);
                        //                        if ($arr[0]=='.ok.ru') {
                        $cookies[$arr[5]] = $arr[6];
                        //                        }
                    }
                    $new_cookies = cookies_str($cookies);
                    $swapData['session']->cookies = $new_cookies;

                    $swapData['found'] = true;
                }
            } elseif (!isset($swapData['found']) && !isset($swapData['locked']) && !isset($swapData['captcha'])) {
                \file_put_contents('./logs/ok/ok_header_'.\time().'.html', $content);
                if (\strpos($content, 'ChooseCodeDestination')) {
                    $swapData['found'] = true;
                } elseif (\strpos($content, 'NotSubject')) {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);

                    return true;
                } elseif (\strpos($content, 'RecoveryImpossible')) {
                    if ('nick' == $checktype || 'url' == $checktype) {
                        $swapData['found'] = true;
                        $swapData['data'] = [];
                    } else {
                        $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');

                        $resultData = new ResultDataList();
                        $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);

                        return true;
                    }
                } elseif (\strpos($content, 'anonymAccountRecoveryFlow') || \strpos($content, 'anonymBlockedByAdmin')) {
                    $swapData['locked'] = 1;
                } elseif (\strpos($content, 'Неправильно') || \strpos($content, 'Введите код')) {
                    if (isset($swapData['session'])) {
                        $mysqli->query('UPDATE isphere.session SET unlocktime=date_add(now(),interval 6 hour),sessionstatusid=6 WHERE id='.$swapData['session']->id);
                        //                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    }
                //                    $error = 'Не удалось выполнить поиск';
                } elseif (\strpos($content, 'anonymPasswordRecoveryNew')) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='recoveryerror' WHERE id=".$swapData['session']->id);
                        unset($swapData['session']);
                    }
                //                    $error = 'Не удалось выполнить поиск';
                } elseif (\preg_match("/<span class=\"input-e[^>]+>([^<]+)<\/span>/", $content, $matches)) {
                    $error = $matches[1];
                } else {
                    \file_put_contents('./logs/ok/ok_err_'.\time().'.html', $content);
                    if ($swapData['iteration'] > 10) {
                        $error = 'Некорректный ответ сервиса';
                    }
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='invalidanswer' WHERE id=".$swapData['session']->id);
                        unset($swapData['session']);
                    }
                }
            } elseif (isset($swapData['locked'])) {
                //                file_put_contents('./logs/ok/ok_locked_'.time().'.html',$content);
                //                $swapData['captcha']++;
                $resultData = new ResultDataList();
                if (isset($initData['phone'])) {
                    $data['phone'] = new ResultDataField('string', 'Phone', $initData['phone'], 'Телефон', 'Телефон');
                } elseif (\preg_match("/телефону ([^\,]+)/", $content, $matches)) {
                    $data['phone'] = new ResultDataField('string', 'Phone', \strip_tags($matches[1]), 'Телефон', 'Телефон');
                }
                if (isset($initData['email'])) {
                    $data['email'] = new ResultDataField('string', 'Email', $initData['email'], 'E-mail', 'E-mail');
                }
                $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                $data['blocked'] = new ResultDataField('string', 'blocked', 'true', 'Заблокирован', 'Заблокирован');
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);

                return true;
            } elseif (isset($swapData['captcha'])) {
                //                file_put_contents('./logs/ok/okcaptcha_'.time().'.jpg',$content);
                $error = 'Ошибка распознавания капчи';
            } elseif (('nick' == $checktype || 'url' == $checktype) && isset($swapData['data']) && !isset($swapData['about'])) {
                \file_put_contents('./logs/ok/ok_profile_'.\time().'.html', $content);
                $data = $swapData['data'];

                if (\preg_match('/"AuthLoginPopup"/', $content)) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='loggedout' WHERE sourceid=6 AND id=".$swapData['session']->id);
                        unset($swapData['session']);
                    }
                } else {
                    if (\preg_match('/tsid="page-not-found"/', $content, $matches)) {
                        $resultData = new ResultDataList();
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);

                        return true;
                    }

                    $data['link'] = new ResultDataField('url:recursive', 'Link', $initData['url'], 'Ссылка', 'Ссылка на профиль');
                    if (\preg_match('/<h1[^>]*>([^<]+)/', $content, $matches)) {
                        $matches[1] = \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $matches[1]));
                        $data['name'] = new ResultDataField('string', 'Name', $matches[1], 'Имя', 'Имя');
                    }
                    if (\preg_match('/__l" data-id="([^"]+)">/', $content, $matches)) {
                        $data['uid'] = new ResultDataField('string', 'uid', $matches[1], 'ID пользователя', 'ID пользователя');
                    }
                    if (\preg_match('/<span[^>]*>Последний визит: ([^<]+)/', $content, $matches)) {
                        $data['last_visited'] = new ResultDataField('string', 'last_visited', \strip_tags($matches[1]), 'Время последнего посещения', 'Время последнего посещения');
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
                    if (\preg_match("/<div class=\"lcTc_avatar __l\"><img [^\/]+([^\s]+)/", $content, $matches)) {
                        $data['photo'] = new ResultDataField('image', 'photo', 'https:'.\html_entity_decode($matches[1]).'_2x', 'Фото профиля', 'Фото профиля');
                    } elseif (\preg_match("/<div class=\"lcTc_avatar __l\"><a href=[^>]+><img [^\/]+(^\s]+)/", $content, $matches)) {
                        $data['photo'] = new ResultDataField('image', 'photo', 'https:'.\html_entity_decode($matches[1]).'_2x', 'Фото профиля', 'Фото профиля');
                    } elseif (\preg_match("/<img srcset=\"[^\s]+ 1x, ([^\s]+) 2x\"/", $content, $matches)) {
                        $data['photo'] = new ResultDataField('image', 'photo', 'https:'.\html_entity_decode($matches[1]), 'Фото профиля', 'Фото профиля');
                    } elseif (\preg_match('/<img width="288" height="288" src="([^"]+)/', $content, $matches)) {
                        $data['photo'] = new ResultDataField('image', 'photo', 'https:'.\html_entity_decode($matches[1]), 'Фото профиля', 'Фото профиля');
                    }

                    $swapData['data'] = $data;
                    $swapData['about'] = 1;
                }
            } elseif (('nick' == $checktype || 'url' == $checktype) && isset($swapData['data'])) {
                \file_put_contents('./logs/ok/ok_about_'.\time().'.html', $content);
                $data = $swapData['data'];
                $counter = ['other' => 0, 'relative' => 0];

                if (\preg_match_all("/<a class=\"mctc_navMenuSec[^>]+>([^<]+)<span class=\"navMenuCount\">([^<]+)<\/span><\/a>/", $content, $matches)) {
                    foreach ($matches[1] as $key => $val) {
                        $title = \trim(\str_replace('&nbsp;', '', $val));
                        $text = \trim(\str_replace('&nbsp;', '', $matches[2][$key]));
                        if (isset($this->names[$title])) {
                            $field = $this->names[$title];
                            $counter[$field[0]] = isset($counter[$field[0]]) ? $counter[$field[0]] + 1 : 0;
                            $data[$field[0].($counter[$field[0]] ?: '')] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $text, $field[1], $field[2]);
                        } elseif (false === \strpos($title, 'праздн')) {
                            ++$counter;
                            $data['other'.$counter['other']] = new ResultDataField('string', 'other'.$counter['other'], $text, $title, $title);
                            \file_put_contents('./logs/fields/ok'.\time().'_'.$title, $title."\n".$text);
                        }
                    }
                }

                if (// preg_match_all("/<span class=\"user-profile_i_t_inner\">([^<]+)<\/span><\/div><\/div><div class=\"user-profile_i_val[^>]+>(.*?)<\/div>.*?<span class=\"darkgray\">([^<]+)<\/span>/", $content, $matches) ||
                    \preg_match_all("/<span class=\"user-profile_i_t_inner\">([^<]+)<\/span><\/div><\/div><div class=\"user-profile_i_val[^>]+>(.*?)<\/div>/", $content, $matches)) {
                    foreach ($matches[1] as $key => $val) {
                        $title = \trim(\str_replace(':', '', $val));
                        $text = \trim(\str_replace('&#039;', "'", \html_entity_decode(\strip_tags($matches[2][$key].(isset($matches[3][$key]) ? ', '.$matches[3][$key] : '')))));
                        $text = \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $text));

                        if (isset($this->names[$title])) {
                            $field = $this->names[$title];
                            $counter[$field[0]] = isset($counter[$field[0]]) ? $counter[$field[0]] + 1 : 0;
                            if ('birthday' == $field[0]) {
                                $repl = [' ' => '.',
                                    'января' => '01', 'февраля' => '02', 'марта' => '03', 'апреля' => '04', 'мая' => '05', 'июня' => '06',
                                    'июля' => '07', 'августа' => '08', 'сентября' => '09', 'октября' => '10', 'ноября' => '11', 'декабря' => '12'];
                                if (\strpos($text, '(')) {
                                    $text = \trim(\substr($text, 0, \strpos($text, '(')));
                                }
                                if (1 == \strpos($text, ' ')) {
                                    $text = '0'.$text;
                                }
                                $text = \strtr($text, $repl);
                            }
                            $data[$field[0].($counter[$field[0]] ?: '')] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $text, $field[1], $field[2]);
                            if (\preg_match('/<a class=\"o user-profile_i_relation-t\" href=\"([^\?]+)[^\"]+\">/', $matches[2][$key], $link)) {
                                $url = 'https://ok.ru'.$link[1];
                                $data[$field[0].($counter[$field[0]] ?: '').'_link'] = new ResultDataField('url', $field[0].'_link', $url, $field[1], $field[2]);
                            }
                        } elseif (false === \strpos($title, 'праздн')) {
                            ++$counter;
                            $data['other'.$counter['other']] = new ResultDataField('string', 'other'.$counter['other'], $text, $title, $title);
                            \file_put_contents('./logs/fields/ok'.\time().'_'.$title, $title."\n".$text);
                        }
                    }
                }

                if (\preg_match_all("/<a class=\"o user-profile_i_relation-t ellip\" href=\"([^\?]+)[^>]+><span>([^<]+)<\/span><div [^>]+><\/div><\/a><div class=\"ellip lstp-t\">([^<]+)<\/div>/", $content, $matches)) {
                    foreach ($matches[1] as $key => $val) {
                        $text = \trim(\str_replace('&#039;', "'", \html_entity_decode(\strip_tags($matches[2][$key]))));
                        $text = \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $text));
                        $title = \trim(\str_replace('&#039;', "'", \html_entity_decode(\strip_tags($matches[3][$key]))));
                        $url = 'https://ok.ru'.$val;

                        if (isset($this->names[$title])) {
                            $field = $this->names[$title];
                            $counter[$field[0]] = isset($counter[$field[0]]) ? $counter[$field[0]] + 1 : 0;
                            $data[$field[0].($counter[$field[0]] ?: '')] = new ResultDataField('string', $field[0], $text, $field[1], $field[2]);
                            $data[$field[0].$counter[$field[0]].'_link'] = new ResultDataField('url', $field[0].'_link', $url, $field[1], $field[2]);
                        } else {
                            ++$counter[''];
                            $data['relative'.$counter['relative']] = new ResultDataField('string', 'relative'.$counter['relative'], $text, $title, $title);
                            $data['relative'.$counter[''].'_link'] = new ResultDataField('url', 'relative_link', $url, $title, $title);
                            \file_put_contents('./logs/fields/ok'.\time().'_rel_'.$title, $title."\n".$text);
                        }
                    }
                }

                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);

                return true;
            } elseif ('phone' == $checktype || 'email' == $checktype) {
                \file_put_contents('./logs/ok/ok_login_'.\time().'.html', $content);
                $resultData = new ResultDataList();
                if (\preg_match('/восстановления/', $content) || \preg_match('/можно восстановить/', $content) || \preg_match('/можно было бы восстановить/', $content)) {
                    $data = [];
                    if (isset($initData['phone'])) {
                        $data['phone'] = new ResultDataField('string', 'Phone', $initData['phone'], 'Телефон', 'Телефон');
                    } elseif (\preg_match("/<div class=\"ext-registration_stub_small_header\">([^<]+)<\/div><div class=\"ext-registration_stub_small_text\">Телефон/", $content, $matches)) {
                        $data['phone'] = new ResultDataField('string', 'Phone', \strip_tags($matches[1]), 'Телефон', 'Телефон');
                    }
                    if (isset($initData['email'])) {
                        $data['email'] = new ResultDataField('string', 'Email', $initData['email'], 'E-mail', 'E-mail');
                    } elseif (\preg_match("/<div class=\"ext-registration_stub_small_header\">([^<]+)<\/div><div class=\"ext-registration_stub_small_text\">Почта/", $content, $matches)) {
                        $data['email'] = new ResultDataField('string', 'Email', \strtr(\strip_tags($matches[1]), ['X' => '*']), 'E-mail', 'E-mail');
                    }

                    if (\preg_match("/<div class=\"ext-registration_username_header\">([^<]+)<\/div>/", $content, $matches)) {
                        //                        $name = iconv('windows-1251','utf-8',html_entity_decode(iconv('utf-8','windows-1251//IGNORE',strip_tags($matches[1]))));
                        $name = \trim(\preg_replace("/[^А-Яа-яЁёA-Za-z\s\-\.\*]/ui", ' ', \html_entity_decode(\strip_tags($matches[1]))));
                        $data['name'] = new ResultDataField('string', 'Name', $name, 'Имя', 'Имя');

                        if (isset($initData['first_name'])) {
                            $split_name = \explode(' ', $this->str_uprus($name));
                            $first_name = '';
                            for ($i = 0; $i < \count($split_name) - 1; ++$i) {
                                $first_name = \trim($first_name.' '.$split_name[$i]);
                            }
                            $last_name = \strtr($split_name[\count($split_name) - 1], ['*' => '']);

                            if ($this->str_uprus($initData['first_name']) == $first_name) {
                                if (isset($initData['last_name']) && ($this->str_uprus(\mb_substr($initData['last_name'], 0, 1)) == \mb_substr($last_name, 0, 1))) {
                                    $match_code = 'MATCHED';
                                } else {
                                    $match_code = 'MATCHED_NAME_ONLY';
                                }
                            } else {
                                $match_code = 'NOT_MATCHED';
                            }
                            $data['match_code'] = new ResultDataField('string', 'match_code', $match_code, 'Результат сравнения имени', 'Результат сравнения имени');
                        }
                    }

                    if (\preg_match_all("/<div class=\"lstp-t\">([^<]+)<\/div>/", $content, $matches)) {
                        foreach ($matches[1] as $key => $text) {
                            if (\preg_match('/Профиль создан/', $text)) {
                                $text = \mb_substr($text, 15);
                                $repl = [' ' => '.',
                                    'января' => '01', 'февраля' => '02', 'марта' => '03', 'апреля' => '04', 'мая' => '05', 'июня' => '06',
                                    'июля' => '07', 'августа' => '08', 'сентября' => '09', 'октября' => '10', 'ноября' => '11', 'декабря' => '12'];
                                $text = \strtr($text, $repl);
                                if (9 == \strlen($text)) {
                                    $text = '0'.$text;
                                }
                                $data['created'] = new ResultDataField('string', 'Created', $text, 'Зарегистрирован', 'Зарегистрирован');
                            } else {
                                $text = \explode(',', $text);
                                $data['age'] = new ResultDataField('string', 'Age', \trim($text[0]), 'Возраст', 'Возраст');
                                if (isset($text[1]) && \trim($text[1])) {
                                    $data['location'] = new ResultDataField('string', 'Location', \strtr(\trim(\html_entity_decode(\strip_tags($text[1]))), ['–' => '-']), 'Местоположение', 'Местоположение');
                                }
                            }
                        }
                    }

                    $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);

                    return true;
                } elseif (\preg_match('/вы помните/', $content)) {
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);

                    return true;
                } elseif (!$content) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                        unset($swapData['session']);
                    }
                } else {
                    \file_put_contents('./logs/ok/ok_login_err_'.\time().'.html', $content);
                    $error = 'Некорректный ответ сервиса';
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='loginerror' WHERE id=".$swapData['session']->id);
                        unset($swapData['session']);
                    }
                }
            } else {
                \file_put_contents('./logs/ok/ok_result_'.\time().'.html', $content);
                if (!\preg_match('/anonymPasswordRecoveryNew/', $content)) {
                    if (('nick' == $checktype || 'url' == $checktype) && $swapData['iteration'] > 10) {
                        $swapData['data'] = [];
                        unset($swapData['session']);
                    } else {
                        if (isset($swapData['session'])) {
                            $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='recoveryerror' WHERE id=".$swapData['session']->id);
                            unset($swapData['session']);
                        }
                        unset($swapData['found']);
                        //                        $rContext->setSleep(1);
                        //                        $error = 'Не удалось получить ответ';
                        //                        $rContext->setSwapData($swapData);
                        //                        return true;
                    }
                } else {
                    $data = [];
                    if (isset($initData['phone'])) {
                        $data['phone'] = new ResultDataField('string', 'Phone', $initData['phone'], 'Телефон', 'Телефон');
                    } elseif (\preg_match("/номер ([^<]+)<\/div>/", $content, $matches)) {
                        $data['phone'] = new ResultDataField('string', 'Phone', \strip_tags($matches[1]), 'Телефон', 'Телефон');
                    }
                    if (isset($initData['email'])) {
                        $data['email'] = new ResultDataField('string', 'Email', $initData['email'], 'E-mail', 'E-mail');
                    } elseif (\preg_match("/почту ([^<]+)<\/div>/", $content, $matches)) {
                        $data['email'] = new ResultDataField('string', 'Email', \strtr(\strip_tags($matches[1]), ['X' => '*']), 'E-mail', 'E-mail');
                    }
                    if ('nick' == $checktype || 'url' == $checktype) {
                        $swapData['data'] = $data;
                        unset($swapData['session']);
                    } else {
                        if (\preg_match("/<div class=\"recovery_profile\">([^<]+)<\/div>/", $content, $matches)) {
                            //                            $name = iconv('windows-1251','utf-8',html_entity_decode(iconv('utf-8','windows-1251//IGNORE',strip_tags($matches[1]))));
                            $name = \trim(\preg_replace("/[^А-Яа-яЁёA-Za-z\s\-\.\*]/ui", ' ', \html_entity_decode(\strip_tags($matches[1]))));
                            $data['name'] = new ResultDataField('string', 'Name', $name, 'Имя', 'Имя');

                            if (isset($initData['first_name'])) {
                                $split_name = \explode(' ', $this->str_uprus($name));
                                $first_name = '';
                                for ($i = 0; $i < \count($split_name) - 1; ++$i) {
                                    $first_name = \trim($first_name.' '.$split_name[$i]);
                                }
                                $last_name = \strtr($split_name[\count($split_name) - 1], ['*' => '']);

                                if ($this->str_uprus($initData['first_name']) == $first_name) {
                                    if (isset($initData['last_name']) && ($this->str_uprus(\mb_substr($initData['last_name'], 0, 1)) == \mb_substr($last_name, 0, 1))) {
                                        $match_code = 'MATCHED';
                                    } else {
                                        $match_code = 'MATCHED_NAME_ONLY';
                                    }
                                } else {
                                    $match_code = 'NOT_MATCHED';
                                }
                                $data['match_code'] = new ResultDataField('string', 'match_code', $match_code, 'Результат сравнения имени', 'Результат сравнения имени');
                            }
                        }
                        $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');

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

        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] > 20) {
            $error = 'Превышено количество попыток получения ответа';
        }

        if ($error && isset($swapData['iteration']) && $swapData['iteration'] > 2) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        $rContext->setSleep(1);

        return true;
    }
}
