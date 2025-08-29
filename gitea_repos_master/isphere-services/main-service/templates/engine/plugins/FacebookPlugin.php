<?php

use Doctrine\DBAL\Connection;

class FacebookPlugin implements PluginInterface
{
    private $names = ['Образование' => ['education', 'Образование', 'Образование'], 'Работа' => ['career', 'Карьера', 'Карьера'], 'Умения и навыки' => ['expirience', 'Умения и навыки', 'Умения и навыки'], 'Места, в которых он жил' => ['places', 'Места проживания', 'Места проживания', 'block'], 'Места, в которых она жила' => ['places', 'Места проживания', 'Места проживания', 'block'], 'Места, в которых он(-а) проживал(-а)' => ['places', 'Места проживания', 'Места проживания', 'block'], 'Контактная информация' => ['contacts', 'Контакты', 'Контакты', 'block'], 'Основная информация' => ['info', 'Основная информация', 'Основная информация', 'block'], 'Другие имена' => ['aliases', 'Другие имена', 'Другие имена'], 'Семейное положение' => ['family', 'Семейное положение', 'Семейное положение'], 'Родственники' => ['relatives', 'Родственники', 'Родственники'], 'О пользователе' => ['about', 'О себе', 'О себе'], 'События из жизни' => ['events', 'События из жизни', 'События из жизни'], 'Любимые цитаты' => ['quotations', 'Любимые цитаты', 'Любимые цитаты'], 'Родной город' => ['birthplace', 'Родной город', 'Родной город'], 'Город проживания' => ['livingplace', 'Город проживания', 'Город проживания'], 'Город' => ['livingplace', 'Город проживания', 'Город проживания'], 'Район' => ['livingdistrict', 'Район', 'Район'], 'Адрес' => ['livingplace', 'Адрес', 'Адрес'], 'Переехал' => ['livingplace', 'Город проживания', 'Город проживания'], 'Купил' => ['livingplace', 'Город проживания', 'Город проживания'], 'Имя' => ['fullname', 'Имя', 'Имя'], 'Пол' => ['gender', 'Пол', 'Пол'], 'Предпочтения' => ['preferences', 'Предпочтения', 'Предпочтения'], 'Интересуют:' => ['interests', 'Интересуют', 'Интересуют'], 'Религиозные взгляды' => ['religion', 'Религия', 'Религия'], 'Политические взгляды' => ['politics', 'Политика', 'Политика'], 'Языки' => ['languages', 'Языки', 'Языки'], 'Умения и навыки' => ['skills', 'Умения и навыки', 'Умения и навыки'], 'Дата рождения' => ['birthdate', 'Дата рождения', 'Дата рождения'], 'День рождения' => ['birthday', 'День рождения', 'День рождения'], 'Год рождения' => ['birthyear', 'Год рождения', 'Год рождения'], 'Именины' => ['nameday', 'Именины', 'Именины'], 'Мобильный' => ['mobile_phone', 'Мобильный телефон', 'Мобильный телефон'], 'Рабочий' => ['work_phone', 'Рабочий телефон', 'Рабочий телефон'], 'Домашний' => ['home_phone', 'Домашний телефон', 'Домашний телефон'], 'Skype' => ['skype', 'Skype', 'Skype', 'skype'], 'ICQ' => ['icq', 'ICQ', 'ICQ'], 'QIP' => ['qip', 'QIP', 'QIP'], 'AIM' => ['aim', 'AIM', 'AIM'], 'BBM' => ['bbm', 'BlackBerry Messenger', 'BlackBerry Messenger'], 'Live' => ['live', 'Windows Live Messenger', 'Windows Live Messenger'], 'LINE' => ['line', 'Line', 'Line'], 'Snapchat' => ['snapchat', 'Snapchat', 'Snapchat'], 'Google Talk' => ['google_talk', 'Google Talk', 'Google Talk'], 'Facebook' => ['facebook', 'Facebook', 'Facebook', 'url', 'https://www.facebook.com'], 'Twitter' => ['twitter', 'Twitter', 'Twitter', 'url:recursive'], 'Instagram' => ['instagram', 'Instagram', 'Instagram', 'url:recursive', 'https://www.instagram.com/'], 'YouTube' => ['youtube', 'YouTube', 'YouTube'], 'SoundCloud' => ['soundcloud', 'SoundCloud', 'SoundCloud'], 'Ask.fm' => ['askfm', 'Ask.fm', 'Ask.fm'], 'VK' => ['vk', 'VK', 'VK', 'url:recursive'], 'OK' => ['ok', 'OK', 'OK', 'url:recursive'], 'LinkedIn' => ['linkedin', 'LinkedIn', 'LinkedIn', 'url:recursive'], 'Веб-сайт' => ['website', 'Сайт', 'Сайт', 'url:recursive'], 'Веб-сайты' => ['website', 'Сайт', 'Сайт', 'url:recursive'], 'Электронный адрес' => ['email', 'Email', 'Email', 'email'], 'Эл. адрес' => ['email', 'Email', 'Email', 'email'], 'Эл. почта' => ['email', 'Email', 'Email', 'email']];

    public function getName()
    {
        return 'Facebook';
    }

    public function getTitle($checktype = '')
    {
        $title = ['' => 'Поиск в Facebook', 'facebook_person' => 'Facebook - поиск профилей по имени', 'facebook_phone' => 'Facebook - проверка телефона на наличие пользователя', 'facebook_email' => 'Facebook - проверка email на наличие пользователя', 'facebook_url' => 'Facebook - профиль пользователя'];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в Facebook';
    }

    public function getSessionData(array $params, $sourceid = 18, $proxyid = false)
    {
        $connection = $params['_connection'];
        \assert($connection instanceof Connection);
        $reqId = $params['_reqId'];
        $sessionData = null;
        if ($sourceid) {
            if (!$proxyid) {
                $proxyid = 's.proxyid';
            }
            $connection->executeStatement("UPDATE session s SET request_id={$reqId} WHERE sessionstatusid=2 AND sourceid={$sourceid} AND unix_timestamp(now())-unix_timestamp(lasttime)>10 ORDER BY lasttime limit 1");
            $result = $connection->executeQuery("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id={$proxyid}) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sessionstatusid=2 AND sourceid={$sourceid} AND request_id={$reqId} ORDER BY lasttime limit 1");
        } else {
            $result = $connection->executeQuery("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE enabled=1 AND status=1 AND country<>'ru' ORDER BY lasttime limit 1");
        }
        if ($result) {
            $row = $result->fetchAssociative();
            if ($row) {
                $sessionData = new \stdClass();
                $sessionData->id = 0;
                $sessionData->proxyid = $row['proxyid'];
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                if ($sourceid) {
                    $sessionData->id = $row['id'];
                    $sessionData->code = $row['captcha'];
                    $sessionData->token = $row['token'];
                    $sessionData->starttime = $row['starttime'];
                    $sessionData->lasttime = $row['lasttime'];
                    $sessionData->cookies = $row['cookies'];
                    $connection->executeStatement("UPDATE session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL WHERE id=".$sessionData->id);
                }
                if (!$row['proxyid']) {
                    //                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE enabled=1 AND status=1 AND country='ru' AND (rotation>0 OR (SELECT COUNT(*) FROM session WHERE proxyid=proxy.id AND sourceid=48 AND sessionstatusid IN (1,2,6,7))<1) ORDER BY lasttime limit 1");
                    $result = $connection->executeQuery("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE status=1 AND country<>'ru' AND rotation>0 ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetchAssociative();
                        if ($row) {
                            $sessionData->proxyid = $row['proxyid'];
                            $sessionData->proxy = $row['proxy'];
                            $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                            //                            $mysqli->query("UPDATE proxy SET lasttime=now() WHERE id=".$row['proxyid']);
                            $connection->executeStatement('UPDATE session SET proxyid='.$row['proxyid'].' WHERE id='.$sessionData->id);
                        }
                    }
                }
                $connection->executeStatement('UPDATE proxy SET lasttime=now(),used=ifnull(used,0)+1 WHERE id='.$row['proxyid']);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $checktype = \substr($initData['checktype'], 9);
        if (!isset($initData['url']) && !isset($initData['phone']) && !isset($initData['email']) && (!isset($initData['last_name']) || !isset($initData['first_name'])) && !isset($initData['text'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (ссылка, телефон, email или фамилия+имя)');

            return false;
        }
        if (isset($initData['phone'])) {
            //            if (strlen($initData['phone'])==10)
            //                $initData['phone']='7'.$initData['phone'];
            //            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            //                $initData['phone']='7'.substr($initData['phone'],1);
            $swapData['phone'] = $initData['phone'];
        }
        if (isset($initData['url'])) {
            if (false === \strpos($initData['url'], 'facebook.com/')) {
                $rContext->setFinished();

                return false;
            }
            $swapData['path'] = \strtr($initData['url'], ['://facebook.com' => '://www.facebook.com', 'app_scoped_user_id/' => 'profile.php?id=']);
        }
        /*
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
                return false;
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData($params, 'phone' == $checktype || 'email' == $checktype ? 31 : 18);
        }
        if (!$swapData['session']) {
            if (isset($swapData['iteration']) && $swapData['iteration'] >= 30) {
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
            } else {
                $rContext->setSwapData($swapData);
                $rContext->setSleep(1);
            }

            return false;
        }
        $rContext->setSwapData($swapData);
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        $host = 'https://www.facebook.com';
        $post = false;
        if ('phone' == $checktype || 'email' == $checktype) {
            if (!isset($swapData['urls'])) {
                $url = $host.'/ajax/login/help/identify.php?ctx=recover';
                $post = \http_build_query([
                    //                    'jazoest' => '2863',
                    'lsd' => $swapData['session']->token,
                    'email' => isset($initData['phone']) ? $initData['phone'] : $initData['email'],
                    'did_submit' => 1,
                    '__user' => 0,
                    '__a' => 1,
                ]);
                $header[] = 'Accept: */*';
                $header[] = 'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3';
                $header[] = 'Referer: '.$host.'/login/identify/?absolute_url_processed=1&ars=facebook_login&ctx=recover&from_login_screen=1';
                $header[] = 'Origin: '.$host;
                $header[] = 'Content-Type: application/x-www-form-urlencoded';
                $header[] = 'X-FB-LSD: '.$swapData['session']->token;
                $header[] = 'Sec-Fetch-Dest: empty';
                $header[] = 'Sec-Fetch-Mode: no-cors';
                $header[] = 'Sec-Fetch-Site: same-origin';
                //                $header[] = 'Connection: keep-alive';
                //                $header[] = 'Pragma: no-cache';
                //                $header[] = 'Cache-Control: no-cache';
                //                $header[] = 'TE: trailers';
                \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
            //                curl_setopt($ch, CURLOPT_HEADER, true);
            //                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            } else {
                $url = $host.$swapData['urls'][$swapData['urlnum']];
                //                curl_setopt($ch, CURLOPT_HEADER, true);
                //                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            }
        } elseif (!isset($swapData['path'])) {
            //            $c_user = '';
            //            if (preg_match("/c_user=([\d]+);/",$swapData['session']->cookies,$matches)) {
            //                $c_user = $matches[1];
            //            }
            //            $url = $host.'/ajax/typeahead/search.php?value='.urlencode(isset($initData['phone']) ? $initData['phone'] : $initData['email']).'&viewer='.$c_user.'&__a=1';
            //            $url = $host.'/ds/search.php?__ajax__&q='.urlencode(isset($initData['phone']) ? $initData['phone'] : $initData['email']);
            //            $url = $host.'/search/people/?__ajax__&q='.urlencode(isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : $initData['text']));
            //            $url = $host.'/search/people/?q='.urlencode(isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : $initData['text']));
            $url = $host.'/search/str/'.\urlencode(isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : (isset($initData['text']) ? $initData['text'] : $initData['last_name'].' '.$initData['first_name']))).'/keywords_users';
        //            $rContext->setSleep(1);
        } elseif (!isset($swapData['about'])) {
            $url = \strtr($swapData['path'], ['://www.' => '://m.']);
        } elseif (isset($swapData['photo_path'])) {
            $url = $swapData['photo_path'];
        } else {
            $url = \strtr($swapData['path'], ['://www.' => '://m.']).(\strpos($swapData['path'], '/profile.php') ? '&v=info' : '/about');
        }
        //        print "URL: $url\n";
        //        $cookies = 'c_user=' . $this->facebook_user . '; xs=' . $this->facebook_xs . '; datr=';
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
        //        curl_setopt($ch, CURLOPT_REFERER, $url);
        //        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
        \curl_setopt($ch, \CURLOPT_ENCODING, '');
        if ($post) {
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $post);
        }
        \curl_setopt($ch, \CURLOPT_COOKIE, 'lh=ru_RU; locale=ru_RU; '.$swapData['session']->cookies);
        \curl_setopt($ch, \CURLOPT_COOKIEFILE, '');
        //        print "Cookie: ".$swapData['session']->cookies."\n";
        if (isset($swapData['urls'])) {
            $s = $this->getSessionData($params, 0);
            \curl_setopt($ch, \CURLOPT_PROXY, $s->proxy);
            //            print "Proxy: ".$s->proxy."\n";
            if ($s->proxy_auth) {
                \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $s->proxy_auth);
                \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
            }
        } elseif ($swapData['session']->proxy) {
            \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
            }
        }
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $checktype = \substr($initData['checktype'], 9);
        $swapData['iteration'] = !isset($swapData['iteration']) ? 1 : $swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);
        $error = false;
        $curlError = \curl_error($rContext->getCurlHandler());
        if ($curlError && $swapData['iteration'] > 10) {
            $rContext->setFinished();
            $rContext->setError('' == $curlError ? 'Превышено количество попыток получения ответа' : $curlError);

            return false;
        }
        if (!$curlError) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());
            $data = [];
            if ('phone' == $checktype || 'email' == $checktype) {
                if (!isset($swapData['urls'])) {
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_identify_'.\time().'.html', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                    $cookies = \App\Utils\Legacy\CookieUtilStatic::str_cookies($swapData['session']->cookies);
                    foreach (\curl_getinfo($rContext->getCurlHandler(), \CURLINFO_COOKIELIST) as $cookie) {
                        $arr = \explode('	', $cookie);
                        //                        if ($arr[0]=='.facebook.com') {
                        $cookies[$arr[5]] = $arr[6];
                        //                        }
                    }
                    $new_cookies = \App\Utils\Legacy\CookieUtilStatic::cookies_str($cookies);
                    $swapData['session']->cookies = $new_cookies;
                    $start = \strpos($content, '{');
                    $content = \trim(\substr($content, $start, \strlen($content) - $start + 1));
                    $res = \json_decode($content, true);
                    if (isset($res['domops'][0][3]['__html'])) {
                        $html = $res['domops'][0][3]['__html'];
                        //                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_identify_html_' . time() . '.html', $html);
                        if (\preg_match('/не найдено/', $html)) {
                            $resultData = new ResultDataList();
                            $rContext->setResultData($resultData);
                            $rContext->setFinished();
                            $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                            $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                        } elseif (\preg_match_all('/<div class="[^"]+">([^<]+)<\\/div><div class="[^"]+">Пользователь Facebook<\\/div>/sim', $html, $matches)) {
                            $resultData = new ResultDataList();
                            foreach ($matches[1] as $name) {
                                $data = [];
                                if (isset($initData['phone']) && $name != '+'.$initData['phone'] || isset($initData['email']) && $name != $initData['email']) {
                                    $data['name'] = new ResultDataField('string', 'Name', \strtr($name, ['...' => '.']), 'Имя', 'Имя');
                                }
                                $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                                $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                                $resultData->addResult($data);
                            }
                            $rContext->setResultData($resultData);
                            $rContext->setFinished();
                            $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                            $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                        } elseif (\preg_match_all('/<a role="button" class="[^"]+" href="(\\/login\\/identify[^"]+)"/', $html, $matches)) {
                            foreach ($matches[1] as $key => $url) {
                                $swapData['urls'][] = \html_entity_decode($url);
                                $swapData['urlnum'] = 0;
                            }
                        } elseif ($swapData['iteration'] > 5) {
                            $error = 'Некорректный ответ сервиса';
                            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_identify_html_err_'.\time().'.html', $html);
                        }
                    } elseif (isset($res['onload'][0])) {
                        $onload = $res['onload'][0];
                        //                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_identify_onload_' . time() . '.html', $onload);
                        if (\preg_match('/window.location.href="([^"]+)"/', $onload, $matches)) {
                            $swapData['urls'][] = \strtr($matches[1], ['\\/' => '/']);
                            $swapData['urlnum'] = 0;
                        } elseif ($swapData['iteration'] > 5) {
                            $error = 'Некорректный ответ сервиса';
                            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_identify_onload_err_'.\time().'.html', $onload);
                        }
                    } elseif (isset($res['jsmods']['require'][0][0]) && 'ServerRedirect' == $res['jsmods']['require'][0][0]) {
                        $redirect = $res['jsmods']['require'][0][3][0];
                        //                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_identify_redirect_' . time() . '.html', $redirect);
                        if (\preg_match('/\\/recover\\/initiate/', $redirect)) {
                            $swapData['urls'][] = $redirect;
                            $swapData['urlnum'] = 0;
                        } elseif ($swapData['iteration'] > 5) {
                            $error = 'Некорректный ответ сервиса';
                            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_identify_redirect_err_'.\time().'.html', $onload);
                        }
                    } elseif (isset($res['errorSummary']) && $res['errorSummary']) {
                        if (\strpos($res['errorSummary'], 'не доступен')) {
                            $resultData = new ResultDataList();
                            $data['result'] = new ResultDataField('string', 'Result', 'Найденный аккаунт недоступен', 'Результат', 'Результат');
                            $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND_UNAVAILABLE', 'Код результата', 'Код результата');
                            $resultData->addResult($data);
                            $rContext->setResultData($resultData);
                            $rContext->setFinished();
                            $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                            $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                        } elseif (\strpos($res['errorSummary'], 'заблокированы')) {
                            //                            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_identify_locked_' . time() . '.html', curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                            if (isset($swapData['session'])) {
                                $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='locked' WHERE id=".$swapData['session']->id);
                                //                                $mysqli->query("UPDATE session SET proxyid=NULL WHERE sourceid=31 AND proxyid=" . $swapData['session']->proxyid . " AND id<>" . $swapData['session']->id . " ORDER BY lasttime LIMIT 3");
                                $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                                unset($swapData['session']);
                            }
                        } else {
                            $error = $res['errorSummary'];
                        }
                    } elseif (\strpos($content, 'something went wrong')) {
                        $resultData = new ResultDataList();
                        $data['result'] = new ResultDataField('string', 'Result', 'Найденный аккаунт недоступен из-за ошибки', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND_ERROR', 'Код результата', 'Код результата');
                        $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                        $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                    } elseif (!$content) {
                        if (isset($swapData['session'])) {
                            $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                            unset($swapData['session']);
                        }
                    } elseif ($swapData['iteration'] > 5) {
                        $error = 'Некорректный ответ сервиса';
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_identify_err_'.\time().'.html', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                    }
                } elseif (\strpos($content, 'заблокированы') && $swapData['iteration'] % 5 == 0) {
                    //                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_recover_locked_' . time() . '.html', curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                    unset($swapData['urls']);
                    if (isset($swapData['session'])) {
                        $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='locked' WHERE id=".$swapData['session']->id);
                        //                        $mysqli->query("UPDATE session SET proxyid=NULL WHERE sourceid=31 AND proxyid=" . $swapData['session']->proxyid . " AND id<>" . $swapData['session']->id . " ORDER BY lasttime LIMIT 3");
                        $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                        unset($swapData['session']);
                    }
                } elseif (\strpos($content, 'не может быть обработан')) {
                    //                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_recover_impossible_' . time() . '.html', curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                    unset($swapData['urls']);
                    if (isset($swapData['session'])) {
                        $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='impossible' WHERE id=".$swapData['session']->id);
                        $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                        unset($swapData['session']);
                    }
                } else {
                    //                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_recover_' . time() . '.html', curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                    $resultData = isset($swapData['result']) ? $swapData['result'] : new ResultDataList();
                    if (\preg_match('/сброса пароля\\?(.*?)Пользователь Facebook/sim', $content, $dataPart)) {
                        $data = [];
                        if (\preg_match('/<div[^>]*>([^<]+)<\\/div><div[^>]*>$/sim', $dataPart[1], $matches)) {
                            $name = \html_entity_decode($matches[1]);
                            if (isset($initData['phone']) && $name != '+'.$initData['phone'] || isset($initData['email']) && $name != $initData['email']) {
                                $data['name'] = new ResultDataField('string', 'Name', \strtr($name, ['...' => '.']), 'Имя', 'Имя');
                            }
                        }
                        if (\preg_match('/img" src="([^\\&]+)\\&amp;square_px/', $dataPart[1], $matches)) {
                            //                            $data['photo'] = new ResultDataField('image','Photo', html_entity_decode($matches[1]).'&square_px=1024', 'Фото', 'Фото');
                        }
                        if (\preg_match_all('/Отправить код по SMS<\\/div><div[^>]*><div[^>]*>([^<]+)/', $dataPart[1], $matches)) {
                            foreach ($matches[1] as $i => $value) {
                                $data['phone'.$i] = new ResultDataField('string', 'Phone', \html_entity_decode($value), 'Телефон', 'Телефон');
                            }
                        }
                        if (\preg_match('/Отправить код на эл. адрес<\\/div><div[^>]*><div[^>]*>(.*?)<\\/div><\\/div><\\/div>/', $dataPart[1], $matches)) {
                            $matches[1] = \explode('</div><div>', $matches[1]);
                            foreach ($matches[1] as $i => $value) {
                                $data['email'.$i] = new ResultDataField('string', 'Email', \html_entity_decode($value), 'Email', 'Email');
                            }
                        }
                    }
                    $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                    $resultData->addResult($data);
                    if (++$swapData['urlnum'] < \count($swapData['urls'])) {
                        $swapData['result'] = $resultData;
                    } else {
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                        $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                    }
                }
            } elseif (!isset($swapData['path'])) {
                //                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_search_'.time().'.html',$content);
                if (\preg_match('/id="empty_result_error">/', $content)) {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                } elseif (\preg_match('/id="BrowseResultsContainer">([^>]+>)(.*?)<div id="browse_end_of_results_footer"/sim', $content, $result) || \preg_match('/id="BrowseResultsContainer">([^>]+>)(.*?) data-testid="paginated_results_pagelet"/sim', $content, $result)) {
                    $resultData = new ResultDataList();
                    $dataParts = \preg_split('/'.$result[1].'/', $result[2]);
                    //                    if (preg_match_all('/'.$result[1].'(.*?)<iframe /',$content,$dataParts))
                    if (\count($dataParts) >= 5) {
                        $error = 'Найдено слишком много совпадений. Попробуйте указать в запросе место учебы, работы или жительства.';
                        if (0 == $rContext->getLevel()) {
                            $rContext->setError($error);
                        }
                        $rContext->setFinished();

                        return true;
                    }
                    foreach ($dataParts as $dataPart) {
                        $data = [];
                        //                        if (preg_match("/<a class=\"[^\"]+\" href=\"(https:\/\/www.facebook.com\/[^\"]+)\" data-testid=\"serp_result_link[^\"]+\"><span>([^<]+)<\/span><\/a>/",$dataPart,$matches)) {
                        if (\preg_match('/<a class="[^"]+" href="(https:\\/\\/www.facebook.com\\/[^"]+)"[^>]*><span>([^<]+)<\\/span><\\/a>/', $dataPart, $matches)) {
                            $data['name'] = new ResultDataField('string', 'Name', \html_entity_decode($matches[2]), 'Имя', 'Имя');
                            $path = \html_entity_decode($matches[1]);
                            if (\strpos($path, '?') && !\strpos($path, '/profile.php?')) {
                                $path = \substr($path, 0, \strpos($path, '?'));
                            }
                            if (\strpos($path, '&') && \strpos($path, '/profile.php?')) {
                                $path = \substr($path, 0, \strpos($path, '&'));
                            }
                            $data['profile'] = new ResultDataField('url'.(\count($dataParts) <= 5 ? ':recursive' : ''), 'Profile', $path, 'Страница в Facebook', 'Страница в Facebook');
                        }
                        if (\preg_match('/<img class="[^\\s]+ img" src="([^"]+)/', $dataPart, $matches)) {
                            $data['photo'] = new ResultDataField('image', 'Photo', \html_entity_decode($matches[1]), 'Фото', 'Фото');
                        }
                        if (\preg_match('/data-profileid="([\\d]+)"/', $dataPart, $matches)) {
                            $data['uid'] = new ResultDataField('string', 'UserID', $matches[1], 'ID', 'ID');
                        }
                        //                        $div = preg_split('/<div>/',$dataPart);
                        //                        $lastdiv = $div[sizeof($div)-1];
                        $lines = \preg_split('/<\\/div><\\/div>/', $dataPart);
                        \array_shift($lines);
                        \array_shift($lines);
                        \array_pop($lines);
                        foreach ($lines as $line) {
                            $text = \trim(\str_replace('&#039;', "'", \html_entity_decode(\strip_tags($line))));
                            if (\preg_match('/^Учился в (.*?)$/', $text, $matches) || \preg_match('/^Училась в (.*?)$/', $text, $matches) || \preg_match('/^Учил[а]* .*? в (.*?)$/', $text, $matches) || \preg_match('/^Изучал[\\^s]* .*? в (.*?)$/', $text, $matches)) {
                                $matches = \explode("'", $matches[1]);
                                $data['education'] = new ResultDataField('string', 'education', $matches[0], 'Место учёбы', 'Место учёбы');
                                if (2 == \count($matches)) {
                                    $data['educationyear'] = new ResultDataField('string', 'educationyear', $matches[1], 'Закончил учиться в', 'Закончил учиться в');
                                }
                            } elseif (\preg_match('/^Жил[а]* в (.*?)$/', $text, $matches) || \preg_match('/^Живет в (.*?)$/', $text, $matches) || \preg_match('/^Купил.*? в (.*?)$/', $text, $matches)) {
                                $data['living'] = new ResultDataField('string', 'living', $matches[1], 'Место жительства', 'Место жительства');
                            } elseif (\preg_match('/^Из (.*?)$/', $text, $matches)) {
                                $data['birthplace'] = new ResultDataField('string', 'birthplace', $matches[1], 'Место рождения', 'Место рождения');
                            } elseif (\preg_match('/^Работал[а]* в компании (.*?)$/', $text, $matches) || \preg_match('/^Worked at компании (.*?)$/', $text, $matches)) {
                                if (\preg_match('/^«(.*?)»$/', $matches[1], $matches2)) {
                                    $matches[1] = $matches2[1];
                                }
                                $data['oldjob'] = new ResultDataField('string', 'oldjob', $matches[1], 'Прошлое место работы', 'Прошлое место работы');
                                if (\preg_match('/ в <a href="(https:\\/\\/www.facebook.com\\/[^"]+)/', $line, $matches) || \preg_match('/ at <a href="(https:\\/\\/www.facebook.com\\/[^"]+)/', $line, $matches)) {
                                    $path = \html_entity_decode($matches[1]);
                                    if (\strpos($path, '?') && !\strpos($path, '/profile.php?')) {
                                        $path = \substr($path, 0, \strpos($path, '?'));
                                    }
                                    if (\strpos($path, '&') && \strpos($path, '/profile.php?')) {
                                        $path = \substr($path, 0, \strpos($path, '&'));
                                    }
                                    $data['oldjobprofile'] = new ResultDataField('url', 'OldJobProfile', $path, 'Страница прошлого места работы', 'Страница прошлого места работы');
                                }
                            } elseif (\preg_match('/^(.*?) в (.*?)$/', $text, $matches)) {
                                $data['position'] = new ResultDataField('string', 'position', $matches[1], 'Должность', 'Должность');
                                $data['job'] = new ResultDataField('string', 'job', $matches[2], 'Место работы', 'Место работы');
                                if (\preg_match('/ в <a href="(https:\\/\\/www.facebook.com\\/[^"]+)/', $line, $matches)) {
                                    $path = \html_entity_decode($matches[1]);
                                    if (\strpos($path, '?') && !\strpos($path, '/profile.php?')) {
                                        $path = \substr($path, 0, \strpos($path, '?'));
                                    }
                                    if (\strpos($path, '&') && \strpos($path, '/profile.php?')) {
                                        $path = \substr($path, 0, \strpos($path, '&'));
                                    }
                                    $data['jobprofile'] = new ResultDataField('url', 'JobProfile', $path, 'Страница места работы', 'Страница места работы');
                                }
                            } elseif ($text && 'Загрузка других результатов...' != $text) {
                                $data['info'] = new ResultDataField('string', 'info', $text, 'Информация', 'Информация');
                            }
                        }
                        if (\count($data)) {
                            $resultData->addResult($data);
                        }
                    }
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                } elseif ($content) {
                    if (!\preg_match('/facebook.com\\/settings\\?/', $content)) {
                        if (isset($swapData['session'])) {
                            $mysqli->executeStatement('UPDATE session SET endtime=now(),sessionstatusid=3 WHERE id='.$swapData['session']->id);
                            unset($swapData['session']);
                        }
                    } else {
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_err_'.\time().'.html', $content);
                        $error = 'Невозможно обработать ответ';
                    }
                }
            } elseif (!isset($swapData['about'])) {
                /*
                                $endurl = curl_getinfo($rContext->getCurlHandler(), CURLINFO_EFFECTIVE_URL);
                                if (substr($endurl,strpos($endurl,'facebook.com'))!=substr($swapData['path'],strpos($swapData['path'],'facebook.com'))) {
                                    $data['profile'] = new ResultDataField('url:recursive','Profile', $endurl, 'Страница в Facebook', 'Страница в Facebook');
                                    $resultData = new ResultDataList();
                                    $resultData->addResult($data);
                                    $rContext->setResultData($resultData);
                                    $rContext->setFinished();
                                }
                */
                //                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_'.time().'.html',$content);
                $data = isset($swapData['data']) ? $swapData['data'] : [];
                /*
                                if(preg_match("/<a href=\"(\/photo.php\?fbid=[^\"]+)\" id=\"/", $content, $matches)){
                                    $swapData['photo_path'] = 'https://m.facebook.com'.$matches[1];
                                }
                */
                if (\preg_match('/<a href="\\/photo.php\\?fbid=([^\\&]+)[^"]+" id="/', $content, $matches)) {
                    $swapData['photo_path'] = 'https://m.facebook.com/photo/view_full_size/?fbid='.$matches[1];
                }
                if (\preg_match('/<strong class="[a-z]+">([^<]+)<\\/strong>/', $content, $matches)) {
                    $data['name'] = new ResultDataField('string', 'Name', $matches[1], 'Имя', 'Имя');
                }
                if (\preg_match('/Ye1TQi63ARL.png/', $content)) {
                    $data['presence'] = new ResultDataField('string', 'presence', 'mobile', 'Присутствие', 'Присутствие');
                }
                if (\preg_match('/\\(\\((\\d+)\\)\\)/', $content, $matches)) {
                    $data['friends'] = new ResultDataField('string', 'friends', $matches[1], 'Друзья', 'Друзья');
                }
                if (\preg_match('/<div class="[a-z]{2} [a-z]{2} [a-z]{2} [a-z]{2}">([^<]+)/', $content, $matches)) {
                    $data['aboutself'] = new ResultDataField('string', 'AboutSelf', $matches[1], 'О себе', 'О себе');
                }
                /*
                                if(preg_match_all("/<span class=\"[a-z]{2} [a-z]{2}\">([^<]+)(.*?)<\/span>/", $content, $matches)){
                                    foreach($matches[1] as $i => $line){
                                        if ((strpos($line,'Учил')!==false) || (strpos($line,'Изучал')!==false)){
                                            $text = str_replace("&#039;", "'",html_entity_decode($line.preg_replace("/<([^<>])+>/", "", $matches[2][$i])));
                                            $data['education'] = new ResultDataField('string', 'education', $text, 'Место учёбы', 'Место учёбы');
                                        } elseif ((strpos($line,'Живет')!==false) || (strpos($line,'Жил')!==false)){
                                            $text = str_replace("&#039;", "'",html_entity_decode($line.preg_replace("/<([^<>])+>/", "", $matches[2][$i])));
                                            $data['living'] = new ResultDataField('string', 'living', $text, 'Место жительства', 'Место жительства');
                                        } else {
                                            $text = str_replace("&#039;", "'",html_entity_decode($line.preg_replace("/<([^<>])+>/", "", $matches[2][$i])));
                                            $data['job'] = new ResultDataField('string', 'job', $text, 'Место работы', 'Место работы');
                                        }
                                    }
                                }
                */
                $content = \substr($content, 0, \strpos($content, '<a name="footer-actions-basic">'));
                $tmparray = \explode('<tr><td class="t ', $content);
                //                        $tmparray = explode('data-sigil="profile-card"><div', $content);
                $counter = 0;
                foreach ($tmparray as $key => $val) {
                    $val = \substr($val, \strpos($val, '>') + 1);
                    $val = \substr($val, \strpos($val, '>') + 1);
                    //                                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/faceb'.$key.'_'.time().'.html',$val);
                    if (0 == $key) {
                        /*
                                                 if(preg_match("/<img src=\"([^\"]+)\" .*? class=\"presence_icon/", $val, $matches)){
                                                     $data['presence'] = new ResultDataField('string', 'presence', 'mobile', 'Присутствие', 'Присутствие');
                                             }
                                                 if(preg_match("/\(\((\d+)\)\)/", $val, $matches)){
                                                     $data['friends'] = new ResultDataField('string', 'friends', $matches[1], 'Друзья', 'Друзья');
                                             }
                                                                 if(preg_match("/<a href=\"(\/photo.php[^\"&]+)/", $val, $matches)){
                                                     $data['fullphoto_url'] = new ResultDataField('url', 'FullPhotoLink', 'https://www.facebook.com/'.$matches[1], 'Полноразмерное фото', 'Полноразмерное фото');
                                                                 }
                                                                 if(preg_match("/<img src=\"([^\"]+)\" width=\"72\" height=\"72\" class=\"profpic img\"/", $val, $matches)){
                                                                         $data['photo'] = new ResultDataField('image','Photo', html_entity_decode($matches[1]), 'Фото страницы', 'Фото страницы');
                                                                 }
                        */
                    }
                    if ($key > 0 && \strpos($val, '<') > 0) {
                        $title = \substr($val, 0, \strpos($val, '<'));
                        $fortitle = \substr($val, \strpos($val, '<'));
                        $fortitle = \preg_replace('/<span>([^<]+)<\\/span><wbr \\/><span class="word_break"><\\/span>/', '$1', $fortitle);
                        if (0 === \strpos($title, 'О пользователе')) {
                            $title = 'О пользователе';
                        }
                        //                                        if(strpos($fortitle, '<div class="c">') !== false){
                        //                                                 $$fortitle = str_replace('<div class="c">', '; ', $fortitle);
                        //                                        }
                        while (\preg_match('/(<div class=\\"[^\\"]+\\" id=\\"u_0_[^\\"]+\\">)/', $fortitle, $div)) {
                            $fortitle = \str_replace($div[1], '; ', $fortitle);
                        }
                        while (\preg_match('/(<table cellspacing=\\"[^\\"]+\\" cellpadding=\\"[^\\"]+\\" class=\\"[^\\"]+\\">)/', $fortitle, $div)) {
                            $fortitle = \str_replace($div[1], '; ', $fortitle);
                        }
                        $fortitle = \str_replace('</i>', '; ', $fortitle);
                        $fortitle = \str_replace('<span>', '; ', $fortitle);
                        $fortitle = \str_replace('<div class="clear">', '; ', $fortitle);
                        $fortitle = \preg_replace('/<a [^>]+>/', '', $fortitle);
                        $fortitle = \preg_replace('/<[^>]+>/', '|', $fortitle);
                        $fortitle = \preg_replace('/\\|+/', ', ', $fortitle);
                        $fortitle = \preg_replace('/^,+\\;/', ' ', $fortitle);
                        $fortitle = \html_entity_decode(\trim($fortitle));
                        $fortitle = \str_replace('&#039;', "'", $fortitle);
                        $fortitle = \str_replace('; ,', ';', $fortitle);
                        $fortitle = \str_replace(', ;', ";\n", $fortitle);
                        if ($fortitle) {
                            if (';' == $fortitle[\strlen($fortitle) - 1] || ',' == $fortitle[\strlen($fortitle) - 1]) {
                                $fortitle = \trim(\substr($fortitle, 0, \strlen($fortitle) - 1));
                            }
                        }
                        if ($fortitle) {
                            if (';' == $fortitle[0] || ',' == $fortitle[0]) {
                                $fortitle = \trim(\substr($fortitle, 1));
                            }
                        }
                        if (isset($this->names[$title])) {
                            $field = $this->names[$title];
                            if (isset($field[3]) && 'block' == $field[3]) {
                                $itmparr = \explode(';', $fortitle);
                                $icounter = 0;
                                foreach ($itmparr as $slplace) {
                                    if ($comma = \strpos($slplace, ',')) {
                                        $val2 = \trim(\substr($slplace, $comma + 1));
                                        if ($val2 && 'Информация скрыта' != $val2 && 'Загрузка...' != $val2) {
                                            $title2 = \trim(\substr($slplace, 0, $comma));
                                            if (0 === \strpos($title2, 'Переехал')) {
                                                $title2 = 'Переехал';
                                            }
                                            if (\strpos($title2, '(') > 0) {
                                                $title2 = \trim(\substr($title2, 0, \strpos($title2, '(')));
                                            }
                                            if (isset($this->names[$title2])) {
                                                $field2 = $this->names[$title2];
                                                if (isset($field2[3]) && 'phone' == $field2[3] && ($val2 = \preg_replace('/\\D/', '', $val2))) {
                                                    if (11 == \strlen($val2) && '8' == \substr($val2, 0, 1)) {
                                                        $val2 = \substr($val2, 1);
                                                    }
                                                    if (10 == \strlen($val2)) {
                                                        $val2 = '7'.$val2;
                                                    }
                                                }
                                                if (isset($field2[3]) && ('url' == $field2[3] || 'url:recursive' == $field2[3]) && isset($field2[4]) && false === \strpos($val2, $field2[4])) {
                                                    $val2 = $field2[4].$val2;
                                                }
                                                $data[$field2[0]] = new ResultDataField(isset($field2[3]) ? $field2[3] : 'string', $field2[0], $val2, $field2[1], $field2[2]);
                                            } else {
                                                ++$icounter;
                                                $data[$field[0].$icounter] = new ResultDataField('string', $field[0].$icounter, $val2, $title2, $title2);
                                                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fields/facebook_'.\time().'_'.$title2, $title2."\n".$val2);
                                            }
                                        }
                                    }
                                }
                            } else {
                                if (isset($field[3]) && ('url' == $field[3] || 'url:recursive' == $field2[3]) && isset($field[4]) && false === \strpos($fortitle, $field[4])) {
                                    $fortitle = $field[4].$fortitle;
                                }
                                $data[$field[0]] = new ResultDataField(isset($field[3]) && 'block' != $field[3] ? $field[3] : 'string', $field[0], $fortitle, $field[1], $field[2]);
                            }
                        } else {
                            ++$counter;
                            $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $fortitle, $title, $title);
                            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fields/facebook_'.\time().'_'.$title, $title."\n".$fortitle);
                        }
                    }
                }
                if (!isset($swapData['photo_path'])) {
                    $resultData = new ResultDataList();
                    if (\count($data)) {
                        $resultData->addResult($data);
                    }
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                }
                $swapData['about'] = true;
                $swapData['data'] = $data;
            } elseif (isset($swapData['photo_path'])) {
                $data = $swapData['data'];
                //                $start = strpos($content,'{');
                //                $content = trim(substr($content,$start,strlen($content)-$start+1));
                //                $ares = json_decode($content, true);
                //                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_photo_'.time().'.html',$content);
                if (\preg_match('/url=([^"]+)/', $content, $matches)) {
                    $data['photo'] = new ResultDataField('image', 'Photo', \html_entity_decode($matches[1]), 'Фото профиля', 'Фото профиля');
                }
                /*
                                if (isset($ares['jsmods']['require'])) {
                                    $icounter = 0;
                                    foreach ($ares['jsmods']['require'] as $elem) {
                                        if (($elem[0]='PhotoShowlift') && ($elem[1]='storeFromData') && isset($elem[3][0]['image'])) {
                                            foreach($elem[3][0]['image'] as $id => $val) {
                                                if (is_array($val)) {
                                                    if($icounter==0)
                                                        $data['photo'] = new ResultDataField('image','Photo', $val['url'], 'Фото профиля', 'Фото профиля');
                                                    else
                                                        $data['photo'.$icounter] = new ResultDataField('image','Photo'.$icounter, $val['url'], 'Фото профиля '.$icounter, 'Фото профиля '.$icounter);
                                                    $icounter++;
                                                }
                                            }
                                        }
                                        if (($elem[0]='PhotoCentralUpdates') && ($elem[1]='handleUpdate') && isset($elem[3][1]['query_results']['edges']) && ($elem[3][1]['set_id']=='profile_picture')) {
                                            foreach($elem[3][1]['query_results']['edges'] as $id => $val) {
                                                if (isset($val['node']['image1']['uri'])) {
                                                    $data['photo'] = new ResultDataField('image','Photo', $val['node']['image1']['uri'], 'Фото профиля', 'Фото профиля');
                                                }
                                            }
                                        }
                                    }
                                }
                */
                unset($swapData['photo_path']);
                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
            } else {
                //                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/face_about_'.time().'.html',$content);
                $data = $swapData['data'];
                $content = \substr($content, 0, \strpos($content, '<a name="footer-actions-basic">'));
                $tmparray = \explode('<tr><td class="s ', $content);
                //                        $tmparray = explode('data-sigil="profile-card"><div', $content);
                $counter = 0;
                foreach ($tmparray as $key => $val) {
                    $val = \substr($val, \strpos($val, '>') + 1);
                    $val = \substr($val, \strpos($val, '>') + 1);
                    //                                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/facebook/faceb'.$key.'_'.time().'.html',$val);
                    if (0 == $key) {
                        /*
                                                 if(preg_match("/<img src=\"([^\"]+)\" .*? class=\"presence_icon/", $val, $matches)){
                                                     $data['presence'] = new ResultDataField('string', 'presence', 'mobile', 'Присутствие', 'Присутствие');
                                             }
                                                 if(preg_match("/\(\((\d+)\)\)/", $val, $matches)){
                                                     $data['friends'] = new ResultDataField('string', 'friends', $matches[1], 'Друзья', 'Друзья');
                                             }
                                                                 if(preg_match("/<a href=\"(\/photo.php[^\"&]+)/", $val, $matches)){
                                                     $data['fullphoto_url'] = new ResultDataField('url', 'FullPhotoLink', 'https://www.facebook.com/'.$matches[1], 'Полноразмерное фото', 'Полноразмерное фото');
                                                                 }
                                                                 if(preg_match("/<img src=\"([^\"]+)\" width=\"72\" height=\"72\" class=\"profpic img\"/", $val, $matches)){
                                                                         $data['photo'] = new ResultDataField('image','Photo', html_entity_decode($matches[1]), 'Фото страницы', 'Фото страницы');
                                                                 }
                        */
                    }
                    if ($key > 0 && \strpos($val, '<') > 0) {
                        $title = \substr($val, 0, \strpos($val, '<'));
                        $fortitle = \substr($val, \strpos($val, '<'));
                        $fortitle = \preg_replace('/<span>([^<]+)<\\/span><wbr \\/><span class="word_break"><\\/span>/', '$1', $fortitle);
                        if (0 === \strpos($title, 'О пользователе')) {
                            $title = 'О пользователе';
                        }
                        //                                        if(strpos($fortitle, '<div class="c">') !== false){
                        //                                                 $$fortitle = str_replace('<div class="c">', '; ', $fortitle);
                        //                                        }
                        while (\preg_match('/(<div class=\\"[^\\"]+\\" id=\\"u_0_[^\\"]+\\">)/', $fortitle, $div)) {
                            $fortitle = \str_replace($div[1], '; ', $fortitle);
                        }
                        while (\preg_match('/(<table cellspacing=\\"[^\\"]+\\" cellpadding=\\"[^\\"]+\\" class=\\"[^\\"]+\\">)/', $fortitle, $div)) {
                            $fortitle = \str_replace($div[1], '; ', $fortitle);
                        }
                        $fortitle = \str_replace('</i>', '; ', $fortitle);
                        $fortitle = \str_replace('<span>', '; ', $fortitle);
                        $fortitle = \str_replace('<div class="clear">', '; ', $fortitle);
                        $fortitle = \preg_replace('/<a [^>]+>/', '', $fortitle);
                        $fortitle = \preg_replace('/<[^>]+>/', '|', $fortitle);
                        $fortitle = \preg_replace('/\\|+/', ', ', $fortitle);
                        $fortitle = \preg_replace('/^,+\\;/', ' ', $fortitle);
                        $fortitle = \html_entity_decode(\trim($fortitle));
                        $fortitle = \str_replace('&#039;', "'", $fortitle);
                        $fortitle = \str_replace('; ,', ';', $fortitle);
                        $fortitle = \str_replace(', ;', ";\n", $fortitle);
                        if ($fortitle) {
                            if (';' == $fortitle[\strlen($fortitle) - 1] || ',' == $fortitle[\strlen($fortitle) - 1]) {
                                $fortitle = \trim(\substr($fortitle, 0, \strlen($fortitle) - 1));
                            }
                        }
                        if ($fortitle) {
                            if (';' == $fortitle[0] || ',' == $fortitle[0]) {
                                $fortitle = \trim(\substr($fortitle, 1));
                            }
                        }
                        if (isset($this->names[$title])) {
                            $field = $this->names[$title];
                            if (isset($field[3]) && 'block' == $field[3]) {
                                $itmparr = \explode(';', $fortitle);
                                $icounter = 0;
                                foreach ($itmparr as $slplace) {
                                    if ($comma = \strpos($slplace, ',')) {
                                        $val2 = \trim(\substr($slplace, $comma + 1));
                                        if ($val2 && 'Информация скрыта' != $val2 && 'Загрузка...' != $val2) {
                                            $title2 = \trim(\substr($slplace, 0, $comma));
                                            if (0 === \strpos($title2, 'Переехал')) {
                                                $title2 = 'Переехал';
                                            }
                                            if (\strpos($title2, '(') > 0) {
                                                $title2 = \trim(\substr($title2, 0, \strpos($title2, '(')));
                                            }
                                            if (isset($this->names[$title2])) {
                                                $field2 = $this->names[$title2];
                                                if (isset($field2[3]) && 'phone' == $field2[3] && ($val2 = \preg_replace('/\\D/', '', $val2))) {
                                                    if (11 == \strlen($val2) && '8' == \substr($val2, 0, 1)) {
                                                        $val2 = \substr($val2, 1);
                                                    }
                                                    if (10 == \strlen($val2)) {
                                                        $val2 = '7'.$val2;
                                                    }
                                                }
                                                if (isset($field2[3]) && ('url' == $field2[3] || 'url:recursive' == $field2[3]) && isset($field2[4]) && false === \strpos($val2, $field2[4])) {
                                                    $val2 = $field2[4].$val2;
                                                }
                                                $data[$field2[0]] = new ResultDataField(isset($field2[3]) ? $field2[3] : 'string', $field2[0], $val2, $field2[1], $field2[2]);
                                            } else {
                                                ++$icounter;
                                                $data[$field[0].$icounter] = new ResultDataField('string', $field[0].$icounter, $val2, $title2, $title2);
                                                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fields/facebook_'.\time().'_'.$title2, $title2."\n".$val2);
                                            }
                                        }
                                    }
                                }
                            } else {
                                if (isset($field[3]) && ('url' == $field[3] || 'url:recursive' == $field2[3]) && isset($field[4]) && false === \strpos($fortitle, $field[4])) {
                                    $fortitle = $field[4].$fortitle;
                                }
                                $data[$field[0]] = new ResultDataField(isset($field[3]) && 'block' != $field[3] ? $field[3] : 'string', $field[0], $fortitle, $field[1], $field[2]);
                            }
                        } else {
                            ++$counter;
                            $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $fortitle, $title, $title);
                            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fields/facebook_'.\time().'_'.$title, $title."\n".$fortitle);
                        }
                    }
                }
                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
            }
            $swapData['data'] = $data;
            $rContext->setSwapData($swapData);
        }
        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] >= 50) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }
        $rContext->setSleep(1);

        return true;
    }
}
