<?php

class InstagramPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Instagram';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск в Instagram',
            'instagram_phone' => 'Instagram - проверка телефона на наличие пользователя',
            'instagram_email' => 'Instagram - проверка email на наличие пользователя',
            'instagram_url' => 'Instagram - профиль пользователя',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];

//        return 'Поиск в Instagram';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=19 AND lasttime<DATE_SUB(now(), INTERVAL 10 SECOND) ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=19 AND request_id=".$reqId." ORDER BY lasttime limit 1");

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

                if (!$row->proxyid) {
//                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND country='ru' AND (rotation>0 OR (SELECT COUNT(*) FROM session WHERE proxyid=proxy.id AND sourceid=19 AND sessionstatusid IN (1,2,6,7))<1) ORDER BY lasttime limit 1");
                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 AND country<>'ru' AND rotation>0 AND id>0 AND (successtime>DATE_SUB(now(), INTERVAL 1 MINUTE) OR lasttime<DATE_SUB(now(), INTERVAL 1 MINUTE)) ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetch_object();
                        if ($sessionData->token && $row) {
                            $sessionData->proxyid = $row->proxyid;
                            $sessionData->proxy = $row->proxy;
                            $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

//                            $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
                            $mysqli->query("UPDATE isphere.session SET proxyid=".$row->proxyid." WHERE id=".$sessionData->id);
                        } else {
                            $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 AND enabled=1 AND country<>'ru' AND rotation=0 AND (successtime>DATE_SUB(now(), INTERVAL 1 MINUTE) OR lasttime<DATE_SUB(now(), INTERVAL 1 MINUTE)) ORDER BY lasttime limit 1");
                            if ($result) {
                                $row = $result->fetch_object();
                                if ($row) {
                                    $sessionData->proxyid = $row->proxyid;
                                    $sessionData->proxy = $row->proxy;
                                    $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

//                                    $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
                                }
                            }
                        }
                    }
                }

                if ($sessionData->proxyid) $mysqli->query("UPDATE isphere.proxy SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->proxyid);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        global $proxphere;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['url']) && !isset($initData['phone']) && !isset($initData['email'])) {
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
            if (strpos($initData['url'],'instagram.com/')===false) {
                $rContext->setFinished();
                return false;
            }
            $swapData['path'] = $initData['url'];
        }
        $rContext->setSwapData($swapData);
/*
        if (isset($initData['phone'])) {
            $rContext->setFinished();
            $rContext->setError('Сервис временно недоступен');
            return false;
        }
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            if(!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration']>=30)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }
                return false;
            }
        }
        $rContext->setSwapData($swapData);

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $site = 'https://www.instagram.com';
        if(isset($swapData['path'])) {
            $url = $swapData['path'];
        } elseif (!$swapData['session']->token) {
            $url = $site.'/accounts/login/';
        } else {
            if (isset($initData['phone'])) {
                $url = $site.'/api/v1/web/accounts/account_recovery_send_ajax/';
                $post = array();
                if (isset($initData['phone']))
                    $post['email_or_username'] = $initData['phone'];
                elseif (isset($initData['email']))
                    $post['email_or_username'] = $initData['email'];
            } elseif (isset($initData['email'])) {
                $url = $site.'/api/v1/web/accounts/web_create_ajax/attempt/';
                $post = array(
                    'first_name' => '',
                    'username' => '',
                    'opt_into_one_tap' => false,
                );
                if (isset($initData['phone']))
                    $post['phone_number'] = $initData['phone'];
                elseif (isset($initData['email']))
                    $post['email'] = $initData['email'];
            } else {
                $url = $site.'/accounts/login/ajax/';
                $post = array(
                    'username' => isset($initData['phone']) ? '+'.$initData['phone'] : $initData['email'],
                    'password' => '',
//                    'queryParams' => array(),
//                    'optIntoOneTap' => false,
                );
            }
            $header = array(
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
            );
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
        if(isset($post)){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if(isset($header)){
            if ($proxphere) $header[] = 'X-Sphere-Proxy-Spec-Id: '.$swapData['session']->proxyid;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
//        curl_setopt($ch, CURLOPT_HEADER, true);
//        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        if ($swapData['session']->proxy) {
            if ($proxphere) {
                curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
                curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
                curl_setopt($ch,CURLOPT_PROXYTYPE,CURLPROXY_SOCKS5_HOSTNAME);
                curl_setopt($ch,CURLOPT_PROXY, $proxphere);
            } else {
                curl_setopt($ch,CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt($ch,CURLOPT_PROXY, $swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth);
                    curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY);
                }
            }
        }
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $error = false;
        $curl_error = false; //curl_error($rContext->getCurlHandler());
//        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;

        if(!$curl_error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());

            if (!$swapData['session']->token) {
                file_put_contents('./logs/instagram/instagram_token_'.time().'.txt',curl_error($rContext->getCurlHandler())."\r\n".curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                if (preg_match("/\"csrf_token\":\"([^\"]+)\"/",$content,$matches)) {
                    $swapData['session']->token = $matches[1];
                    $cookies = str_cookies($swapData['session']->cookies);
                    foreach (curl_getinfo($rContext->getCurlHandler(),CURLINFO_COOKIELIST) as $cookie) {
//                        print 'Response cookie '.$cookie."\n";
                        $arr = explode("	",$cookie);
                        if (!isset($cookies[$arr[5]]) || $cookies[$arr[5]]!=$arr[6]) {
                            $cookies[$arr[5]] = $arr[6];
//                            print 'New cookie '.$arr[5].' = '.$arr[6]."\n";
                        }
                    }
                    $swapData['session']->cookies = cookies_str($cookies);
                    $rContext->setSwapData($swapData);
//                    file_put_contents('./logs/instagram/instagram_'.time().'.cookies',$swapData['session']->cookies);
                    $mysqli->query("UPDATE session SET used=0,statuscode='new',token='{$swapData['session']->token}',cookies='{$swapData['session']->cookies}' WHERE id=" . $swapData['session']->id);
                }
                unset($swapData['session']);
            } elseif (!isset($swapData['path'])) {
                file_put_contents('./logs/instagram/instagram_login_'.time().'.txt',curl_error($rContext->getCurlHandler())."\r\n".curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                $start = strpos($content,"\n{");
                $content = trim(substr($content,$start,strlen($content)-$start+1));
                $res = json_decode($content, true);

                $notified = array();

                if (isset($res['errors']['email'][0]['message']) && strpos($res['errors']['email'][0]['message'],' using '))
                    $res['user'] = true;
                if (isset($res['errors']['email'][0]['message']) && strpos($res['errors']['email'][0]['message'],' valid '))
                    $res['user'] = false;
                if (isset($res['errors']['username'][0]['message']) && !isset($res['errors']['email'][0]['message']))
                    $res['user'] = false;
                if (isset($res['message']) && !is_array($res['message']) && strpos($res['message'],'your password'))
                    $res['user'] = false;
//                if (isset($res['message']) && $res['message']=='checkpoint_required')
                if (strpos($content,'checkpoint_required'))
                    $res['user'] = true;
                if (isset($res['message'][0]) && strpos($res['message'][0],' link '))
                    $res['user'] = true;
                if (isset($res['message'][0]) && strpos($res['message'][0],' not active'))
                    $res['user'] = true;
                if (isset($res['message']) && !is_array($res['message']) && $res['message']=='No users found')
                    $res['user'] = false;
                if (isset($res['message']) && !is_array($res['message']) && strpos($res['message'],' security '))
                    $res['user'] = true;
                if (isset($res['message']) && !is_array($res['message']) && strpos($res['message'],' again '))
                    $res['user'] = true;
                if (isset($res['title']) && $res['title']=='SMS Sent') {
                    $res['user'] = true;
                    $notified[] = 'sms';
                    if (isset($res['contact_point']))
                        $res['phone'] = $res['contact_point'];
                }
                if (isset($res['title']) && $res['title']=='Email Sent') {
                    $res['user'] = true;
                    $notified[] = 'email';
                    if (isset($res['contact_point']))
                        $res['email'] = $res['contact_point'];
                }

                if (isset($res['user'])) {
                    $resultData = new ResultDataList();
                    if ($res['user']) {
                        if (isset($initData['phone'])) {
                            $data['phone'] = new ResultDataField('string','Phone',$initData['phone'],'Телефон','Телефон');
                        } elseif (isset($res['phone'])) {
                            $data['phone'] = new ResultDataField('string','Phone',$res['phone'],'Телефон','Телефон');
                        }
                        if (isset($initData['email'])) {
                            $data['email'] = new ResultDataField('string','Email',$initData['email'],'E-mail','E-mail');
                        } elseif (isset($res['email'])) {
                            $data['email'] = new ResultDataField('string','Email',$res['email'],'E-mail','E-mail');
                        }
                        if (sizeof($notified)==1) {
                            $data['notifiedby'] = new ResultDataField('string','NotifiedBy',$notified[0],'Отправлено уведомление','Отправлено уведомление');
                        }
                        $data['result'] = new ResultDataField('string','Result','Найден','Результат','Результат');
                        $data['result_code'] = new ResultDataField('string','ResultCode','FOUND','Код результата','Код результата');
                        $resultData->addResult($data);
                    }
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } elseif(isset($res['errors']['error'][0])) {
                    $error = $res['errors']['error'][0];
                    if (strpos($error,"there was a problem") || strpos($error,"an error occured")) {
                        $error = false;
//                        $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='error' WHERE id=" . $swapData['session']->id);
                        $mysqli->query("UPDATE isphere.session SET statuscode='error' WHERE id=" . $swapData['session']->id);
                        $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                        unset($swapData['session']);
                    } else {
                        file_put_contents('./logs/instagram/instagram_login_err_'.time().'.txt',$content);
                    }
                } elseif(isset($res['message']) && ($res['message']=='feedback_required' || $res['message']=='checkpoint_required')) {
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='spam' WHERE id=" . $swapData['session']->id);
//                    $mysqli->query("UPDATE isphere.session SET statuscode='spam' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                    unset($swapData['session']);
                    file_put_contents('./logs/instagram/instagram_login_err_spam_'.time().'.txt',$content);
                } elseif(isset($res['error_type']) && $res['error_type']=='ip_block') {
//                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='blocked' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET statuscode='blocked' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                    unset($swapData['session']);
                    file_put_contents('./logs/instagram/instagram_login_err_ip_'.time().'.txt',$content);
                } elseif(strpos($content,'before you try again')) {
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='wait' WHERE id=" . $swapData['session']->id);
//                    $mysqli->query("UPDATE isphere.session SET statuscode='wait' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                    unset($swapData['session']);
                    file_put_contents('./logs/instagram/instagram_login_err_wait_'.time().'.txt',$content);
                } elseif(isset($res['message'][0])) {
                    $error = $res['message'][0];
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                    file_put_contents('./logs/instagram/instagram_login_err_msg_'.time().'.txt',$content);
                } elseif(isset($res['message']) && !is_array($res['message'])) {
                    $error = $res['message'];
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                    file_put_contents('./logs/instagram/instagram_login_err_msg_'.time().'.txt',$content);
                } elseif(strpos($content,'something went wrong')) {
//                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='wrong' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET statuscode='wrong' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                    unset($swapData['session']);
                    file_put_contents('./logs/instagram/instagram_login_err_wrong_'.time().'.txt',$content);
                } elseif(strpos($content,'an error occurred')) {
//                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='oops' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET statuscode='oops' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                    unset($swapData['session']);
                    file_put_contents('./logs/instagram/instagram_login_err_oops_'.time().'.txt',$content);
                } elseif(strpos($content,'Not Allowed')) {
//                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='notallowed' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET statuscode='notallowed' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                    unset($swapData['session']);
                    file_put_contents('./logs/instagram/instagram_login_err_notallowed_'.time().'.txt',$content);
                } elseif(strpos($content,'Post "')) {
                    $mysqli->query("UPDATE isphere.session SET proxyid=NULL,unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='proxy' WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                    file_put_contents('./logs/instagram/instagram_login_err_proxy_'.time().'.txt',$content);
                } else {
                    if (!$content) {
//                        $mysqli->query("UPDATE isphere.session SET proxyid=NULL,unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='empty' WHERE id=" . $swapData['session']->id);
                        $mysqli->query("UPDATE isphere.session SET proxyid=NULL,statuscode='empty' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    } elseif (strpos($content,'page could not be loaded')) {
//                        $mysqli->query("UPDATE isphere.session SET proxyid=NULL,unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='notloaded' WHERE id=" . $swapData['session']->id);
                        $mysqli->query("UPDATE isphere.session SET proxyid=NULL,statuscode='notloaded' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                    } elseif ($swapData['iteration']>=3) {
                        $error = 'Невозможно обработать ответ';
                        file_put_contents('./logs/instagram/instagram_login_err_'.time().'.txt',$content);
                    }
                }
            } else {
                file_put_contents('./logs/instagram/instagram_'.time().'.html',$content);

                if (preg_match("/window\._sharedData = (.*?);<\/script>/", $content, $matches)){
                    $res = json_decode($matches[1], true);
                    $data = array();
                    $resultData = new ResultDataList();
                    if (isset($res['entry_data']['ProfilePage'][0]['graphql']['user'])) {
                        $user = $res['entry_data']['ProfilePage'][0]['graphql']['user'];
 
                        if (isset($user['username'])) {
                            $data['link'] = new ResultDataField('url','Link','https://instagram.com/'.$user['username'],'Ссылка на профиль','Ссылка на профиль');
                            $data['username'] = new ResultDataField('string','UserName',$user['username'],'Имя пользователя','Имя пользователя');
                        }
                        if (isset($user['full_name'])) {
                            $data['name'] = new ResultDataField('string','Name',iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',$user['full_name'])),'Полное имя','Полное имя');
                        }
                        if (isset($user['biography'])) {
                            $data['about'] = new ResultDataField('string','About',iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',$user['biography'])),'О себе','О себе');
                        }
                        if (isset($user['external_url'])) {
                            $data['url'] = new ResultDataField('url:recursive','Website',$user['external_url'],'Сайт','Сайт');
                        }
                        if (isset($user['followed_by']['count'])) {
                            $data['followers'] = new ResultDataField('string','Followers',$user['followed_by']['count'],'Подписчиков','Подписчиков');
                        }
                        if (isset($user['follows']['count'])) {
                            $data['follows'] = new ResultDataField('string','Follows',$user['follows']['count'],'Подписки','Подписки');
                        }
                        if (isset($user['media']['count'])) {
                            $data['posts'] = new ResultDataField('string','Posts',$user['media']['count'],'Публикаций','Публикаций');
                        }
                        if (isset($user['is_private'])) {
                            $data['private'] = new ResultDataField('string','Private',$user['is_private']?'да':'нет','Закрытый профиль','Закрытый профиль');
                        }
                        if (isset($user['profile_pic_url_hd'])) {
                            $data['image'] = new ResultDataField('image','Image',$user['profile_pic_url_hd'],'Фото профиля','Фото профиля');
                        }
                        $resultData->addResult($data);
                    }
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                } elseif (preg_match("/Page Not Found/",$content)) {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                } elseif(strpos($content,'Post "')) {
                    $mysqli->query("UPDATE isphere.session SET proxyid=NULL,unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='proxy' WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                    file_put_contents('./logs/instagram/instagram_login_err_proxy_'.time().'.txt',$content);
                } else {
                    $error = 'Невозможно обработать ответ';
                    file_put_contents('./logs/instagram/instagram_err_' . time() . '.html', $content);
                }

            }
        }
        $rContext->setSwapData($swapData);

        if(!$error && ($swapData['iteration']>=30)) {
            $error='Превышено количество попыток получения ответа';
        }
        if ($error && ($swapData['iteration']>1)) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

//        $rContext->setSleep(1);
        return true;
    }
}

?>