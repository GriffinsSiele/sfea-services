<?php

class InstagramPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Instagram';
    }

    public function getTitle($checktype = '')
    {
        $title = ['' => 'Поиск в Instagram', 'instagram_phone' => 'Instagram - проверка телефона на наличие пользователя', 'instagram_email' => 'Instagram - проверка email на наличие пользователя', 'instagram_url' => 'Instagram - профиль пользователя'];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в Instagram';
    }

    public function getSessionData(array $params)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $reqId = $params['_reqId'];
        $sessionData = null;
        $mysqli->executeStatement('UPDATE session s SET request_id='.$reqId.' WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=19 AND unix_timestamp(now())-unix_timestamp(lasttime)>10 ORDER BY lasttime limit 1');
        $result = $mysqli->executeQuery("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sourceid=19 AND request_id=".$reqId.' ORDER BY lasttime limit 1');
        if ($result) {
            $row = $result->fetchAssociative();
            if ($row) {
                $sessionData = new \stdClass();
                $sessionData->id = $row['id'];
                $sessionData->code = $row['captcha'];
                $sessionData->token = $row['token'];
                $sessionData->starttime = $row['starttime'];
                $sessionData->lasttime = $row['lasttime'];
                $sessionData->cookies = $row['cookies'];
                $sessionData->proxyid = $row['proxyid'];
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                $mysqli->executeStatement("UPDATE session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL WHERE id=".$sessionData->id);
                if (!$row['proxyid']) {
                    //                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE enabled=1 AND status=1 AND country='ru' AND (rotation>0 OR (SELECT COUNT(*) FROM session WHERE proxyid=proxy.id AND sourceid=48 AND sessionstatusid IN (1,2,6,7))<1) ORDER BY lasttime limit 1");
                    $result = $mysqli->executeQuery("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE status=1 AND country<>'ru' AND rotation>0 ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetchAssociative();
                        if ($row) {
                            $sessionData->proxyid = $row['proxyid'];
                            $sessionData->proxy = $row['proxy'];
                            $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                            //                            $mysqli->query("UPDATE proxy SET lasttime=now() WHERE id=".$row['proxyid']);
                            $mysqli->executeStatement('UPDATE session SET proxyid='.$row['proxyid'].' WHERE id='.$sessionData->id);
                        }
                    }
                }
                $mysqli->executeStatement('UPDATE proxy SET lasttime=now(),used=ifnull(used,0)+1 WHERE id='.$row['proxyid']);
                //                $mysqli->query("UPDATE session SET endtime=now(),sessionstatusid=3 WHERE used>=1 AND id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        if (!isset($initData['url']) && !isset($initData['phone']) && !isset($initData['email'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (ссылка, телефон или email)');

            return false;
        }
        if (isset($initData['phone'])) {
            //            if (strlen($initData['phone'])==10)
            //                $initData['phone']='7'.$initData['phone'];
            //            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            //                $initData['phone']='7'.substr($initData['phone'],1);
            $swapData['phone'] = $initData['phone'];
        }
        if (isset($initData['email'])) {
            $swapData['email'] = $initData['email'];
        }
        if (isset($initData['url'])) {
            if (false === \strpos($initData['url'], 'instagram.com/')) {
                $rContext->setFinished();

                return false;
            }
            $swapData['path'] = $initData['url'];
        }
        $rContext->setSwapData($swapData);
        /*
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
                return false;
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData($params);
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && $swapData['iteration'] >= 20) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }

                return false;
            }
            if ($swapData['iteration'] > 5 && \rand(0, 2)) {
                $astro = ['193.23.50.59:10451', '94.247.132.131:10127'];
                $swapData['session']->proxyid = 0;
                $swapData['session']->proxy = $astro[\rand(0, \count($astro) - 1)];
                $swapData['session']->proxy_auth = 'isphere:e6eac1';
            }
        }
        $rContext->setSwapData($swapData);
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        $site = 'https://www.instagram.com';
        if (isset($swapData['path'])) {
            $url = $swapData['path'];
        } else {
            $url = $site.'/accounts/login/ajax/';
            $post = ['username' => isset($initData['phone']) ? '+'.$initData['phone'] : $initData['email'], 'password' => ''];
            $header = [
                //                'Accept-Encoding: deflate',
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: '.$site.'/',
                'Origin: '.$site,
                'x-asbd-id: 198387',
                'x-csrftoken: '.$swapData['session']->token,
                'x-ig-app-id: 936619743392459',
                'x-ig-www-claim: hmac.AR01Dzt8b2ciNrasg-aSbVu9w7eE16KFgJHAtoxHAHNvXP2Z',
                'x-instagram-ajax: 5506009d214a',
                'x-requested-with: XMLHttpRequest',
            ];
        }
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, 1);
        \curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
        \curl_setopt($ch, \CURLOPT_ENCODING, '');
        \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
        if (isset($post)) {
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($post));
        }
        if (isset($header)) {
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
        }
        if ($swapData['session']->proxy) {
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
        $error = false;
        $curl_error = \curl_error($rContext->getCurlHandler());
        //        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        if (!$curl_error) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());
            if (!isset($swapData['path'])) {
                //                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/instagram/instagram_login_'.time().'.txt',$content);
                $res = \json_decode($content, true);
                if (isset($res['message']) && \strpos($res['message'], 'your password')) {
                    $res['user'] = false;
                }
                if (isset($res['user'])) {
                    $resultData = new ResultDataList();
                    if ($res['user']) {
                        if (isset($initData['phone'])) {
                            $data['phone'] = new ResultDataField('string', 'Phone', $initData['phone'], 'Телефон', 'Телефон');
                        } else {
                            $data['email'] = new ResultDataField('string', 'Email', $initData['email'], 'E-mail', 'E-mail');
                        }
                        $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                        $resultData->addResult($data);
                    }
                    $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } elseif (isset($res['errors']['error'][0])) {
                    $error = $res['errors']['error'][0];
                    if (\strpos($error, 'there was a problem') || \strpos($error, 'an error occured')) {
                        $error = false;
                        //                        $mysqli->query("UPDATE session SET endtime=now(),sessionstatusid=4,statuscode='error' WHERE id=" . $swapData['session']->id);
                        //                        $mysqli->query("UPDATE session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='error' WHERE id=" . $swapData['session']->id);
                        $mysqli->executeStatement('UPDATE session SET proxyid=NULL WHERE id='.$swapData['session']->id);
                        $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                        unset($swapData['session']);
                    }
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/instagram/instagram_login_err_'.\time().'.txt', $content);
                } elseif (isset($res['message']) && ('feedback_required' == $res['message'] || 'checkpoint_required' == $res['message'])) {
                    //                    $mysqli->query("UPDATE session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='spam' WHERE id=" . $swapData['session']->id);
                    $mysqli->executeStatement('UPDATE session SET proxyid=NULL WHERE id='.$swapData['session']->id);
                    $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                    unset($swapData['session']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/instagram/instagram_login_err_spam_'.\time().'.txt', $content);
                } elseif (isset($res['error_type']) && 'ip_block' == $res['error_type']) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='blocked' WHERE id=".$swapData['session']->id);
                    $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                    unset($swapData['session']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/instagram/instagram_login_err_ip_'.\time().'.txt', $content);
                } elseif (\strpos($content, 'before you try again')) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='wait' WHERE id=".$swapData['session']->id);
                    $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                    unset($swapData['session']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/instagram/instagram_login_err_wait_'.\time().'.txt', $content);
                } elseif (isset($res['message'])) {
                    $error = $res['message'];
                    $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/instagram/instagram_login_err_'.\time().'.txt', $content);
                } elseif (\strpos($content, 'something went wrong')) {
                    $mysqli->executeStatement("UPDATE session SET endtime=now(),sessionstatusid=4,statuscode='wrong' WHERE id=".$swapData['session']->id);
                    $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                    unset($swapData['session']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/instagram/instagram_login_err_'.\time().'.txt', $content);
                } else {
                    if (!$content || \strpos($content, 'page could not be loaded')) {
                        //                        $mysqli->query("UPDATE session SET endtime=now(),sessionstatusid=4,statuscode='notloaded' WHERE id=" . $swapData['session']->id);
                        $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='notloaded' WHERE id=".$swapData['session']->id);
                        unset($swapData['session']);
                    } else {
                        $error = 'Невозможно обработать ответ';
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/instagram/instagram_login_err_'.\time().'.txt', $content);
                    }
                }
            } else {
                //                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/instagram/instagram_'.time().'.html',$content);
                if (\preg_match('/window\\._sharedData = (.*?);<\\/script>/', $content, $matches)) {
                    $res = \json_decode($matches[1], true);
                    $data = [];
                    $resultData = new ResultDataList();
                    if (isset($res['entry_data']['ProfilePage'][0]['graphql']['user'])) {
                        $user = $res['entry_data']['ProfilePage'][0]['graphql']['user'];
                        if (isset($user['username'])) {
                            $data['link'] = new ResultDataField('url', 'Link', 'https://instagram.com/'.$user['username'], 'Ссылка на профиль', 'Ссылка на профиль');
                            $data['username'] = new ResultDataField('string', 'UserName', $user['username'], 'Имя пользователя', 'Имя пользователя');
                        }
                        if (isset($user['full_name'])) {
                            $data['name'] = new ResultDataField('string', 'Name', \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $user['full_name'])), 'Полное имя', 'Полное имя');
                        }
                        if (isset($user['biography'])) {
                            $data['about'] = new ResultDataField('string', 'About', \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $user['biography'])), 'О себе', 'О себе');
                        }
                        if (isset($user['external_url'])) {
                            $data['url'] = new ResultDataField('url:recursive', 'Website', $user['external_url'], 'Сайт', 'Сайт');
                        }
                        if (isset($user['followed_by']['count'])) {
                            $data['followers'] = new ResultDataField('string', 'Followers', $user['followed_by']['count'], 'Подписчиков', 'Подписчиков');
                        }
                        if (isset($user['follows']['count'])) {
                            $data['follows'] = new ResultDataField('string', 'Follows', $user['follows']['count'], 'Подписки', 'Подписки');
                        }
                        if (isset($user['media']['count'])) {
                            $data['posts'] = new ResultDataField('string', 'Posts', $user['media']['count'], 'Публикаций', 'Публикаций');
                        }
                        if (isset($user['is_private'])) {
                            $data['private'] = new ResultDataField('string', 'Private', $user['is_private'] ? 'да' : 'нет', 'Закрытый профиль', 'Закрытый профиль');
                        }
                        if (isset($user['profile_pic_url_hd'])) {
                            $data['image'] = new ResultDataField('image', 'Image', $user['profile_pic_url_hd'], 'Фото профиля', 'Фото профиля');
                        }
                        $resultData->addResult($data);
                    }
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                } elseif (\preg_match('/Page Not Found/', $content)) {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->executeStatement('UPDATE proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                } else {
                    $error = 'Некорректный ответ сервиса';
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/instagram/instagram_err_'.\time().'.html', $content);
                }
            }
        }
        $rContext->setSwapData($swapData);
        if (!$error && $swapData['iteration'] >= 30) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error && $swapData['iteration'] > 1) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }
        $rContext->setSleep(1);

        return true;
    }
}
