<?php

class FacebookPlugin implements PluginInterface
{
    private $names = array (
                           'Образование' => array('education', 'Образование', 'Образование'),
                           'Работа' => array('career', 'Карьера', 'Карьера'),
                           'Умения и навыки' => array('expirience', 'Умения и навыки', 'Умения и навыки'),
                           'Места, в которых он жил' => array('places', 'Места проживания', 'Места проживания', 'block'),
                           'Места, в которых она жила' => array('places', 'Места проживания', 'Места проживания', 'block'),
                           'Места, в которых он(-а) проживал(-а)' => array('places', 'Места проживания', 'Места проживания', 'block'),
                           'Контактная информация' => array('contacts', 'Контакты', 'Контакты', 'block'),
                           'Основная информация' => array('info', 'Основная информация', 'Основная информация', 'block'),
                           'Другие имена' => array('aliases', 'Другие имена', 'Другие имена'),
                           'Семейное положение' => array('family', 'Семейное положение', 'Семейное положение'),
                           'Родственники' => array('relatives', 'Родственники', 'Родственники'),
                           'О пользователе' => array('about', 'О себе', 'О себе'),
                           'События из жизни' => array('events', 'События из жизни', 'События из жизни'),
                           'Любимые цитаты' => array('quotations', 'Любимые цитаты', 'Любимые цитаты'),
                           'Родной город' => array('birthplace', 'Родной город', 'Родной город'),
                           'Город проживания' => array('livingplace', 'Город проживания', 'Город проживания'),
                           'Город' => array('livingplace', 'Город проживания', 'Город проживания'),
                           'Район' => array('livingdistrict', 'Район', 'Район'),
                           'Адрес' => array('livingplace', 'Адрес', 'Адрес'),
                           'Переехал' => array('livingplace', 'Город проживания', 'Город проживания'),
                           'Купил' => array('livingplace', 'Город проживания', 'Город проживания'),
                           'Имя' => array('fullname', 'Имя', 'Имя'),
                           'Пол' => array('gender', 'Пол', 'Пол'),
                           'Предпочтения' => array('preferences', 'Предпочтения', 'Предпочтения'),
                           'Интересуют:' => array('interests', 'Интересуют', 'Интересуют'),
                           'Религиозные взгляды' => array('religion', 'Религия', 'Религия'),
                           'Политические взгляды' => array('politics', 'Политика', 'Политика'),
                           'Языки' => array('languages', 'Языки', 'Языки'),
                           'Умения и навыки' => array('skills', 'Умения и навыки', 'Умения и навыки'),
                           'Дата рождения' => array('birthdate', 'Дата рождения', 'Дата рождения'),
                           'День рождения' => array('birthday', 'День рождения', 'День рождения'),
                           'Год рождения' => array('birthyear', 'Год рождения', 'Год рождения'),
                           'Именины' => array('nameday', 'Именины', 'Именины'),
                           'Мобильный' => array('mobile_phone', 'Мобильный телефон', 'Мобильный телефон'/*, 'phone'*/),
                           'Рабочий' => array('work_phone', 'Рабочий телефон', 'Рабочий телефон'/*, 'phone'*/),
                           'Домашний' => array('home_phone', 'Домашний телефон', 'Домашний телефон'/*, 'phone'*/),
                           'Skype' => array('skype', 'Skype', 'Skype', 'skype'),
                           'ICQ' => array('icq', 'ICQ', 'ICQ'),
                           'QIP' => array('qip', 'QIP', 'QIP'),
                           'AIM' => array('aim', 'AIM', 'AIM'),
                           'BBM' => array('bbm', 'BlackBerry Messenger', 'BlackBerry Messenger'),
                           'Live' => array('live', 'Windows Live Messenger', 'Windows Live Messenger'),
                           'LINE' => array('line', 'Line', 'Line'),
                           'Snapchat' => array('snapchat', 'Snapchat', 'Snapchat'),
                           'Google Talk' => array('google_talk', 'Google Talk', 'Google Talk'),
                           'Facebook' => array('facebook', 'Facebook', 'Facebook', 'url', 'https://www.facebook.com'),
                           'Twitter' => array('twitter', 'Twitter', 'Twitter', 'url:recursive'),
                           'Instagram' => array('instagram', 'Instagram', 'Instagram', 'url:recursive', 'https://www.instagram.com/'),
                           'YouTube' => array('youtube', 'YouTube', 'YouTube'),
                           'SoundCloud' => array('soundcloud', 'SoundCloud', 'SoundCloud'),
                           'Ask.fm' => array('askfm', 'Ask.fm', 'Ask.fm'),
                           'VK' => array('vk', 'VK', 'VK', 'url:recursive'),
                           'OK' => array('ok', 'OK', 'OK', 'url:recursive'),
                           'LinkedIn' => array('linkedin', 'LinkedIn', 'LinkedIn', 'url:recursive'),
                           'Веб-сайт' => array('website', 'Сайт', 'Сайт', 'url:recursive'),
                           'Веб-сайты' => array('website', 'Сайт', 'Сайт', 'url:recursive'),
                           'Электронный адрес' => array('email', 'Email', 'Email', 'email'),
                           'Эл. адрес' => array('email', 'Email', 'Email', 'email'),
                           'Эл. почта' => array('email', 'Email', 'Email', 'email'),
    );

    public function getName()
    {
        return 'Facebook';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск в Facebook',
            'facebook_person' => 'Facebook - поиск профилей по имени',
            'facebook_phone' => 'Facebook - проверка телефона на наличие пользователя',
            'facebook_email' => 'Facebook - проверка email на наличие пользователя',
            'facebook_url' => 'Facebook - профиль пользователя',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск в Facebook';
    }

    public function getSessionData($sourceid = 18, $proxyid = false)
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

           
        if ($sourceid) {
            if (!$proxyid) $proxyid="s.proxyid";
            $mysqli->query("UPDATE session s SET request_id=$reqId WHERE sessionstatusid=2 AND sourceid=$sourceid AND lasttime<DATE_SUB(now(), INTERVAL 10 SECOND) ORDER BY lasttime limit 1");
            $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=$proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=$sourceid AND request_id=$reqId ORDER BY lasttime limit 1");
        } else {
            $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND country<>'ru' ORDER BY lasttime limit 1");
        }

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;

                $sessionData->id = 0;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                if ($sourceid) {
                    $sessionData->id = $row->id;
                    $sessionData->code = $row->captcha;
                    $sessionData->token = $row->token;
                    $sessionData->starttime = $row->starttime;
                    $sessionData->lasttime = $row->lasttime;
                    $sessionData->cookies = $row->cookies;
                    $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL WHERE id=".$sessionData->id);
                }

                if (!$row->proxyid) {
//                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND country='ru' AND (rotation>0 OR (SELECT COUNT(*) FROM session WHERE proxyid=proxy.id AND sourceid=31 AND sessionstatusid IN (1,2,6,7))<1) ORDER BY lasttime limit 1");
                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 AND country<>'ru' AND rotation>0 AND id>0 AND (successtime>DATE_SUB(now(), INTERVAL 1 MINUTE) OR lasttime<DATE_SUB(now(), INTERVAL 1 MINUTE)) ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetch_object();
                        if ($row) {
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

        $checktype = substr($initData['checktype'],9);

        if(!isset($initData['url']) && !isset($initData['phone']) && !isset($initData['email']) && (!isset($initData['last_name']) || !isset($initData['first_name'])) && !isset($initData['text'])) {
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
            if (strpos($initData['url'],'facebook.com/')===false) {
                $rContext->setFinished();
                return false;
            }
            $swapData['path'] = strtr($initData['url'],array('://facebook.com'=>'://www.facebook.com','app_scoped_user_id/'=>'profile.php?id='));
        }
/*
        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен');
        return false;
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
        if(!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData($checktype=='phone' || $checktype=='email'?31:18/*, ($checktype=='phone' || $checktype=='email') && ($swapData['iteration']>5) && rand(0,1)?9:false*/);
        }
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
        $rContext->setSwapData($swapData);

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $host = 'https://www.facebook.com';
        $post = false;
        $header = array();
        if($checktype=='phone' || $checktype=='email') {
            if (!isset($swapData['urls'])) {
                $url = $host.'/ajax/login/help/identify.php?ctx=recover';
                $post = http_build_query(array(
//                    'jazoest' => '2863',
                    'lsd' => $swapData['session']->token,
                    'email' => isset($initData['phone'])?$initData['phone']:$initData['email'],
                    'did_submit' => 1,
                    '__user' => 0,
                    '__a' => 1,
//                    '__dyn' => '7xeUmBwjbg7ebwKBWo5O12wAxu13wqovzEdEc8uxa0z8S2S4o1j8hwem0nCq1ewcG0KEswaq0yE7i0n2US1vw9W1PwBgao884y0Mo5W3S1lwlEbE28xe3C0D85a2W2K0zE5W0HUvw4JwJwSyES0gq',
//                    '__csr' => '',
//                    '__req' => 8,
//                    '__hs' => '18954.BP:DEFAULT.2.0.0.0.',
//                    'dpr' => 2,
//                    '__ccg' => 'EXCELLENT',
//                    '__rev' => '1004768206',
//                    '__s' => 'ozfros:mz3blz:d0puxt',
//                    '__hsi' => '7033837314180771177-0',
//                    '__comet_req' => 0,
//                    '__spin_r' => '1004768206',
//                    '__spin_b' => 'trunk',
//                    '__spin_t' => '1637692869',
                ));
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
//                curl_setopt($ch, CURLOPT_HEADER, true);
//                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            } else {
                $url = $host.$swapData['urls'][$swapData['urlnum']];
            }
        } elseif(!isset($swapData['path'])) {
//            $c_user = '';
//            if (preg_match("/c_user=([\d]+);/",$swapData['session']->cookies,$matches)) {
//                $c_user = $matches[1];
//            }
//            $url = $host.'/ajax/typeahead/search.php?value='.urlencode(isset($initData['phone']) ? $initData['phone'] : $initData['email']).'&viewer='.$c_user.'&__a=1';
//            $url = $host.'/ds/search.php?__ajax__&q='.urlencode(isset($initData['phone']) ? $initData['phone'] : $initData['email']);
//            $url = $host.'/search/people/?__ajax__&q='.urlencode(isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : $initData['text']));
//            $url = $host.'/search/people/?q='.urlencode(isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : $initData['text']));
            $url = $host.'/search/str/'.urlencode(isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : (isset($initData['text']) ? $initData['text'] : $initData['last_name'].' '.$initData['first_name']))).'/keywords_users';
//            $rContext->setSleep(1);
        }
        elseif(!isset($swapData['about']))
        {
            $url = strtr($swapData['path'],array('://www.'=>'://m.',));
        }
/*
        elseif(!isset($swapData['photos']))
        {
            $url = strtr($swapData['path'],array('://www.'=>'://m.')).(strpos($swapData['path'],'/profile.php') ? '&v=photos' : '/photos');
        }
*/
        elseif(isset($swapData['photo_path']))
        {
            $url = $swapData['photo_path'];
        }
        else
        {
            $url = strtr($swapData['path'],array('://www.'=>'://m.')).(strpos($swapData['path'],'/profile.php') ? '&v=info' : '/about');
        }
//        print "URL: $url\n";
//        $cookies = 'c_user=' . $this->facebook_user . '; xs=' . $this->facebook_xs . '; datr=';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
//        curl_setopt($ch, CURLOPT_REFERER, $url);
//        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_COOKIE, 'lh=ru_RU; locale=ru_RU; '.$swapData['session']->cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
//        print "Cookie: ".$swapData['session']->cookies."\n";
        if (isset($swapData['urls'])) {
            $s = $this->getSessionData(0);
            if (!$s) {
                if (isset($swapData['iteration']) && ($swapData['iteration']>=30)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSleep(1);
                }
                return false;
            }
//            print "Proxy: ".$s->proxy."\n";
            if ($proxphere) {
                $header[] = 'X-Sphere-Proxy-Spec-Id: '.$s->proxyid;
                curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
                curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
                curl_setopt($ch,CURLOPT_PROXYTYPE,CURLPROXY_SOCKS5_HOSTNAME);
                curl_setopt($ch,CURLOPT_PROXY, $proxphere);
            } else {
                curl_setopt($ch,CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt($ch,CURLOPT_PROXY, $s->proxy);
                if ($swapData['session']->proxy_auth) {
                    curl_setopt($ch,CURLOPT_PROXYUSERPWD,$s->proxy_auth);
                    curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY);
                }
            }
        } elseif ($swapData['session']->proxy) {
//            print "Proxy: ".$swapData['session']->proxy."\n";
            if ($proxphere) {
                $header[] = 'X-Sphere-Proxy-Spec-Id: '.$swapData['session']->proxyid;
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],9);

        $error = false;
        $curlError = false; //curl_error($rContext->getCurlHandler());

        if($curlError && $swapData['iteration']>10)
        {
            $rContext->setFinished();
            $rContext->setError($curlError==''?'Превышено количество попыток получения ответа':$curlError);

            return false;
        }

        if(!$curlError) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
            $data = array();

            if ($checktype=='phone' || $checktype=='email') {
                if (!isset($swapData['urls'])) {
//                    file_put_contents('./logs/facebook/face_identify_'.$swapData['iteration'].'_'.time().'.html', curl_error($rContext->getCurlHandler())."\r\n".curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);

                    $cookies = str_cookies($swapData['session']->cookies);
                    foreach (curl_getinfo($rContext->getCurlHandler(),CURLINFO_COOKIELIST) as $cookie) {
                        $arr = explode("	",$cookie);
//                        if ($arr[0]=='.facebook.com') {
                            $cookies[$arr[5]] = $arr[6];
//                        }
                    }
                    $new_cookies = cookies_str($cookies);
                    $swapData['session']->cookies = $new_cookies;

                    $start = strpos($content,'{');
                    $content = trim(substr($content,$start,strlen($content)-$start+1));
                    $res = json_decode($content, true);

                    if (isset($res['domops'][0][3]['__html'])) {
                        $html = $res['domops'][0][3]['__html'];
//                        file_put_contents('./logs/facebook/face_identify_html_'.$swapData['iteration'].'_'.time().'.html', $html);
                        if (preg_match("/не найдено/",$html)) {
                            $resultData = new ResultDataList();
                            $rContext->setResultData($resultData);
                            $rContext->setFinished();
                            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                            $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                        } elseif (preg_match_all("/<div class=\"[^\"]+\">([^<]+)<\/div><div class=\"[^\"]+\">Пользователь Facebook<\/div>/sim", $html, $matches)) {
                            $resultData = new ResultDataList();
                            foreach ($matches[1] as $name) {
                                $data = array();
                                if ((isset($initData['phone']) && $name!='+'.$initData['phone']) || (isset($initData['email']) && $name!=$initData['email']))
                                    $data['name'] = new ResultDataField('string','Name', strtr($name,array('...'=>'.')), 'Имя', 'Имя');
                                $data['result'] = new ResultDataField('string','Result','Найден','Результат','Результат');
                                $data['result_code'] = new ResultDataField('string','ResultCode','FOUND','Код результата','Код результата');
                                $resultData->addResult($data);
                            }
                            $rContext->setResultData($resultData);
                            $rContext->setFinished();
                            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                            $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                        } elseif (preg_match_all("/<a role=\"button\" class=\"[^\"]+\" href=\"(\/login\/identify[^\"]+)\"/", $html, $matches)) {
                            foreach ($matches[1] as $key => $url) {
                                $swapData['urls'][] = html_entity_decode($url);
                                $swapData['urlnum'] = 0;
                            }
                        } elseif ($swapData['iteration']>5) {
                            $error = "Некорректный ответ сервиса";
                            file_put_contents('./logs/facebook/face_identify_html_err_'.$swapData['iteration'].'_'.time().'.html', $html);
                        }
                    } elseif (isset($res['onload'][0])) {
                        $onload = $res['onload'][0];
//                        file_put_contents('./logs/facebook/face_identify_onload_'.$swapData['iteration'].'_'.time().'.html', $onload);
                        if (preg_match("/window.location.href=\"([^\"]+)\"/", $onload, $matches)) {
                            $swapData['urls'][] = strtr($matches[1],array('\/'=>'/'));
                            $swapData['urlnum'] = 0;
                        } elseif ($swapData['iteration']>5) {
                            $error = "Некорректный ответ сервиса";
                            file_put_contents('./logs/facebook/face_identify_onload_err_'.$swapData['iteration'].'_'.time().'.html', $onload);
                        }
                    } elseif (isset($res['jsmods']['require'][0][1]) && ($res['jsmods']['require'][0][1]=='redirectPageTo') && isset($res['jsmods']['require'][0][3][0])) {
                        $redirect = $res['jsmods']['require'][0][3][0];
//                        file_put_contents('./logs/facebook/face_identify_redirect_'.$swapData['iteration'].'_'.time().'.html', $redirect);
                        if (preg_match("/\/recover\/initiate/", $redirect)) {
                            $swapData['urls'][] = $redirect;
                            $swapData['urlnum'] = 0;
                        } elseif ($swapData['iteration']>5) {
                            $error = "Некорректный ответ сервиса";
                            file_put_contents('./logs/facebook/face_identify_redirect_err_'.$swapData['iteration'].'_'.time().'.html', $onload);
                        }
                    } elseif(isset($res['errorSummary']) && $res['errorSummary']) {
                        if (strpos($res['errorSummary'],'не доступен')) {
                            $resultData = new ResultDataList();
                            $data['result'] = new ResultDataField('string','Result','Найденный аккаунт недоступен','Результат','Результат');
                            $data['result_code'] = new ResultDataField('string','ResultCode','FOUND_UNAVAILABLE','Код результата','Код результата');
                            $resultData->addResult($data);
                            $rContext->setResultData($resultData);
                            $rContext->setFinished();
                            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                            $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                        } elseif (strpos($res['errorSummary'],'заблокированы')) {
//                            file_put_contents('./logs/facebook/face_identify_locked_'.$swapData['iteration'].'_'.time().'.html', curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                            if (isset($swapData['session'])) {
                                $mysqli->query("UPDATE isphere.session SET proxyid=NULL,unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='locked' WHERE id=" . $swapData['session']->id);
//                                $mysqli->query("UPDATE isphere.session SET proxyid=NULL WHERE sourceid=31 AND proxyid=" . $swapData['session']->proxyid . " AND id<>" . $swapData['session']->id . " ORDER BY lasttime LIMIT 3");
                                $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                                unset($swapData['session']);
                            }
                        } else {
                            $error = $res['errorSummary'];
                        }
                    } elseif(strpos($content,'something went wrong')) {
                        $resultData = new ResultDataList();
                        $data['result'] = new ResultDataField('string','Result','Найденный аккаунт недоступен из-за ошибки','Результат','Результат');
                        $data['result_code'] = new ResultDataField('string','ResultCode','FOUND_ERROR','Код результата','Код результата');
                        $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                        $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                    } elseif(strpos($content,'Post "')) {
                        $mysqli->query("UPDATE isphere.session SET proxyid=NULL,unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='proxy' WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                        file_put_contents('./logs/facebook/facebook_err_proxy_'.time().'.txt',$content);
                    } elseif (!$content) {
                        if (isset($swapData['session'])) {
                            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='empty' WHERE id=" . $swapData['session']->id);
                            unset($swapData['session']);
                        }
                    } elseif ($swapData['iteration']>5) {
                        $error = "Некорректный ответ сервиса";
                        file_put_contents('./logs/facebook/face_identify_err_'.$swapData['iteration'].'_'.time().'.html', curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                    }
                } elseif (strpos($content,'заблокированы') && $swapData['iteration']%5==0) {
//                    file_put_contents('./logs/facebook/face_recover_locked_'.$swapData['iteration'].'_'.time().'.html', curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                    unset($swapData['urls']);
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET proxyid=NULL,unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='locked' WHERE id=" . $swapData['session']->id);
//                        $mysqli->query("UPDATE isphere.session SET proxyid=NULL WHERE sourceid=31 AND proxyid=" . $swapData['session']->proxyid . " AND id<>" . $swapData['session']->id . " ORDER BY lasttime LIMIT 3");
                        $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                        unset($swapData['session']);
                    }
                } elseif (strpos($content,'не может быть обработан')) {
//                    file_put_contents('./logs/facebook/face_recover_impossible_'.$swapData['iteration'].'_'.time().'.html', curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                    unset($swapData['urls']);
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='impossible' WHERE id=" . $swapData['session']->id);
                        $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                        unset($swapData['session']);
                    }
                } else {
//                    file_put_contents('./logs/facebook/face_recover_'.$swapData['iteration'].'_'.time().'.html', curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                    $resultData = isset($swapData['result'])?$swapData['result']:new ResultDataList();
                    if (preg_match("/сброса пароля\?(.*?)Пользователь Facebook/sim",$content,$dataPart)) {
                        $data = array();
                        if (preg_match("/<div[^>]*>([^<]+)<\/div><div[^>]*>$/sim",$dataPart[1],$matches)) {
                            $name = html_entity_decode($matches[1]);
                            if ((isset($initData['phone']) && $name!='+'.$initData['phone']) || (isset($initData['email']) && $name!=$initData['email']))
                                $data['name'] = new ResultDataField('string','Name', strtr($name,array('...'=>'.')), 'Имя', 'Имя');
                        }
                        if (preg_match("/img\" src=\"([^\&]+)\&amp;square_px/",$dataPart[1],$matches)) {
//                            $data['photo'] = new ResultDataField('image','Photo', html_entity_decode($matches[1]).'&square_px=1024', 'Фото', 'Фото');
                        }
                        if (preg_match_all("/Отправить код по SMS<\/div><div[^>]*><div[^>]*>([^<]+)/",$dataPart[1],$matches)) {
                            foreach($matches[1] as $i => $value)
                                $data['phone'.$i] = new ResultDataField('string','Phone', html_entity_decode($value), 'Телефон', 'Телефон');
                        }
                        if (preg_match("/Отправить код на эл. адрес<\/div><div[^>]*><div[^>]*>(.*?)<\/div><\/div><\/div>/",$dataPart[1],$matches)) {
                            $matches[1] = explode('</div><div>',$matches[1]);
                            foreach($matches[1] as $i => $value)
                                $data['email'.$i] = new ResultDataField('string','Email', html_entity_decode($value), 'Email', 'Email');
                        }
                    }
                    $data['result'] = new ResultDataField('string','Result','Найден','Результат','Результат');
                    $data['result_code'] = new ResultDataField('string','ResultCode','FOUND','Код результата','Код результата');

                    $resultData->addResult($data);
                    if (++$swapData['urlnum']<sizeof($swapData['urls'])) {
                        $swapData['result'] = $resultData;
                    } else {
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                        $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                    }
                }
            } elseif (!isset($swapData['path'])) {
//                file_put_contents('./logs/facebook/face_search_'.time().'.html',$content);
                if (preg_match("/id=\"empty_result_error\">/",$content)) {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                } elseif (preg_match("/id=\"BrowseResultsContainer\">([^>]+>)(.*?)<div id=\"browse_end_of_results_footer\"/sim",$content,$result)
                       || preg_match("/id=\"BrowseResultsContainer\">([^>]+>)(.*?) data-testid=\"paginated_results_pagelet\"/sim",$content,$result)) {
                    $resultData = new ResultDataList();
                    $dataParts = preg_split('/'.$result[1].'/',$result[2]);
//                    if (preg_match_all('/'.$result[1].'(.*?)<iframe /',$content,$dataParts))
                    if (sizeof($dataParts)>=5) {
                        $error = "Найдено слишком много совпадений. Попробуйте указать в запросе место учебы, работы или жительства.";
                        if ($rContext->getLevel()==0)
                            $rContext->setError($error);
                        $rContext->setFinished();
                        return true;
                    }
                    foreach ($dataParts as $dataPart) {
                        $data = array();

//                        if (preg_match("/<a class=\"[^\"]+\" href=\"(https:\/\/www.facebook.com\/[^\"]+)\" data-testid=\"serp_result_link[^\"]+\"><span>([^<]+)<\/span><\/a>/",$dataPart,$matches)) {
                        if (preg_match("/<a class=\"[^\"]+\" href=\"(https:\/\/www.facebook.com\/[^\"]+)\"[^>]*><span>([^<]+)<\/span><\/a>/",$dataPart,$matches)) {
                            $data['name'] = new ResultDataField('string','Name', html_entity_decode($matches[2]), 'Имя', 'Имя');
                            $path = html_entity_decode($matches[1]);
                            if (strpos($path,'?') && !strpos($path,'/profile.php?'))
                                $path = substr($path,0,strpos($path,'?'));
                            if (strpos($path,'&') && strpos($path,'/profile.php?'))
                                $path = substr($path,0,strpos($path,'&'));
                            $data['profile'] = new ResultDataField('url'.(sizeof($dataParts)<=5?':recursive':''),'Profile', $path, 'Страница в Facebook', 'Страница в Facebook');
                        }
                        if (preg_match("/<img class=\"[^\s]+ img\" src=\"([^\"]+)/",$dataPart,$matches))
                            $data['photo'] = new ResultDataField('image','Photo', html_entity_decode($matches[1]), 'Фото', 'Фото');
                        if (preg_match("/data-profileid=\"([\d]+)\"/",$dataPart,$matches))
                            $data['uid'] = new ResultDataField('string','UserID', $matches[1], 'ID', 'ID');

//                        $div = preg_split('/<div>/',$dataPart);
//                        $lastdiv = $div[sizeof($div)-1];
                        $lines = preg_split('/<\/div><\/div>/',$dataPart /*$lastdiv*/);
                        array_shift($lines);
                        array_shift($lines);
                        array_pop($lines);

                        foreach ($lines as $line) {
                            $text = trim(str_replace("&#039;", "'", html_entity_decode(strip_tags($line))));
                            if (preg_match("/^Учился в (.*?)$/",$text,$matches) || preg_match("/^Училась в (.*?)$/",$text,$matches) || preg_match("/^Учил[а]* .*? в (.*?)$/",$text,$matches) || preg_match("/^Изучал[\^s]* .*? в (.*?)$/",$text,$matches)) {
                                $matches = explode("'",$matches[1]);
                                $data['education'] = new ResultDataField('string', 'education', $matches[0], 'Место учёбы', 'Место учёбы');
                                if (sizeof($matches)==2)
                                    $data['educationyear'] = new ResultDataField('string', 'educationyear', $matches[1], 'Закончил учиться в', 'Закончил учиться в');
                            } elseif (preg_match("/^Жил[а]* в (.*?)$/",$text,$matches) || preg_match("/^Живет в (.*?)$/",$text,$matches) || preg_match("/^Купил.*? в (.*?)$/",$text,$matches)) {
                                $data['living'] = new ResultDataField('string', 'living', $matches[1], 'Место жительства', 'Место жительства');
                            } elseif (preg_match("/^Из (.*?)$/",$text,$matches)) {
                                $data['birthplace'] = new ResultDataField('string', 'birthplace', $matches[1], 'Место рождения', 'Место рождения');
                            } elseif (preg_match("/^Работал[а]* в компании (.*?)$/",$text,$matches) || preg_match("/^Worked at компании (.*?)$/",$text,$matches)) {
                                if (preg_match("/^«(.*?)»$/",$matches[1],$matches2))
                                    $matches[1] = $matches2[1];
                                $data['oldjob'] = new ResultDataField('string', 'oldjob', $matches[1], 'Прошлое место работы', 'Прошлое место работы');
                                if (preg_match("/ в <a href=\"(https:\/\/www.facebook.com\/[^\"]+)/",$line,$matches) || preg_match("/ at <a href=\"(https:\/\/www.facebook.com\/[^\"]+)/",$line,$matches)) {
                                    $path = html_entity_decode($matches[1]);
                                    if (strpos($path,'?') && !strpos($path,'/profile.php?'))
                                        $path = substr($path,0,strpos($path,'?'));
                                    if (strpos($path,'&') && strpos($path,'/profile.php?'))
                                        $path = substr($path,0,strpos($path,'&'));
                                    $data['oldjobprofile'] = new ResultDataField('url','OldJobProfile', $path, 'Страница прошлого места работы', 'Страница прошлого места работы');
                                }
                            } elseif (preg_match("/^(.*?) в (.*?)$/",$text,$matches)) {
                                $data['position'] = new ResultDataField('string', 'position', $matches[1], 'Должность', 'Должность');
                                $data['job'] = new ResultDataField('string', 'job', $matches[2], 'Место работы', 'Место работы');
                                if (preg_match("/ в <a href=\"(https:\/\/www.facebook.com\/[^\"]+)/",$line,$matches)) {
                                    $path = html_entity_decode($matches[1]);
                                    if (strpos($path,'?') && !strpos($path,'/profile.php?'))
                                        $path = substr($path,0,strpos($path,'?'));
                                    if (strpos($path,'&') && strpos($path,'/profile.php?'))
                                        $path = substr($path,0,strpos($path,'&'));
                                    $data['jobprofile'] = new ResultDataField('url','JobProfile', $path, 'Страница места работы', 'Страница места работы');
                                }
                            } elseif ($text && $text!='Загрузка других результатов...') {
                                $data['info'] = new ResultDataField('string', 'info', $text, 'Информация', 'Информация');
                            }
                        }

                        if (sizeof($data)) $resultData->addResult($data);
                    }
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                } elseif ($content) {
                    if (!preg_match("/facebook.com\/settings\?/",$content)) {
                        if (isset($swapData['session'])) {
                            $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE id=" . $swapData['session']->id);
                            unset($swapData['session']);
                        }
                    } else {
                        file_put_contents('./logs/facebook/face_err_'.$swapData['iteration'].'_'.time().'.html', $content);
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
//                file_put_contents('./logs/facebook/face_'.time().'.html',$content);
                $data = isset($swapData['data']) ? $swapData['data'] : array();
/*
                if(preg_match("/<a href=\"(\/photo.php\?fbid=[^\"]+)\" id=\"/", $content, $matches)){
                    $swapData['photo_path'] = 'https://m.facebook.com'.$matches[1];
                }
*/
                if(preg_match("/<a href=\"\/photo.php\?fbid=([^\&]+)[^\"]+\" id=\"/", $content, $matches)){
                    $swapData['photo_path'] = 'https://m.facebook.com/photo/view_full_size/?fbid='.$matches[1];
                }
                if(preg_match("/<strong class=\"[a-z]+\">([^<]+)<\/strong>/", $content, $matches)){
                    $data['name'] = new ResultDataField('string','Name', $matches[1], 'Имя', 'Имя');
                }
                if(preg_match("/Ye1TQi63ARL.png/", $content)){
                    $data['presence'] = new ResultDataField('string', 'presence', 'mobile', 'Присутствие', 'Присутствие');
                }
                if(preg_match("/\(\((\d+)\)\)/", $content, $matches)){
                    $data['friends'] = new ResultDataField('string', 'friends', $matches[1], 'Друзья', 'Друзья');
                }
                if(preg_match("/<div class=\"[a-z]{2} [a-z]{2} [a-z]{2} [a-z]{2}\">([^<]+)/", $content, $matches)){
                    $data['aboutself'] = new ResultDataField('string','AboutSelf', $matches[1], 'О себе', 'О себе');
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
                        $content = substr($content,0,strpos($content,'<a name="footer-actions-basic">'));
                        $tmparray = explode('<tr><td class="t ', $content);
//                        $tmparray = explode('data-sigil="profile-card"><div', $content);
                        $counter = 0;
                        foreach($tmparray as $key => $val ){
                                $val = substr($val,strpos($val,'>')+1);
                                $val = substr($val,strpos($val,'>')+1);
//                                file_put_contents('./logs/facebook/faceb'.$key.'_'.time().'.html',$val);
			        if($key == 0){
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

                                if(($key > 0) && (strpos($val,'<')>0)){
                                        $title = substr($val,0,strpos($val,'<'));
                                        $fortitle = substr($val,strpos($val,'<'));
                                        $fortitle = preg_replace("/<span>([^<]+)<\/span><wbr \/><span class=\"word_break\"><\/span>/","$1",$fortitle);

                                        if (strpos($title,'О пользователе')===0) $title = 'О пользователе';
//                                        if(strpos($fortitle, '<div class="c">') !== false){
//                                                 $$fortitle = str_replace('<div class="c">', '; ', $fortitle);
//                                        }

                                        while(preg_match('/(<div class=\"[^\"]+\" id=\"u_0_[^\"]+\">)/',$fortitle,$div)){
                                                 $fortitle = str_replace($div[1],'; ',$fortitle);
                                        }
                                        while(preg_match('/(<table cellspacing=\"[^\"]+\" cellpadding=\"[^\"]+\" class=\"[^\"]+\">)/',$fortitle,$div)){
                                                 $fortitle = str_replace($div[1],'; ',$fortitle);
                                        }

			                $fortitle = str_replace('</i>','; ',$fortitle);
			                $fortitle = str_replace('<span>','; ',$fortitle);
			                $fortitle = str_replace('<div class="clear">','; ',$fortitle);

                                        $fortitle = preg_replace("/<a [^>]+>/", "", $fortitle);
                                        $fortitle = preg_replace("/<[^>]+>/", "|", $fortitle);
                                        $fortitle = preg_replace("/\|+/", ", ", $fortitle);
                                        $fortitle = preg_replace("/^,+\;/", " ", $fortitle);
                                        $fortitle = html_entity_decode(trim($fortitle));
                                        $fortitle = str_replace("&#039;", "'", $fortitle);
                                        $fortitle = str_replace("; ,", ";", $fortitle);
                                        $fortitle = str_replace(", ;", ";\n", $fortitle);

                                        if ($fortitle) {
                                            if (($fortitle[strlen($fortitle)-1]==';') || ($fortitle[strlen($fortitle)-1]==',')) $fortitle = trim(substr($fortitle,0,strlen($fortitle)-1));
                                        }
                                        if ($fortitle) {
                                            if (($fortitle[0]==';') || ($fortitle[0]==',')) $fortitle = trim(substr($fortitle,1));
                                        }
                                        if (isset($this->names[$title])){
                                                 $field = $this->names[$title];
						 if (isset($field[3]) && ($field[3]=='block')){
                                                         $itmparr = explode(';', $fortitle);
                                                         $icounter = 0;
                                                         foreach ($itmparr as $slplace){
						                  if($comma=strpos($slplace,',')){
                                                                           $val2 = trim(substr($slplace,$comma+1));
                                                                           if ($val2 && ($val2!='Информация скрыта') && ($val2!='Загрузка...')) {
                                                                               $title2 = trim(substr($slplace,0,$comma));
                                                                               if (strpos($title2,'Переехал')===0) $title2 = 'Переехал';
                                                                               if (strpos($title2,'(')>0) $title2 = trim(substr($title2,0,strpos($title2,'(')));
							                       if (isset($this->names[$title2])){
                                                                                  $field2 = $this->names[$title2];
                                                                                  if (isset($field2[3]) && ($field2[3]=='phone') && $val2 = preg_replace("/\D/","",$val2)) {
                                                                                     if ((strlen($val2)==11) && (substr($val2,0,1)=='8'))
                                                                                        $val2 = substr($val2,1);
                                                                                     if (strlen($val2)==10)
                                                                                        $val2 = '7'.$val2;
                                                                                  }
                                                                                  if (isset($field2[3]) && ($field2[3]=='url' || $field2[3]=='url:recursive') && isset($field2[4]) && (strpos($val2,$field2[4])===false)) {
                                                                                     $val2 = $field2[4].$val2;
                                                                                  }
								                  $data[$field2[0]] = new ResultDataField(isset($field2[3])?$field2[3]:'string',$field2[0], $val2, $field2[1], $field2[2]);
								               } else {
                                                                                  $icounter++;
                                                                                  $data[$field[0].$icounter] = new ResultDataField('string', $field[0].$icounter, $val2, $title2, $title2);
                                                                                  file_put_contents('./logs/fields/facebook_'.time().'_'.$title2 , $title2."\n".$val2);
							                       }
                                                                           }
							          }
                                                         }
						 } else {
                                                         if (isset($field[3]) && ($field[3]=='url' || $field2[3]=='url:recursive') && isset($field[4]) && (strpos($fortitle,$field[4])===false)) {
                                                            $fortitle = $field[4].$fortitle;
                                                         }
                                                         $data[$field[0]] = new ResultDataField(isset($field[3])&&($field[3]!='block')?$field[3]:'string',$field[0], $fortitle, $field[1], $field[2]);
                                                 }
                                        }
                                        else{
                                                 $counter++;
                                                 $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $fortitle, $title, $title);
                                                 file_put_contents('./logs/fields/facebook_'.time().'_'.$title , $title."\n".$fortitle);
                                        }
                                }
                        }


                if (!isset($swapData['photo_path'])) {
                    $resultData = new ResultDataList();
                    if (sizeof($data))
                        $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
                }

                $swapData['about'] = true;
                $swapData['data'] = $data;
            } elseif (isset($swapData['photo_path'])) {
                $data = $swapData['data'];
//                $start = strpos($content,'{');
//                $content = trim(substr($content,$start,strlen($content)-$start+1));
//                $ares = json_decode($content, true);
//                file_put_contents('./logs/facebook/face_photo_'.time().'.html',$content);
                if (preg_match("/url=([^\"]+)/",$content,$matches)) {
                    $data['photo'] = new ResultDataField('image','Photo', html_entity_decode($matches[1]), 'Фото профиля', 'Фото профиля');
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
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
            } else {
//                file_put_contents('./logs/facebook/face_about_'.time().'.html',$content);
                $data = $swapData['data'];

                        $content = substr($content,0,strpos($content,'<a name="footer-actions-basic">'));
                        $tmparray = explode('<tr><td class="s ', $content);
//                        $tmparray = explode('data-sigil="profile-card"><div', $content);
                        $counter = 0;
                        foreach($tmparray as $key => $val ){
                                $val = substr($val,strpos($val,'>')+1);
                                $val = substr($val,strpos($val,'>')+1);
//                                file_put_contents('./logs/facebook/faceb'.$key.'_'.time().'.html',$val);
			        if($key == 0){
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

                                if(($key > 0) && (strpos($val,'<')>0)){
                                        $title = substr($val,0,strpos($val,'<'));
                                        $fortitle = substr($val,strpos($val,'<'));
                                        $fortitle = preg_replace("/<span>([^<]+)<\/span><wbr \/><span class=\"word_break\"><\/span>/","$1",$fortitle);

                                        if (strpos($title,'О пользователе')===0) $title = 'О пользователе';
//                                        if(strpos($fortitle, '<div class="c">') !== false){
//                                                 $$fortitle = str_replace('<div class="c">', '; ', $fortitle);
//                                        }

                                        while(preg_match('/(<div class=\"[^\"]+\" id=\"u_0_[^\"]+\">)/',$fortitle,$div)){
                                                 $fortitle = str_replace($div[1],'; ',$fortitle);
                                        }
                                        while(preg_match('/(<table cellspacing=\"[^\"]+\" cellpadding=\"[^\"]+\" class=\"[^\"]+\">)/',$fortitle,$div)){
                                                 $fortitle = str_replace($div[1],'; ',$fortitle);
                                        }

			                $fortitle = str_replace('</i>','; ',$fortitle);
			                $fortitle = str_replace('<span>','; ',$fortitle);
			                $fortitle = str_replace('<div class="clear">','; ',$fortitle);

                                        $fortitle = preg_replace("/<a [^>]+>/", "", $fortitle);
                                        $fortitle = preg_replace("/<[^>]+>/", "|", $fortitle);
                                        $fortitle = preg_replace("/\|+/", ", ", $fortitle);
                                        $fortitle = preg_replace("/^,+\;/", " ", $fortitle);
                                        $fortitle = html_entity_decode(trim($fortitle));
                                        $fortitle = str_replace("&#039;", "'", $fortitle);
                                        $fortitle = str_replace("; ,", ";", $fortitle);
                                        $fortitle = str_replace(", ;", ";\n", $fortitle);

                                        if ($fortitle) {
                                            if (($fortitle[strlen($fortitle)-1]==';') || ($fortitle[strlen($fortitle)-1]==',')) $fortitle = trim(substr($fortitle,0,strlen($fortitle)-1));
                                        }
                                        if ($fortitle) {
                                            if (($fortitle[0]==';') || ($fortitle[0]==',')) $fortitle = trim(substr($fortitle,1));
                                        }
                                        if (isset($this->names[$title])){
                                                 $field = $this->names[$title];
						 if (isset($field[3]) && ($field[3]=='block')){
                                                         $itmparr = explode(';', $fortitle);
                                                         $icounter = 0;
                                                         foreach ($itmparr as $slplace){
						                  if($comma=strpos($slplace,',')){
                                                                           $val2 = trim(substr($slplace,$comma+1));
                                                                           if ($val2 && ($val2!='Информация скрыта') && ($val2!='Загрузка...')) {
                                                                               $title2 = trim(substr($slplace,0,$comma));
                                                                               if (strpos($title2,'Переехал')===0) $title2 = 'Переехал';
                                                                               if (strpos($title2,'(')>0) $title2 = trim(substr($title2,0,strpos($title2,'(')));
							                       if (isset($this->names[$title2])){
                                                                                  $field2 = $this->names[$title2];
                                                                                  if (isset($field2[3]) && ($field2[3]=='phone') && $val2 = preg_replace("/\D/","",$val2)) {
                                                                                     if ((strlen($val2)==11) && (substr($val2,0,1)=='8'))
                                                                                        $val2 = substr($val2,1);
                                                                                     if (strlen($val2)==10)
                                                                                        $val2 = '7'.$val2;
                                                                                  }
                                                                                  if (isset($field2[3]) && ($field2[3]=='url' || $field2[3]=='url:recursive') && isset($field2[4]) && (strpos($val2,$field2[4])===false)) {
                                                                                     $val2 = $field2[4].$val2;
                                                                                  }
								                  $data[$field2[0]] = new ResultDataField(isset($field2[3])?$field2[3]:'string',$field2[0], $val2, $field2[1], $field2[2]);
								               } else {
                                                                                  $icounter++;
                                                                                  $data[$field[0].$icounter] = new ResultDataField('string', $field[0].$icounter, $val2, $title2, $title2);
                                                                                  file_put_contents('./logs/fields/facebook_'.time().'_'.$title2 , $title2."\n".$val2);
							                       }
                                                                           }
							          }
                                                         }
						 } else {
                                                         if (isset($field[3]) && ($field[3]=='url' || $field2[3]=='url:recursive') && isset($field[4]) && (strpos($fortitle,$field[4])===false)) {
                                                            $fortitle = $field[4].$fortitle;
                                                         }
                                                         $data[$field[0]] = new ResultDataField(isset($field[3])&&($field[3]!='block')?$field[3]:'string',$field[0], $fortitle, $field[1], $field[2]);
                                                 }
                                        }
                                        else{
                                                 $counter++;
                                                 $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $fortitle, $title, $title);
                                                 file_put_contents('./logs/fields/facebook_'.time().'_'.$title , $title."\n".$fortitle);
                                        }
                                }
                        }

                    $resultData = new ResultDataList();
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    $mysqli->query("UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->proxyid);
            }
            $swapData['data'] = $data;
            $rContext->setSwapData($swapData);
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>=10)
            $error='Превышено количество попыток получения ответа';

        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        $rContext->setSleep(1);
        return true;
    }
}

?>