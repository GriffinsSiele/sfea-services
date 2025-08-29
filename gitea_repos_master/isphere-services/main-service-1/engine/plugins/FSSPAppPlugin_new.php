<?php

class FSSPAppPlugin implements PluginInterface
{
    public function getName()
    {
        return 'fssp';
    }

    public function getTitle()
    {
        return 'ФССП - исполнительные производства';
    }

    public function getSessionData($generate=1)
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;
/*
        if ($generate) {
            $result = $mysqli->query("SELECT COUNT(*) count FROM isphere.session WHERE sessionstatusid=2 AND sourceid=13 AND unix_timestamp(now())-unix_timestamp(lasttime)>5");
            if($result) {
                $row = $result->fetch_object();
                $generate = ($row && $row->count>0);
            } else {
                $generate = false;
                return null;
            }
        }
*/
        $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=13 AND token>'' AND unix_timestamp(now())-unix_timestamp(lasttime)>5 ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=13 AND request_id=".$reqId." ORDER BY lasttime limit 1");
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
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;
                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL WHERE id=".$sessionData->id);
/*
                if ($generate) {
                    $sessionData->id = 0;
                    $sessionData->token = substr($row->token,5).rand(10000,99999);
                } else {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='exhausted',sessionstatusid=3 WHERE used>=10 AND id=".$row->id);
                }
*/
            }
        }

        return $sessionData;
    }


    public function prepareRequest(&$rContext)
    {

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения)');

            return false;
        }

        if(isset($initData['last_name']) && isset($initData['first_name']) && preg_match("/[^А-Яа-яЁё\s\-\.]/ui", $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic'])?' '.$initData['patronymic']:''))){
            $rContext->setFinished();
            $rContext->setError('Имя может содержать только русские буквы');
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

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

        $url = 'https://api.fssp.gov.ru/api/v2/search?';
        $params['type']='form';
        $params['udid']='';
        $params['ver']='32';
        $params['session_id'] = $swapData['session']->token;
        $params['region_id']=isset($initData['region_id'])?$initData['region_id']:'0';
        $params['last_name']=isset($initData['last_name']) && $initData['last_name']?$initData['last_name']:'-';
        $params['first_name']=isset($initData['first_name']) && $initData['first_name']?$initData['first_name']:'-';
        $params['patronymic']=isset($initData['patronymic'])?$initData['patronymic']:'';
        $params['date']=isset($initData['date'])?date('d.m.Y',strtotime($initData['date'])):'';

        foreach($params as $key => $value)
            $url .= $key.'='.urlencode($value).'&';

        curl_setopt($ch,CURLOPT_URL,$url);
//        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);

        if ($swapData['session']->proxy) {
            curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
            }
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $swapData = $rContext->getSwapData();

        $content = curl_multi_getcontent($rContext->getCurlHandler());
        file_put_contents('./logs/fsspapp/fssp_'.time().'_'.$swapData['iteration'].'.txt', $content);

        $error = false; //$swapData['iteration']>=5 ? curl_error($rContext->getCurlHandler()) : false;

        $data = json_decode($content, true);

        $initData = $rContext->getInitData();

        if(isset($data['data']['captcha'])) {
            if (isset($swapData['session']) && $swapData['session']->id)
                $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='exhausted',sessionstatusid=3 WHERE id=" . $swapData['session']->id);
            unset($swapData['session']);
        } elseif (isset($data['error_name']) && $data['error_name']=='Большое количество запросов') {
            if (isset($swapData['session']) && $swapData['session']->id)
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='limit' WHERE id=" . $swapData['session']->id);
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='exhausted',sessionstatusid=3 WHERE id=" . $swapData['session']->id);
            unset($swapData['session']);
        } elseif (isset($data['error_name']) && $data['error_name']=='Успешно' && isset($data['data']['list'])) {
            if (isset($swapData['session']) && $swapData['session']->id)
                $mysqli->query("UPDATE isphere.session SET statuscode='success', success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);

            $resultData = new ResultDataList();

            $data = $data['data'];
            $found = sizeof($data['list']);
            if (($found >= 100) && !isset($initData['date']) && !isset($initData['region_id'])) {
                $rContext->setError("Найдено слишком много совпадений ($found). Попробуйте указать в запросе дату рождения или регион.");
                $rContext->setFinished();
                return true;
            }

            foreach($data['list'] as $element)
            {
                if(isset($element['name']))
                {
                    if(count($element)<8)
                        continue;

                    $record = array();

                    if (preg_match("/^(.*?) (\d{2}\.\d{2}\.\d{4})(.*?)$/",$element['name'],$matches)) {
                        $debtor = trim($matches[1]);
                        $debtor_birthdate = trim($matches[2]);
                        $debtor_birthplace = trim($matches[3]);
                    } elseif (strpos($element['name'],',')>12) {
                        $debtor = trim(substr($element['name'],0,strpos($element['name'],',')-11));
                        $debtor_birthdate = substr($element['name'],strpos($element['name'],',')-10,10);
                        $debtor_birthplace = trim(substr($element['name'],strpos($element['name'],',')+1));
                    } else {
                        $debtor = trim($element['name']);
                        $debtor_birthdate = '';
                        $debtor_birthplace = '';
                    }
                    $case = explode(' от ', $element['exe_production']);
                    $doc = $element['details'];
                    $bailiff_doc = '';
                    $close = explode(', ', $element['ip_end']);
                    $subjects = explode('руб. ', $element['subject']);
                    if (preg_match("/^(.*?)Исполнительский сбор:/",$subjects[0],$matches) || preg_match("/^(.*?)Задолженность по расходам:/",$subjects[0],$matches) || preg_match("/^(.*?)Штраф СПИ:/",$subjects[0],$matches)) {
                        $subject = array($matches[1]);
                        $subjects[0] = substr($subjects[0],strlen($matches[1]));
                    } else {
                        $subject = explode(': ', $subjects[0]);
                        array_shift($subjects);
                    }
                    $department = $element['department'];
                    if (preg_match("/^(.*?) \d{6},/",$department,$matches)) {
                        $department_address = trim(substr($department,strlen($matches[1])+1));
                        $department = $matches[1];
                    }
                    $bailiff = explode('<br>', $element['bailiff']);
                    if (preg_match("/^([А-Яа-яЁё\-\.\s]+) [0-9\+\-\(\)\s]+$/",$bailiff[0],$matches)) {
                        $bailiff_name = trim($matches[1]);
                        $bailiff[0] = substr($bailiff[0],strlen($matches[1])+1);
                    } else {
                        $bailiff_name = trim($bailiff[0]);
                        array_shift($bailiff);
                    }

                    $record['name'] = new ResultDataField('string','Debtor', strtr($debtor,array(' '=>'')), 'Должник', 'Должник');
                    if ($debtor_birthdate)
                        $record['debtor_birthday'] = new ResultDataField('string','DebtorBirthday', $debtor_birthdate, 'Дата рождения', 'Дата рождения должника');
                    if ($debtor_birthplace)
                        $record['debtor_birthplace'] = new ResultDataField('string','DebtorBirthplace', $debtor_birthplace, 'Место рождения', 'Место рождения должника');

                    $record['case_num'] = new ResultDataField('string','CaseNumber', $case[0], 'Номер ИП', 'Номер исполнительного производства');
                    $record['case_date'] = new ResultDataField('string','CaseDate', substr(trim($case[1]),0,10), 'Дата ИП', 'Дата исполнительного производства');
                    if (strlen(trim($case[1]))>11)
                        $record['summary_case_num'] = new ResultDataField('string','SummaryCaseNumber', substr(trim($case[1]),11), 'Номер сводного ИП', 'Номер сводного исполнительного производства');

                    $record['doc_text'] = new ResultDataField('string','DocText', trim(strip_tags($doc)), 'Реквизиты документа', 'Реквизиты исполнительного документа');
                    $record['doc_type'] = new ResultDataField('string','DocType', trim(strip_tags(substr($doc,0,strpos($doc,' от ')))), 'Вид документа', 'Вид исполнительного документа');
                    $doc = trim(substr($doc,strpos($doc,' от ')+6));
                    if (preg_match("/^([0-9\.]+) /",$doc,$matches)) {
                        $record['doc_date'] = new ResultDataField('string','DocDate', $matches[1], 'Дата документа', 'Дата исполнительного документа');
                        $doc = trim(substr($doc,strlen($matches[1])+1));
                    }
                    
                    if (preg_match("/^№ ([^\s]+ №[\s]*[^\s]+) /",$doc,$matches) || preg_match("/^№ ([А-Яа-яЁё]+ [\d]+)/",$doc,$matches) || preg_match("/^№ ([^\s]+) /",$doc,$matches) || preg_match("/^№ (.*?)$/",$doc,$matches)) {
                        $record['doc_num'] = new ResultDataField('string','DocNumber', $matches[1], 'Номер документа', 'Номер исполнительного документа');
                        $doc = substr($doc,strlen($matches[1])+4);
                    }
                    $doc = trim(strtr($doc,array('Постановление о взыскании исполнительского сбора'=>'')));
                    if ($doc)
                        $record['doc_issuer'] = new ResultDataField('string','DocIssuer', $doc, 'Орган', 'Орган, выдавший исполнительный документ');
/*
                    if ($h=strpos($doc,"href='http")) {
                        $h+=6;
                        $record['doc_url'] = new ResultDataField('url','DocURL', substr($doc,$h,strpos($doc,"'",$h)-$h), 'URL документа', 'URL исполнительного документа');
                    }
*/
                    if (isset($close[0]) && $close[0]) {
                        if (sizeof($close)>1)
                            $record['close_date'] = new ResultDataField('string','CloseDate', date('d.m.Y',strtotime($close[0])), 'Дата завершения', 'Дата завершения исполнительного производства');
                        else
                            $close[1]=$close[0];
                    }
                    if (isset($close[1]) && $close[1]) {
                        $record['close_reason'] = new ResultDataField('string','CloseReason', 'ст. '.$close[1] . (isset($close[2])?' ч. '.$close[2]:'') . (isset($close[3])?' п. '.$close[3]:''), 'Причина завершения', 'Причина завершения исполнительного производства');
                        $record['close_reason1'] = new ResultDataField('string','CloseReason1', $close[1], 'Причина завершения - статья', 'Причина завершения исполнительного производства - статья');
                    }
                    if (isset($close[2]) && $close[2]) {
                        $record['close_reason2'] = new ResultDataField('string','CloseReason2', $close[2], 'Причина завершения - часть', 'Причина завершения исполнительного производства - часть');
                    }
                    if (isset($close[3]) && $close[3]) {
                        $record['close_reason3'] = new ResultDataField('string','CloseReason3', $close[3], 'Причина завершения - пункт', 'Причина завершения исполнительного производства - пункт');
                    }

                    if ($subject[0])
                        $record['subject'] = new ResultDataField('string','Subject', $subject[0], 'Предмет', 'Предмет исполнения');
                    if (sizeof($subject)>1)
                        $record['total'] = new ResultDataField('float','Total', strtr(substr($subject[1],0,strpos($subject[1],' ')),',','.'), 'Сумма', 'Сумма задолженности');
                    foreach($subjects as $subject) {
                        $subject_b = explode(': ', $subject);
                        $name = array(
                            'Исполнительский сбор' => array('Bailiff','Сбор','Сумма сбора'),
                            'Задолженность по расходам' => array('Costs','Расходы','Сумма расходов'),
                            'Штраф СПИ' => array('Fine','Штраф','Сумма штрафа'),
                        );
                        if (isset($name[$subject_b[0]])) {
                            $n = $name[$subject_b[0]][0];
                            $s = $name[$subject_b[0]][1];
                            $t = $name[$subject_b[0]][2];
                            $record[$n.'Subject'] = new ResultDataField('string',$n.'Subject', $subject_b[0], $s, $s.' исполнителя');
                            if(sizeof($subject_b)>1)
                                $record[$n.'Total'] = new ResultDataField('float',$n.'Total', strtr(substr($subject_b[1],0,strpos($subject_b[1],' ')),',','.'), $t, $t.' исполнителя');
                        }
                    }

                    $record['department'] = new ResultDataField('string','Department', $department, 'Отдел', 'Отдел судебных приставов');
                    if (isset($department_address))
                        $record['department_address'] = new ResultDataField('string','DepartmentAddress', $department_address, 'Адрес отдела', 'Адрес отдела судебных приставов');

                    $record['bailiff'] = new ResultDataField('string','Bailiff', $bailiff_name, 'Пристав', 'Судебный пристав-исполнитель');
                    foreach($bailiff as $i => $bailiff_phone) {
                        if (strpos($bailiff_phone,'<')!==false) break;
                        if ($bailiff_phone && ($i==0 || $bailiff_phone!=$bailiff[$i-1]))
                            $record['bailiff_phone'.$i] = new ResultDataField('string','BailiffPhone', strip_tags($bailiff_phone), 'Телефон', 'Телефон судебного пристава-исполнителя');
                    }

                    $resultData->addResult($record);
                }

            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif (strpos($content,'Exception') || strpos($content,'404 Not Found') || strpos($content,'502 Bad Gateway') || strpos($content,'Ошибка')!==false) {
            if ($swapData['iteration']>=3) $error = 'Внутренняя ошибка источника';
            file_put_contents('./logs/fsspapp/fssp_err_'.time().'_'.$swapData['iteration'].'.txt', $content);
        } elseif (strpos($content,'403 Forbidden')) {
            if (isset($swapData['session']) && $swapData['session']->id)
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='forbidden' WHERE id=" . $swapData['session']->id);
            unset($swapData['session']);
        } elseif (!$content) {
            if (isset($swapData['session']) && $swapData['session']->id)
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='empty' WHERE id=" . $swapData['session']->id);
            unset($swapData['session']);
        } else {
            if ($swapData['iteration']>=5) $error = 'Некорректный ответ сервиса';
            file_put_contents('./logs/fsspapp/fssp_err_'.time().'_'.$swapData['iteration'].'.txt', $content);
            if (isset($swapData['session']) && $swapData['session']->id)
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='invalidanswer' WHERE id=" . $swapData['session']->id);
            unset($swapData['session']);
        }
        $rContext->setSwapData($swapData);

        if(isset($initData['last_name']) && $initData['last_name'] && ($initData['last_name']==$initData['first_name'] || (isset($initData['patronymic']) && $initData['last_name']==$initData['patronymic']) || (isset($initData['patronymic']) && $initData['first_name']==$initData['patronymic'])) && $swapData['iteration']>=3) {
            $rContext->setFinished();
            $rContext->setError($error?$error:'ФССП не может обработать запрос с совпадением полей в ФИО');
            return false;
        }
        if($error || $swapData['iteration']>=10) {
            $rContext->setFinished();
            $rContext->setError($error?$error:'Превышено количество попыток получения ответа');
            return false;
        }

        $rContext->setSleep(1);
        return true;
    }
}

?>