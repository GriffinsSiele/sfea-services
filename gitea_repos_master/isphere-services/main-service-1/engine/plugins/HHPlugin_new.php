<?php

class HHPlugin implements PluginInterface
{
    public function getName()
    {
        return 'HH';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск в HH',
            'hh_person' => 'HH - поиск резюме по имени',
            'hh_phone' => 'HH - поиск резюме по номеру телефона',
            'hh_email' => 'HH - поиск резюме по email',
            'hh_url' => 'HH - резюме',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск в HH';
    }

    public function getSessionData($sessionid = false)
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=27 AND unix_timestamp(now())-unix_timestamp(lasttime)>5".($sessionid?" AND id=$sessionid":"")." ORDER BY lasttime limit 1");
        if($result) {
            $row = $result->fetch_object();
            if ($row) {
                $sessionData = new \StdClass;
                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = '95.182.120.95:25050'; //$row->proxy;
                $sessionData->proxy_auth = '69dSocXj0x:sj-den'; //strlen($row->proxy_auth)>1?$row->proxy_auth:false;
                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used' WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

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
        }

        if (isset($initData['url'])) {
            if (strpos($initData['url'],'hh.ru/')===false) {
                $rContext->setFinished();
                return false;
            }
            if (!isset($swapData['pdfpath']))
                $swapData['path'] = $initData['url'];
        }
        $rContext->setSwapData($swapData);
/*
        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен)');
        return false;
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        if(!isset($swapData['session'])) {
            $sessionid = false;
            if (isset($swapData['path'])) {
                if (preg_match("/^([^\?]+)\?sessionid=([\d]+)$/",$swapData['path'],$matches)) {
                    $swapData['path'] = $matches[1];
                    $sessionid = $matches[2];
                } else {
                    $rContext->setFinished();
                    return false;
                }
            }
            $swapData['session'] = $this->getSessionData($sessionid);
            if (isset($swapData['session'])) {
                $swapData['iteration']=1;
                $rContext->setSwapData($swapData);
            }
        }
        if(!$swapData['session']) {
            $result = $mysqli->query("SELECT count(*) sessions FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=27");
            if($result) {
                $row = $result->fetch_object();
                if ($row && $row->sessions==0) {
                    $rContext->setFinished();
//                    $rContext->setError('Нет доступных аккаунтов для выполнения запроса');
                }
            }

            if (isset($swapData['iteration']) && ($swapData['iteration']>=30)) {
                $rContext->setFinished();
                $rContext->setError('Слишком много запросов в очереди');
            } else {
                (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
                $rContext->setSwapData($swapData);
                $rContext->setSleep(3);
            }
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        if(!isset($swapData['path']) && !isset($swapData['pdfpath'])) {
//area=113 Россия
            $url = 'https://hh.ru/search/resume?st=resumeSearch&area=113&items_on_page=50&exp_period=all_time&order_by=publication_time&text='.urlencode(isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : (isset($initData['text']) ? $initData['text'] : $initData['last_name'].' '.$initData['first_name']))).'&pos=full_text&logic=normal&clusters=true&no_magic=false';
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        } elseif( isset($swapData['path']) ){
            $url = strtr($swapData['path'],array('/archive'=>'/resume')).'?print=true';
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        } elseif( isset($swapData['photopath']) ){
            $url = $swapData['photopath'];
        } else {
            $url = $swapData['pdfpath'];
        }

        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36 OPR/56.0.3051.52');

        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
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
        global $mysqli;
        global $serviceurl;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = $swapData['iteration']>3 ? curl_error($rContext->getCurlHandler()) : false;
        if (strpos($error,'timed out') || strpos($error,'connection')) {
            $error = false;
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='connectionerror' WHERE id=" . $swapData['session']->id);
        }
        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());

//            if (!isset($swapData['pdfpath']) && !isset($swapData['photopath'])) {
                $cookies = str_cookies($swapData['session']->cookies);
                foreach (curl_getinfo($rContext->getCurlHandler(),CURLINFO_COOKIELIST) as $cookie) {
//                    print 'Response cookie '.$cookie."\n";
                    $arr = explode("	",$cookie);
                    if (!isset($cookies[$arr[5]]) || $cookies[$arr[5]]!=$arr[6]) {
                        $cookies[$arr[5]] = $arr[6];
//                        print 'New cookie '.$arr[5].' = '.$arr[6]."\n";
                    }
                }
                $new_cookies = cookies_str($cookies);
                $swapData['session']->cookies = $new_cookies;
                $rContext->setSwapData($swapData);
                file_put_contents('./logs/hh/hh_'.time().'.cookies',$new_cookies);
                $mysqli->query("UPDATE isphere.session SET cookies='$new_cookies' WHERE id=" . $swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
//            }

            if(!isset($swapData['path']) && !isset($swapData['pdfpath'])) {
                file_put_contents('./logs/hh/hh_search_'.time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content."\r\n".implode("; ",curl_getinfo($rContext->getCurlHandler(),CURLINFO_COOKIELIST)));

                if(strlen($content)<100){
                    file_put_contents('./logs/hh/hh_err_'.time().'.html',$content);
                    if ($swapData['iteration']>5) {
//                        $mysqli->query("UPDATE isphere.session SET lasttime=now(),endtime=now(),sessionstatusid=3,statuscode='noanswer' WHERE id=" . $swapData['session']->id);
                        $rContext->setError("Ответ не получен");
                        $rContext->setFinished();
                    } else {
                       $rContext->setSleep(3);
                    }
                    return true;
                } elseif(preg_match("/<a href=\"\/account\/login/sim", $content, $matches)){
                    file_put_contents('./logs/hh/hh_notlogged_'.time().'.html',$content);
//                    $mysqli->query("UPDATE isphere.session SET lasttime=now(),endtime=now(),sessionstatusid=3,statuscode='notlogged' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='notlogged' WHERE id=" . $swapData['session']->id);
//                    $rContext->setError("Нет актуальных сессий");
//                    $rContext->setFinished();
//                    return true;
                } elseif(preg_match("/404 Not Found/sim", $content, $matches)){
                    $rContext->setError("Сервис временно недоступен");
                    $rContext->setFinished();
                    return true;
//                }elseif(preg_match("/<tr itemscope=\"itemscope\".*?<\/tr>/sim", $content, $matches)){
                }elseif(preg_match("/<div data-qa=\"resume-serp__results-search\">.*?<div class=\"supernova-footer\"></sim", $content, $matches)){
                    $resultData = new ResultDataList();
                    $dataParts = preg_split("/<div class=\"resume-search-item__content\"/",$matches[0]);
                    array_shift($dataParts);
                    if (preg_match("/ ничего не найдено</",$content)) {
                        $dataParts = array();
                    }
                    if (sizeof($dataParts)>=5) {
                        $error = "Найдено слишком много совпадений. Попробуйте указать в запросе место учебы, работы или жительства.";
                        if ($rContext->getLevel()==0)
                            $rContext->setError($error);
                        $rContext->setFinished();
                        return true;
                    }
                    foreach ($dataParts as $i => $dataPart) {
                        $dataPart = strtr($dataPart,array('&nbsp;'=>' ','<!-- -->'=>''));
                        $data = array();
//                        if(preg_match("/data-hh-resume-hash=\"([^\"]+)\"/", $dataPart, $matches)){
//                            $swapData['pdfpath'] = 'https://hh.ru/resume_converter/resume.pdf?hash='.$matches[1].'&type=pdf';
//                        }
                        if(preg_match("/resume-fullname\">([^<,]+)/", $dataPart, $matches)){
                            $data['name'] = new ResultDataField('string','Name', $matches[1], 'Имя', 'Имя');
                        }
                        if(preg_match("/resume-age\">([^<]+)/", $dataPart, $matches)){
                            $data['age'] = new ResultDataField('string','Age', $matches[1], 'Возраст', 'Возраст');
                        }
                        if(preg_match("/Регион<\/div><div class=\"resume-search-item__description-content\"[^>]+>(.*?)<\/div>/", $dataPart, $matches)){
                            $data['location'] = new ResultDataField('string','Location', trim(strip_tags($matches[1])), 'Местоположение', 'Местоположение');
                        }
                        if(preg_match("/ class=\"resume-userpic\"><img src=\"([^\"]+)/", $dataPart, $matches)){
//                            $data['photo'] = new ResultDataField('image','Photo', html_entity_decode($matches[1]), 'Фото', 'Фото');
                        }
//                        if(preg_match("/\"big\"\: \"([^\"]+)/", $dataPart, $matches)){
//                            $data['photo'] = new ResultDataField('image','Photo', 'https://hhcdn.ru'.html_entity_decode($matches[1]), 'Фото', 'Фото');
//                        }
                        if(preg_match("/ href=\"(\/resume\/[^\?]+)[^>]+>([^<>]+)<\/a>/", $dataPart, $matches)){
                            $data['occupation'] = new ResultDataField('string','Occupation', $matches[2], 'Должность', 'Должность');
//                            $path = 'https://hh.ru'.strtr($matches[1],array('/resume'=>'/archive')).'?sessionid='.$swapData['session']->id;
//                            $data['resume'] = new ResultDataField('hidden'.(sizeof($dataParts)<=5?':recursive':''),'Resume', $path, 'Резюме', 'Ссылка на резюме');
//                            $swapData['path'] = $path;
                        }
                        if(preg_match("/resume-compensation\">([^<]+)/", $dataPart, $matches) && strlen(trim($matches[1]))){
                            $data['compensation'] = new ResultDataField('string','Compensation', trim($matches[1]), 'Зарплата', 'Зарплата');
                        }
                        if(preg_match("/resume-expirience-sum\">([^<]+)/", $dataPart, $matches)){
                            $data['expiriencesum'] = new ResultDataField('float','ExpirienceSum', $matches[1], 'Стаж', 'Стаж');
                        }
                        if(preg_match("/resume-search-item__company-name\">([^<]+)<[^>]+>[^>]+>([^<]+)/", $dataPart, $matches)){
                            $data['employer'] = new ResultDataField('string','Employer', html_entity_decode($matches[1]), 'Место работы', 'Место работы');
                            $data['period'] = new ResultDataField('string','Period', html_entity_decode($matches[2]), 'Период работы', 'Период работы');
                        }
                        if(preg_match("/bloko-link-switch_inherited\">([^<]+)/", $dataPart, $matches)){
                           $data['position'] = new ResultDataField('string','Position', strip_tags($matches[1]), 'Занимаемая должность', 'Занимаемая должность');
                        }
                        if(preg_match("/<span class=\"resume-search-item__date\">Обновлено ([^<]+)/si", $dataPart, $matches)){
                            $data['updated'] = new ResultDataField('string','Updated', trim($matches[1]), 'Обновлено', 'Обновлено');
                        }
                        if(preg_match("/Был на сайте ([^<]+)/si", $dataPart, $matches)){
                            $data['lastvisited'] = new ResultDataField('string','LastVisited', trim($matches[1]), 'Последнее посещение', 'Последнее посещение');
                        }

                        if(preg_match_all("/<a data-qa=\"resume-serp__resume-another\" rel=\"nofollow\" href=\"(\/resume\/[^\?]+)[^\"]+\">([^<]+)<\/a>(.*?)<\/li>/si", $dataPart, $matches)){
//                        if(preg_match_all("/ href=\"(\/resume\/[^\?]+)[^>]+>([^<>]+)<\/a>/", $dataPart, $matches)){
                            foreach ($matches[1] as $key => $val) {
//                                $data = array();
                                $path = 'https://hh.ru'.strtr($val,array('/resume'=>'/archive')).'?sessionid='.$swapData['session']->id;
//                                $data['anotherresume'.$key] = new ResultDataField('hidden'.((0 && sizeof($dataParts)<=5 && $key<=5)?':recursive':''),'AnotherResume', $path, 'Другое резюме', 'Ссылка на другое резюме');
                                $data['anotheroccupation'.$key] = new ResultDataField('string','AnotherOccupation', $matches[2][$key], 'Другая должность', 'Другая должность');
                                if (trim($matches[3][$key]))
                                    $data['anothercompensation'] = new ResultDataField('string','AnotherCompensation', trim(strtr(strip_tags($matches[3][$key]),array(','=>'','&nbsp;'=>' '))), 'Другая зарплата', 'Друга зарплата');
                            }
                        }

                        $resultData->addResult($data);
                    }

                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
//                    $swapData['data'] = $data;
                } else {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                }
            }elseif(isset($swapData['path'])){
                file_put_contents('./logs/hh/hh_'.time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content."\r\n".implode("; ",curl_getinfo($rContext->getCurlHandler(),CURLINFO_COOKIELIST)));
//                $data = $swapData['data'];

                if(preg_match("/data-hh-resume-hash=\"([^\"]+)\"/", $content, $matches)){
                    $swapData['pdfpath'] = 'https://hh.ru/resume_converter/resume.pdf?hash='.$matches[1].'&type=pdf';
                    $swapData['hash'] = $matches[1];
                }
                if(preg_match("/<div class=\"resume__position__specialization\">([^<]+)/si", $content, $matches)){
                    $data['specialization'] = new ResultDataField('string','Specialization', $matches[1], 'Специализация', 'Специализация');
                }
                if(preg_match_all("/<li class=\"resume__position__specialization_item\">([^<]+)/si", $content, $matches)){
                    $data['specialization_details'] = new ResultDataField('string','SpecializationDetails', implode($matches[1],'; '), 'Детали специализации', 'Детали специализации');
                }
                if(preg_match("/itemprop=\"name\" data-qa=\"resume-personal-name\">([^<]+)/si", $content, $matches)){
                    $data['name'] = new ResultDataField('string','Name', $matches[1], 'Имя', 'Имя');
                }
                if(preg_match("/data-qa=\"resume-block-title-position\">([^<]+)/si", $content, $matches)){
                    $data['occupation'] = new ResultDataField('string','Occupation', $matches[1], 'Должность', 'Должность');
                }
                if(preg_match("/itemprop=\"birthDate\" data-qa=\"resume-personal-birthday\" content=\"([^\"]+)/si", $content, $matches)){
                    $birthdate=explode('-',$matches[1]);
                    $data['birthdate'] = new ResultDataField('string','BirthDate', $birthdate[2].'.'.$birthdate[1].'.'.$birthdate[0], 'Дата рождения', 'Дата рождения');
                }
                if(preg_match("/data-qa=\"resume-personal-age\">([^<]+)/si", $content, $matches)){
                    $data['age'] = new ResultDataField('string','Age', $matches[1], 'Возраст', 'Возраст');
                }
                if(preg_match("/itemprop=\"gender\" data-qa=\"resume-personal-gender\">([^<]+)/si", $content, $matches)){
                    $data['gender'] = new ResultDataField('string','Gender', $matches[1], 'Пол', 'Пол');
                }
                if(preg_match("/itemprop=\"addressLocality\" data-qa=\"resume-personal-address\">([^<]+)/si", $content, $matches)){
                    $data['city'] = new ResultDataField('string','City', $matches[1], 'Город', 'Город');
                }
                if(preg_match("/<span data-qa=\"resume-personal-metro\" style=\"color:[^\"]+\">м. ([^<]+)/si", $content, $matches)){
                    $data['metro'] = new ResultDataField('string','Metro', $matches[1], 'Метро', 'Метро');
                }
                if(preg_match_all("/itemprop=\"telephone[^>]+>([^<]+)/si", $content, $matches)){
                    foreach($matches[1] as $key => $val) {
                        $data['phone'.($key?$key:'')] = new ResultDataField('phone','Phone', trim($val), 'Телефон', 'Телефон');
                    }
                }
                if(preg_match_all("/itemprop=\"email[^>]+>([^<]+)/si", $content, $matches)){
                    foreach($matches[1] as $key => $val) {
                        $data['email'.($key?$key:'')] = new ResultDataField('email','Email', trim($val), 'Email', 'Email');
                    }
                }
                if(preg_match("/m-siteicon_skype\">([^<]+)/si", $content, $matches)){
                    $data['skype'] = new ResultDataField('skype','Skype', $matches[1], 'Skype', 'Skype');
                }
                if(preg_match("/<a href=\"([^\"]+)\" target=\"_blank\" itemprop=\"url\"/si", $content, $matches)){
                    $data['website'] = new ResultDataField('url:recursive','Website', trim(strip_tags($matches[1])), 'Website', 'Website');
                }
                if(preg_match("/Опыт работы ([^<]+)/", $content, $matches)){
                    $data['expirience'] = new ResultDataField('string','Expirience', html_entity_decode(strip_tags($matches[1])), 'Опыт работы', 'Опыт работы');
                }
                if(preg_match("/data-qa=\"resume-block-salary\">([^<]+)/si", $content, $matches)){
                    $data['compensation'] = new ResultDataField('string','Compensation', $matches[1], 'Зарплата', 'Зарплата');
                }
                if(preg_match("/Резюме обновлено ([^<]+)/", $content, $matches)){
                    $data['updated'] = new ResultDataField('string','Updated', $matches[1], 'Обновлено', 'Обновлено');
                }
                if(preg_match("/<img src=\"([^\"]+)\" alt=\"\" class=\"resume-photo__image/", $content, $matches)){
                    $swapData['photopath'] = 'https://hh.ru'.html_entity_decode($matches[1]);
                }

                if (isset($swapData['pdfpath'])) {
                    $swapData['data'] = $data;
                    unset($swapData['path']);
                    $rContext->setSwapData($swapData);
                    if (!isset($swapData['photopath']))
                        $rContext->setSleep(3);
                } else {
                    $resultData = new ResultDataList();
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                }
                return true;
            }elseif(isset($swapData['photopath'])){
                $data = $swapData['data'];
                $name = 'logs/hh/hh_'.$swapData['hash'].'_'.time().'.jpg';
                file_put_contents('./'.$name,$content);
                $data['photo'] = new ResultDataField('image', 'Photo', $serviceurl.$name, 'Фото', 'Фото');
                $swapData['data'] = $data;
                unset($swapData['photopath']);
                $rContext->setSwapData($swapData);
                $rContext->setSleep(3);
                return true;
            }elseif(isset($swapData['pdfpath'])){
                $data = $swapData['data'];
                $name = 'logs/hh/hh_'.$swapData['hash'].'_'.time().'.pdf';
                file_put_contents('./'.$name,$content);
                $data['pdf'] = new ResultDataField('url', 'PDF', $serviceurl.$name, 'PDF', 'PDF');
//                $swapData['data'] = $data;
//                unset($swapData['pdfpath']);
//                $swapData = $rContext->setSwapData($swapData);
//                return true;
//            }  else {
                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            }
            $rContext->setSwapData($swapData);
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>5)
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