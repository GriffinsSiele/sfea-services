<?php

class EGRULPlugin implements PluginInterface
{
    public function getName()
    {
        return 'egrul';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск в ЕГРЮЛ/ЕГРИП',
            'egrul_person' => 'Проверка на наличие статуса индивидуального предпринимателя и поиск аффилированных организаций',
            'egrul_org' => 'Поиск организации в ЕГРЮЛ',
            'egrul_ip' => 'Поиск индивидуального предпринимателя в ЕГРИП',
            'egrul_daughter' => 'Поиск дочерних организаций в ЕГРЮЛ',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск в ЕГРЮЛ/ЕГРИП';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=9 AND lasttime<DATE_SUB(now(), INTERVAL 5 SECOND) ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=9 AND request_id=".$reqId." ORDER BY lasttime limit 1");

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
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used>=1 AND id=".$sessionData->id);
/*
                if (!$row->proxyid) {
                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND country='ru' AND id NOT IN (select proxyid FROM session WHERE sourceid=9 AND proxyid IS NOT NULL) ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetch_object();
                        if ($row) {
                            $sessionData->proxyid = $row->proxyid;
                            $sessionData->proxy = $row->proxy;
                            $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                            $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
                            $mysqli->query("UPDATE isphere.session SET proxyid=".$row->proxyid." WHERE id=".$sessionData->id);
                        }
                    }
                }
*/
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],6);

        if(!isset($initData['inn']) && !isset($initData['ogrn']) /*&& !isset($initData['name']) && (!isset($initData['first_name']) || !isset($initData['last_name']))*/) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ИНН, ОГРН, наименование или ФИО)');

            return false;
        }
/*
        if(isset($initData['ogrn']) && !isset($swapData['ogrn'])) {
            $swapData['ogrn']=$initData['ogrn'];
            $rContext->setSwapData($swapData);
        }
*/
/*
        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен');
        return false;
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        if (isset($initData['ogrn']))
            $swapData['ogrn'] = $initData['ogrn'];

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
//        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData(/*$swapData['iteration']<=5*/);
            if(!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration']>=20)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }
                return false;
            }
            $rContext->setSwapData($swapData);
//        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $host = 'https://zachestnyibiznes.ru';
        if (!isset($swapData['ogrn'])) {
            $url = $host.'/search?query='.(isset($initData['inn'])?($checktype=='daughter'?'est_':'').$initData['inn']:'aff_'.mb_strtoupper(isset($initData['name'])?$initData['name']:$initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic'])?' '.$initData['patronymic']:'')));
            $swapData['referer'] = $url;
            $rContext->setSwapData($swapData);
        } else {
            $url = $host.'/company/'.(strlen($swapData['ogrn'])==15?'ip':'ul').'/'.$swapData['ogrn'];
//            $url = 'https://src2.i-sphere.ru/zach/?link='.urlencode($host.'/company/'.(strlen($swapData['ogrn'])==15?'ip':'ul').'/'.$swapData['ogrn']);
//            $swapData['session']->proxy = false;
        }
        $header = array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
            'DNT: 1',
            'Upgrade-Insecure-Requests: 1',
        );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, isset($swapData['referer'])?$swapData['referer']:$host.':');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_ENCODING, '');
//        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
        if ($swapData['session']->proxy) {
            curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
            }
        }
        $rContext->setCurlHandler($ch);
//        echo "$url\n";
        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],6);

        $error = false; //($swapData['iteration']>5) ? curl_error($rContext->getCurlHandler()) : false;
        if (strpos($error,'timed out') || strpos($error,'refused') || strpos($error,'reset by peer')) {
           $error = 'Невозможно подключиться к сервису';
        }
        if(!$error) {
            $srccontent = curl_multi_getcontent($rContext->getCurlHandler());
            $content = str_replace("<div class=\"helloparsers\">По данным портала <a href=\"https://zachestnyibiznes.ru\">ЗАЧЕСТНЫЙБИЗНЕС</a></div>","",$srccontent);
            $content = preg_replace("/<div class=\"helloparsers\"><a[^>]*>По данным портала <\/a><a href=\"https:\/\/zachestnyibiznes.ru\">ЗАЧЕСТНЫЙБИЗНЕС<\/a><\/div>/","",$content);
            $content = preg_replace("/&nbsp;/","",$content);
            $content = preg_replace("/<svg[^>]*>.*?<\/svg>/sim","",$content);
//            if (preg_match("/\$\.each\(\$\(\"\.([\d]+)\"\)\, function\(\)\{/sim",$content,$matches)) {
            if (preg_match_all("/each[^\"]+\"\.([0-9]+)\"/",$content,$matches)) {
                foreach($matches[1] as $class) {
                    $content = preg_replace_callback("/<span class=\"".$class."\">[^<]+<\/span>/",
                        create_function(
                            '$matches',
                            'return base64_decode(strip_tags($matches[0]));'
                        ),
                    $content);
                }
            }
            if (strlen($content)==0 || strpos($content,"Checking your browser before accessing")!==false || strpos($content,"Lame Page")!==false) {
                if ($content) file_put_contents('./logs/egrul/egrul_err_'.time().'.html',$content);
                if (isset($swapData['iteration']) && $swapData['iteration']>5)
                    $error = "Ошибка обработки запроса";
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=6,statuscode='checking',unlocktime=date_add(now(),interval 1 year) WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
            } elseif (strpos($content,"автоматизированные запросы")!==false) {
                file_put_contents('./logs/egrul/egrul_captcha_'.time().'.html',$content);
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=6,statuscode='captcha',unlocktime=date_add(now(),interval 1 year) WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
            } elseif (strpos($content,"IP адрес")!==false) {
                file_put_contents('./logs/egrul/egrul_locked_'.time().'.html',$content);
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=6,statuscode='locked',unlocktime=date_add(now(),interval 6 hour) WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
            } elseif (!isset($swapData['ogrn'])) {
                file_put_contents('./logs/egrul/egrul_search_'.time().'.html',$content);
                $resultData = new ResultDataList();

//                if(preg_match("/>(Показано: [\d]+ из [\d]+.*?)<\/div><\/div><\/div><\/div><\/div><\/div>/sim", $content, $matches)){
//                if(preg_match("/(найдено [\d]+.*?)<\/div><\/div><\/div><\/div><\/div><\/div>/sim", $content, $matches)){
                if(preg_match("/(найдено [\d]+.*?)<form id=\"form-filters\"/sim", $content, $matches)){
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);

                    $parts = preg_split("/<div class=\"row\">/",$matches[1]);
                    array_shift($parts);
                    $found = sizeof($parts);
                    if ($found > 100) {
                        $rContext->setFinished();
                        $rContext->setError("Найдено слишком много совпадений");
                        return false;
                    } elseif ($found) foreach ($parts as $i => $dataPart) {
                        file_put_contents('./logs/egrul/egrul_search_part'.$i.'_'.time().'.html',$dataPart);
                        $data = array();
                        if(preg_match("/<a href=\"\/company\/ul\/([0-9]+)_([0-9]+)[^>]+>([^<>]*)<\/a>/smu", $dataPart, $matches)){
                            if ($checktype=='org' && sizeof($parts)==1 && isset($initData['inn']) && strlen($initData['inn'])==10) {
                                $swapData['ogrn'] = trim($matches[1]);
                                $rContext->setSwapData($swapData);
                                $rContext->setSleep(3);
                                return true;
                            } else {
                                $data['ogrn'] = new ResultDataField('string','OGRN', trim($matches[1]), 'ОГРН', 'ОГРН');
                                $data['inn'] = new ResultDataField('string','INN', trim($matches[2]), 'ИНН', 'ИНН');
                                $data['name'] = new ResultDataField('string','Name', trim($matches[3]), 'Наименование', 'Наименование');
                                if(preg_match("/<b class=\"text-[^\"]+\">([^<]+)<\/b>/", $dataPart, $matches)){
                                    $data['orgstatus'] = new ResultDataField('string','OrgStatus', trim($matches[1]), 'Статус', 'Статус');
                                }
                                if(preg_match("/<p [^>]+>\n([^<]+)<br>\n<span class=\"collapse\"/", $dataPart, $matches)){
                                    $data['head'] = new ResultDataField('string','Head', trim($matches[1]), 'Руководитель', 'Руководитель');
                                }
                            }
                        }
                        if(preg_match("/<a href=\"\/company\/ip\/([0-9]+)_([0-9]+)[^>]+>[\s]+([А-Я][^А-Я]+)([А-Я][^<]+)<\/a>/smu", $dataPart, $matches)){
//                        if(preg_match("/ref=\"\/company\/ip\/([0-9]+)_([0-9]+)[^>]+>[\s]+([А-Я][^А-Я]+[^<]+)/smu", $dataPart, $matches)){
/*
                            if (sizeof($parts)==1 && isset($initData['inn']) && strlen($initData['inn'])==12) {
                                $swapData['ogrn'] = trim($matches[1]);
                                $rContext->setSwapData($swapData);
                                $rContext->setSleep(3);
                                return true;
                            } else {
*/
                                $data['ogrn'] = new ResultDataField('string','OGRN', trim($matches[1]), 'ОГРН', 'ОГРН');
                                $data['inn'] = new ResultDataField('string','INN', trim($matches[2]), 'ИНН', 'ИНН');
                                $data['iptype'] = new ResultDataField('string','IPType', trim($matches[3]), 'Тип ИП', 'Тип индивидуального предпринимателя');
                                $data['ipname'] = new ResultDataField('string','IPName', trim($matches[4]), 'Имя ИП', 'Имя индивидуального предпринимателя');
                                if(preg_match("/<b class=\"text-[^\"]+\">([^<]+)<\/b>/", $dataPart, $matches)){
                                    $data['ipstatus'] = new ResultDataField('string','IPStatus', trim($matches[1]), 'Статус', 'Статус');
                                }
/*
                            }
*/
                        }
                        if(preg_match("/>[\s]+<\/p>[\s]+<p [^>]+>([^<]+)<\/p>/smu", $dataPart, $matches)){
                            $data['address'] = new ResultDataField('string','Address', trim($matches[1]), 'Адрес', 'Адрес');
                        }
                        if(preg_match("/>Дата регистрации<[^>]+>([^<]+)/", $dataPart, $matches)){
                            $data['regdate'] = new ResultDataField('string','RegDate', trim($matches[1]), 'Дата регистрации', 'Дата регистрации');
                        }
                        if(preg_match("/<\/div>[\s]+<div [^>]+>([^<]+)<\/div>([^<]+)<\/div>[\s]+<\/div>/sim", $dataPart, $matches)){
                            $data['okved'] = new ResultDataField('string','OKVED', trim($matches[1]), 'Основной код ОКВЭД', 'Основной код ОКВЭД');
                            $data['okvedname'] = new ResultDataField('string','OKVEDName', trim($matches[2]), 'Основной вид деятельности', 'Основной вид деятельности');
                        }
                        if (sizeof($data)) {
                            $data['Type'] = new ResultDataField('string','Type', $checktype=='daughter'?'daughter':'affiliated', 'Тип записи', 'Тип записи');
                            $resultData->addResult($data);
                        }
                    }
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } else {
                    $mysqli->query("UPDATE isphere.session SET statuscode='invalid',unlocktime=date_add(now(),interval 1 hour) WHERE id=" . $swapData['session']->id);
                    if ($content) file_put_contents('./logs/egrul/egrul_err_'.time().'.html',$content);
                    $error = "Некорректный ответ источника";
                }
            } else {
                file_put_contents('./logs/egrul/egrul_'.time().'.html',$content);
                $resultData = new ResultDataList();
                if (strlen($swapData['ogrn'])==15) {
                    if(preg_match("/Вид предпринимательства[\s]+<\/div>[\s]+<div [^>]+>[\s]+<p [^>]+>([^<]+)/smu", $content, $matches)){
                        $data['iptype'] = new ResultDataField('string','IPType', trim($matches[1]), 'Тип ИП', 'Тип индивидуального предпринимателя');
                    }
                    if(preg_match("/itemprop=\"taxID\">([^<]+)<\/a>/smu", $content, $matches)){
                        $data['ipname'] = new ResultDataField('string','IPName', trim($matches[1]), 'Имя ИП', 'Имя индивидуального предпринимателя');
                    }
                    if(preg_match("/<span class=\"badge [^>]+>([^<]+)/sim", $content, $matches)){
                        $data['ipstatus'] = new ResultDataField('string','IPStatus', trim($matches[1]), 'Статус', 'Статус');
                    }
                    if(preg_match("/<b[^>]*>ОГРНИП<\/b>.*?<span[^>]*>([\d]+)/sim", $content, $matches)){
                        $data['ogrn'] = new ResultDataField('string','OGRN', trim($matches[1]), 'ОГРН', 'ОГРН');
                    }
                    if(preg_match("/<b[^>]*>ИНН<\/b>.*?<span[^>]*>([\d]+)/sim", $content, $matches)){
                        $data['inn'] = new ResultDataField('string','INN', trim($matches[1]), 'ИНН', 'ИНН');
                    }
                } else {
                    if(preg_match("/<h1[^>]*>([^<]+)/sim", $content, $matches)){
                        $data['orgname'] = new ResultDataField('string','OrgName', trim($matches[1]), 'Организация', 'Организация');
                    }
                    if(preg_match("/<h2[^>]*>([^<]+)/", $content, $matches)){
                        $data['orgfullname'] = new ResultDataField('string','OrgFullName', trim($matches[1]), 'Полное наименование', 'Полное наименование');
                    }
                    if(preg_match("/<h2[^<]+<span[^<]+<span[^>]+>([^<]+)<\/span>/sim", $content, $matches)){
                        $data['orgstatus'] = new ResultDataField('string','OrgStatus', trim($matches[1]), 'Статус', 'Статус');
                    }
                    if(preg_match("/<b[^>]*>ОГРН<\/b>.*?<span[^>]*>([\d]+)/sim", $content, $matches)){
                        $data['ogrn'] = new ResultDataField('string','OGRN', trim($matches[1]), 'ОГРН', 'ОГРН');
                    }
                    if(preg_match("/<b[^>]*>ИНН<\/b>.*?<span[^>]*>([\d]+)/sim", $content, $matches)){
                        $data['inn'] = new ResultDataField('string','INN', trim($matches[1]), 'ИНН', 'ИНН');
                    }
                }
                if(preg_match("/<b[^>]*>Дата регистрации<\/b>.*?<span[^>]*>([0-9\.]+)<\/span>/sim", $content, $matches)){
                    $data['regdate'] = new ResultDataField('string','RegDate', trim($matches[1]), 'Дата регистрации', 'Дата регистрации');
                }
                if(preg_match("/<b[^>]*>Дата ликвидации<\/b>.*?<span[^>]*>([0-9\.]+)<\/span>/sim", $content, $matches) || preg_match("/Дата прекращения деятельности.*?<a [^>]+>([0-9\.]+)/smu", $content, $matches)){
                    $data['closedate'] = new ResultDataField('string','CloseDate', trim($matches[1]), 'Дата ликвидации', 'Дата ликвидации');
                }
                if(preg_match("/<span itemprop=\"address\">([^<]+)<[^<]+<span[^>]+>[^\d]+([\d\.]+)/sim", $content, $matches)){
                    $data['address'] = new ResultDataField('string','Address', trim($matches[1]), 'Адрес', 'Адрес');
                    $data['addressdate'] = new ResultDataField('string','AddressDate', trim($matches[2]), 'Дата изменения адреса', 'Дата изменения адреса');
                }
                if(preg_match("/Местоположение[\s]*<\/div>[\s]*<div [^>]+>([^<]+)/sim", $content, $matches) && trim($matches[1])){
                    $data['location'] = new ResultDataField('string','Location', trim($matches[1]), 'Местоположение', 'Местоположение');
                }
                if(preg_match("/сведения об юридическом адресе признаны недостоверными/", $content, $matches)){
                    $data['unreliableaddress'] = new ResultDataField('string','UnrelialbleAddress', 'В результате проверки, сведения об юридическом адресе признаны недостоверными (по данным ФНС)', 'Недостоверный адрес', 'Недостоверный адрес');
                }
                if(preg_match("/<b[^>]*>КПП<\/b>.*?<span[^>]*>([^<]+)/sim", $content, $matches)){
                    $data['kpp'] = new ResultDataField('string','KPP', trim($matches[1]), 'КПП', 'КПП');
                }
                if(preg_match("/<b[^>]*>ОКПО<\/b>.*?<span[^>]*>([^<]+)/sim", $content, $matches)){
                    $data['okpo'] = new ResultDataField('string','OKPO', trim($matches[1]), 'ОКПО', 'ОКПО');
                }
                if(preg_match("/<b[^>]*>ОКТМО<\/b>.*?<span[^>]*>([^<]+)/sim", $content, $matches)){
                    $data['oktmo'] = new ResultDataField('string','OKТМO', trim($matches[1]), 'ОКТМО', 'ОКТМО');
                }
                if(preg_match("/ОКАТО.*?<span[^>]*>([^<]+)/sim", $content, $matches)){
                    $data['okato'] = new ResultDataField('string','OKATO', trim($matches[1]), 'ОКАТО', 'ОКАТО');
                }
                if(preg_match("/ОКОГУ.*?<span[^>]*>([^<]+)/sim", $content, $matches)){
                    $data['okogu'] = new ResultDataField('string','OKOGU', trim($matches[1]), 'ОКОГУ', 'ОКОГУ');
                }
                if(preg_match("/ОКОПФ.*?<span[^>]*>([^<]+)/sim", $content, $matches)){
                    $data['okopf'] = new ResultDataField('string','OKOPF', trim($matches[1]), 'ОКОПФ', 'ОКОПФ');
                }
                if(preg_match("/ОКФС.*?<span[^>]*>([^<]+)/sim", $content, $matches)){
                    $data['okfs'] = new ResultDataField('string','OKFS', trim($matches[1]), 'ОКФС', 'ОКФС');
                }
                if(preg_match("/Уставный капитал<\/p>[\s]*<\/div>[\s]*<div [^>]>[\s]*<p [^>]>([^0-9\s]+)/smu", $content, $matches)){
                    $data['capital'] = new ResultDataField('float','Capital', strtr(trim($matches[1]),array(' '=>'',','=>'.')), 'Уставный капитал', 'Уставный капитал');
                }
                if(preg_match("/<p [^>]*>Налоговый орган.*?<\/p><p [^>]*>([^<]+)<br>\s<small>[^:]+:([^<]+)/sim", $content, $matches)){
                    $data['fns'] = new ResultDataField('string','FNS', trim($matches[1]), 'Подразделение ФНС', 'Подразделение ФНС');
                    $data['fnsdate'] = new ResultDataField('string','FNSDate', trim($matches[2]), 'Дата постановки на учет в ФНС', 'Дата постановки на учет в ФНС');
                }
/*
                if(preg_match("/id=\"pfr\">([^<]+)<\/span[^<]+<\/td[^<]+<td>([^<]+)/", $content, $matches)){
                    $data['pfr'] = new ResultDataField('string','PFRNum', trim($matches[1]), 'Номер ПФР', 'Регистрационный номер ПФР');
                    $data['pfrdate'] = new ResultDataField('string','PFRDate', trim($matches[2]), 'Дата регистрации в ПФР', 'Дата регистрации в ПФР');
                }
                if(preg_match("/id=\"fss\">([^<]+)<\/span[^<]+<\/td[^<]+<td>([^<]+)/", $content, $matches)){
                    $data['fss'] = new ResultDataField('string','FSSNum', trim($matches[1]), 'Номер ФСС', 'Регистрационный номер ФСС');
                    $data['fssdate'] = new ResultDataField('string','FSSDate', trim($matches[2]), 'Дата регистрации в ФСС', 'Дата регистрации в ФСС');
                }
*/
                if(preg_match("/<p [^>]*>Руководитель Юридического Лица.*?<\/p><p [^>]+><a [^>]+>([^<]+)<\/a>/smu", $content, $matches)){
//                    $data['headtitle'] = new ResultDataField('string','HeadTitle', trim(strip_tags($matches[1])), 'Должность руководителя', 'Должность руководителя');
                    $data['head'] = new ResultDataField('string','Head', trim(strip_tags($matches[1])), 'Руководитель', 'Руководитель');
                }
                if(preg_match("/<p [^>]*>Руководитель Юридического Лица<\/p>.*?ИНН <a[^>]*>([^<]+)<\/a>\s/sim", $content, $matches)){
                    $data['headinn'] = new ResultDataField('string','HeadINN', trim($matches[1]), 'ИНН руководителя', 'ИНН руководителя');
                }
                if(preg_match("/<p [^>]*>Руководитель Юридического Лица<\/p>.*?действует с ([^<]+)/sim", $content, $matches)){
                    $data['headdate'] = new ResultDataField('string','HeadDate', trim($matches[1]), 'Дата вступления в должность', 'Дата вступления в должность');
                }
                if(preg_match("/<b[^>]*>Основной вид деятельности<\/b>.*?<p[^>]*>[\s]+([0-9\.]+)[\s]+([^<]+)/sim", $content, $matches)){
                    $data['okved'] = new ResultDataField('string','OKVED', trim($matches[1]), 'Основной код ОКВЭД', 'Основной код ОКВЭД');
                    $data['okvedname'] = new ResultDataField('string','OKVEDName', trim($matches[2]), 'Основной вид деятельности', 'Основной вид деятельности');
                }
                $data['Type'] = new ResultDataField('string','Type', strlen($swapData['ogrn'])==15?'ip':'org', 'Тип записи', 'Тип записи');
                if (sizeof($data)) $resultData->addResult($data);
/*
                if(preg_match("/<table class=\"table text-left okved-table\">(.*?)<\/table>/sim", $content, $matches)){
                    $parts = preg_split("/<tr>/",$matches[1]);
                    array_shift($parts);
                    foreach ($parts as $i => $dataPart) {
                        $data = array();
                        if(preg_match("/<td>([^<]+)<\/td><td>([^<]+)/sim", $dataPart, $matches)){
                            $data['okved'] = new ResultDataField('string','OKVED', trim($matches[1]), 'Дополнительный код ОКВЭД', 'Дополнительный код ОКВЭД');
                            $data['okvedname'] = new ResultDataField('string','OKVEDName', trim($matches[2]), 'Дополнительный вид деятельности', 'Дополнительный вид деятельности');
                            $data['Type'] = new ResultDataField('string','Type', 'okved', 'Тип записи', 'Тип записи');
                        }
                        if (sizeof($data)) $resultData->addResult($data);
                    }
                }
*/
                if(preg_match("/<table class=\"[a-z\-\s]*founders-table[a-z\-\s]*\">(.*?)<\/table>/sim", $content, $matches)){
                    $parts = preg_split("/<tr>/",$matches[1]);
                    array_shift($parts);
                    array_shift($parts);
                    foreach ($parts as $i => $dataPart) {
                        $data = array();
                        if(preg_match("/\"Учредитель:\">(.*?)<\/td>/sim", $dataPart, $matches)){
                            $data['owner'] = new ResultDataField('string','Owner', trim(strip_tags($matches[1])), 'Владелец', 'Владелец');
                        }
                        if(preg_match("/\"ИНН: \">(.*?)<\/td>/", $dataPart, $matches)){
                            $data['ownerinn'] = new ResultDataField('string','OwnerINN', trim(strip_tags($matches[1])), 'ИНН владельца', 'ИНН владельца');
                        }
                        if(preg_match("/\"Доля \(\%\): \">([^\%]+)\%<\/td>/sim", $dataPart, $matches)){
                            $data['ownerpercent'] = new ResultDataField('float','OwnerPercent', trim($matches[1]), 'Доля %', 'Доля %');
                        }
                        if(preg_match("/\"Доля \(руб\.\): \">([^<]+)<\/td>/sim", $dataPart, $matches)){
                            $data['ownertotal'] = new ResultDataField('float','OwnerTotal', strtr(trim($matches[1]),array(' '=>'',','=>'.')), 'Стоимость доли', 'Стоимость доли');
                        }
                        if(preg_match("/\"Дата: \">([\d]{2}\.[\d]{2}\.[\d]{4})<\/td>/sim", $dataPart, $matches)){
                            $data['ownerdate'] = new ResultDataField('string','OwnerDate', trim($matches[1]), 'Дата начала владения', 'Дата начала владения');
                        }
                        $data['Type'] = new ResultDataField('string','Type', 'owner', 'Тип записи', 'Тип записи');
                        if (sizeof($data)) $resultData->addResult($data);
                    }
                }

                $rContext->setResultData($resultData);
                $rContext->setFinished();

                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
            }
        }
        $rContext->setSwapData($swapData);

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>10)
            $error='Превышено количество попыток получения ответа';

        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        $rContext->setSleep(3);
        return true;
    }
}

?>