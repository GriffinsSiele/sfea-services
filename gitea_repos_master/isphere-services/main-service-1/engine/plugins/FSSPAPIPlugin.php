<?php

class FSSPAPIPlugin implements PluginInterface
{
    public function getName()
    {
        return 'fssp';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'ФССП - поиск исполнительных производств',
            'fssp_person' => 'ФССП - исполнительные производства (api)',
            'fssp_org' => 'ФССП - исполнительные производства по организации',
            'fssp_ip' => 'ФССП - информация об исполнительном производстве',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'ФССП РФ - поиск исполнительных производств';
    }

    public function prepareRequest(&$rContext)
    {
        global $http_connecttimeout, $http_timeout;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],5);

        if(($checktype=='person') && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date'])))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения)');

            return false;
        }

        if(($checktype=='org') && !isset($initData['name']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (название организации)');

            return false;
        }

        if(($checktype=='ip') && !isset($initData['fssp_ip']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (номер ИП)');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $params = array('token' => 'e60G5CFkL92V');
        if (!isset($swapData['task'])) {
            $methods = array(
                'person'=>'physical',
                'org'=>'legal',
                'ip'=>'ip',
            );
            $method = 'search/'.$methods[$checktype];
            if ($checktype=='person') {
                $params['region']=isset($initData['region_id'])?$initData['region_id']:'all';
                $params['lastname']=$initData['last_name'];
                $params['firstname']=$initData['first_name'];
                $params['secondname']=isset($initData['patronymic'])?$initData['patronymic']:'';
                $params['birthdate']=isset($initData['date'])?date('d.m.Y',strtotime($initData['date'])):'';
            } elseif ($checktype=='org') {
                $params['region']=isset($initData['region_id'])?$initData['region_id']:0;
                $params['name']=$initData['name'];
                $params['address']=isset($initData['address'])?$initData['address']:'';
            } elseif ($checktype=='ip') {
                $params['number']=$initData['fssp_ip'];
            } else {
                $rContext->setFinished();
                return false;
            }
        } else {
            $params['task'] = $swapData['task'];
            $method = $swapData['method'];
        }
        $url = 'https://api-ip.fssp.gov.ru/api/v1.0/'.$method.'?'.http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $error = false;

        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $checktype = substr($initData['checktype'],5);

        $rContext->setSwapData($swapData);
        $content = curl_multi_getcontent($rContext->getCurlHandler());


        if (!isset($swapData['task'])) {
//            if ($content) file_put_contents('./logs/fsspapi/fsspapi_start_'.time().'.txt',$content);
            $start = strpos($content,'{');
            $content = trim(substr($content,$start,strlen($content)-$start+1));
            $res = json_decode($content,true);
            if (is_array($res) && isset($res['response']['task'])) {
                $swapData['task'] = $res['response']['task'];
                $swapData['method'] = 'result'; //'status';
                $rContext->setSwapData($swapData);
                $rContext->setSleep(4);
            } elseif (isset($res['exception'])) {
                if ($content) file_put_contents('./logs/fsspapi/fsspapi_start_err_'.time().'.txt',$content);
                $error = $res['exception'];
            } else {
                $error = "Ошибка при выполнении запроса";
            }
        } else {
//            if (!empty(trim($content))) file_put_contents('./logs/fsspapi/fsspapi_'.$swapData['method'].'_'.time().'.txt',$content);
            $start = strpos($content,'{');
            $content = trim(substr($content,$start,strlen($content)-$start+1));
            $res = json_decode($content,true);

            $resultData = new ResultDataList();
            if (is_array($res)) {
                if (isset($res['response']['status']) && ($res['response']['status']==1 || $res['response']['status']==2)) {
                    $rContext->setSleep(2);
                } elseif ($swapData['method']=='status') {
                    $swapData['method'] = 'result';                    
                    $rContext->setSwapData($swapData);
                } elseif (isset($res['response']['result'][0])) {
                    $found = isset($res['response']['result'][0]['result']) ? sizeof($res['response']['result'][0]['result']) : 0;
                    if (($found >= 100) && !isset($initData['date']) && !isset($initData['region_id'])) {
                        $rContext->setError("Найдено слишком много совпадений ($found). Попробуйте указать в запросе дату рождения или регион.");
                        $rContext->setFinished();
                        return true;
                    }

            if(isset($res['response']['result'][0]['result']['errors']['name'][0])) {
                $rContext->setFinished();
                $rContext->setError($res['response']['result'][0]['result']['errors']['name'][0]);
                return false;
            }

        if (isset($res['response']['result'][0]['result']))
            foreach($res['response']['result'][0]['result'] as $element) {
                if(isset($element['name']))
                {
                    $record = array();

                    if (preg_match("/^(.*?) (\d{2}\.\d{2}\.\d{4}) (.*?)$/",$element['name'],$matches)) {
                        $debtor = trim($matches[1]);
                        $debtor_birthdate = trim($matches[2]);
                        $debtor_birthplace = trim($matches[3]);
                    } elseif ($checktype=='person' && strpos($element['name'],',')>12) {
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
                    $subject = explode(': ', $subjects[0]);
                    array_shift($subjects);
                    $department = $element['department'];
                    $bailiff = explode('<br>', $element['bailiff']);
                    if (strpos($bailiff[0],'<br/>')) {
                       $bailiff = explode('<br/>', $bailiff[0]);
                    }

                    $record['name'] = new ResultDataField('string','Debtor', $debtor, 'Должник', 'Должник');
                    if ($debtor_birthdate)
                        $record['debtor_birthday'] = new ResultDataField('string','DebtorBirthday', $debtor_birthdate, 'Дата рождения', 'Дата рождения должника');
                    if ($debtor_birthplace)
                        $record['debtor_birthplace'] = new ResultDataField('string','DebtorBirthplace', $debtor_birthplace, 'Место рождения', 'Место рождения должника');

                    $record['case_num'] = new ResultDataField('string','CaseNumber', $case[0], 'Номер ИП', 'Номер исполнительного производства');
                    $record['case_date'] = new ResultDataField('string','CaseDate', substr(trim($case[1]),0,10), 'Дата ИП', 'Дата исполнительного производства');
                    if (strlen(trim($case[1]))>11)
                        $record['summary_case_num'] = new ResultDataField('string','SummaryCaseNumber', substr(trim($case[1]),11), 'Номер сводного ИП', 'Номер сводного исполнительного производства');

                    $record['doc_type'] = new ResultDataField('string','DocType', strip_tags(substr($doc,0,strpos($doc,' от '))), 'Вид документа', 'Вид исполнительного документа');
                    $record['doc_date'] = new ResultDataField('string','DocDate', substr($doc,strpos($doc,' от ')+6,10), 'Дата документа', 'Дата исполнительного документа');
                    if ($numpos=strpos($doc,'№')) {
                        $num = substr($doc,$numpos+4);
                        if ($docpos=strpos($num,' П')) {
                            $bailiff_doc = substr($num,$docpos+1);
                            $num = substr($num,0,$docpos);
                        }
                        $record['doc_num'] = new ResultDataField('string','DocNumber', $num, 'Номер документа', 'Номер исполнительного документа');
                    }
//                    $record['doc_issuer'] = new ResultDataField('string','DocIssuer', $doc_issuer, 'Орган', 'Орган, выдавший исполнительный документ');
                    if ($h=strpos($doc,"href='http")) {
                        $h+=6;
                        $record['doc_url'] = new ResultDataField('url','DocURL', substr($doc,$h,strpos($doc,"'",$h)-$h), 'URL документа', 'URL исполнительного документа');
                    }

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
//                    $record['department_address'] = new ResultDataField('string','DepartmentAddress', $department_address, 'Адрес отдела', 'Адрес отдела судебных приставов');

                    $record['bailiff'] = new ResultDataField('string','Bailiff', $bailiff[0], 'Пристав', 'Судебный пристав-исполнитель');
//                    $record['bailiff_phone'] = new ResultDataField('string','BailiffPhone', $bailiff[1], 'Телефон', 'Телефон судебного пристава-исполнителя');

                    $resultData->addResult($record);
                }

            }

                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } else {
                    if (!empty(trim($content))) file_put_contents('./logs/fsspapi/fsspapi_'.$swapData['method'].'_err_'.time().'.txt',$content);
                    $error = $res['exception'];
                }
            } elseif (empty(trim($content))) {
                $rContext->setSleep(1);
            }
        }

        if ($error == 'Too Many Attempts.') {
            $rContext->setSleep(4);
        }

        if($swapData['iteration']>50) {
            $rContext->setFinished();
            $rContext->setError($error==''?'Превышено количество попыток получения ответа':$error);
            return false;
        }

        return true;
    }
}

?>