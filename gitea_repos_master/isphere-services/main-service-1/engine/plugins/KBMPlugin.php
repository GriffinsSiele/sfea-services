<?php

class KBMPlugin implements PluginInterface
{
    public function getName()
    {
        return 'RSA';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'РСА - Проверка КБМ водителя',
            'rsa_kbm' => 'РСА - проверка КБМ водителя',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Проверка КБМ водителя';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=32 AND lasttime<DATE_SUB(now(), INTERVAL 1 SECOND) ORDER BY lasttime limit 1");

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

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],4);

        if(($checktype=='kbm') && (!isset($initData['driver_number']) || !isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date'])))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (фамилия, имя, дата рождения и номер в/у)');

            return false;
        }

        if (!isset($swapData['date'])) {
            $swapData['date'] = date('d.m.Y',isset($initData['reqdate']) ? strtotime($initData['reqdate']) : time());
            $rContext->setSwapData($swapData);
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        if(!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            if (isset($swapData['session'])) {
                $rContext->setSwapData($swapData);
            } else {
//                $rContext->setFinished();
//                $rContext->setError('Нет актуальных сессий');
                $rContext->setSleep(1);
                return false;
            }
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://kbm-osago.online/proverka-kbm';
        $params = array(
            'fio' => mb_strtoupper(isset($initData['name'])?$initData['name']:$initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic'])?' '.$initData['patronymic']:'')),
            'birthday' => isset($initData['date'])?date('d.m.Y',strtotime($initData['date'])):'',
            'vod' => mb_substr($initData['driver_number'],0,4).' '.trim(mb_substr($initData['driver_number'],4)),
            'old_vod' => '',
            'sposob' => 0,
            'datekbm' => $swapData['date'],
        );
        if (preg_match_all("/value = ([a-z0-9]+) name=\"([a-z]+)\"/",$swapData['session']->token,$matches)) {
            foreach($matches[1] as $i => $value) {
                $params[$matches[2][$i]] = $value;
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With: XMLHttpRequest',
        ));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
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
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $curlerror = false; //curl_error($rContext->getCurlHandler());

        if($curlError && $swapData['iteration']>10)
        {
            $rContext->setFinished();
            $rContext->setError($curlError==''?'Превышено количество попыток получения ответа':$curlError);

            return false;
        }

        $rContext->setSwapData($swapData);

        $content = curl_multi_getcontent($rContext->getCurlHandler());
//        if (!empty(trim($content))) file_put_contents('./logs/rsa/kbm_'.time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);

        if (preg_match("/КБМ для указанных данных: /",$content)) { 
            if (isset($swapData['list'])) {
                $resultData = $swapData['list'];
            } else {
                $resultData = new ResultDataList();
            }
/*
Расчет на дату: 03.08.2015<br>
КБМ для указанных данных: 1.55 класс 1<br>
Страховщик: РОСГОССТРАХ<br>
Полис: ССС 0653816043 начало 16.12.2013 окончание 15.12.2014<br>
КБМ по полису: 1 класс 3<br>
Дата убытка: 25.02.2014 полис ССС 0305364991 СГ<br>
<a href="http://apins.ru/download/xsnoj1564789724.pdf" download>Скачать файл отчета РСА (ссылка доступна 5 мин)</a><hr>
*/

/*
Расчет на дату: 03.08.2019<br>
КБМ для указанных данных: 1 класс 3<br>
Не найден предыдущий договор ОСАГО<br>
<a href="http://apins.ru/download/hlp7s1564787710.pdf" download>Скачать файл отчета РСА (ссылка доступна 5 мин)</a><hr>
*/

            $data = array();
            $next_date = false;
            if (preg_match("/Расчет на дату: ([\d.]+)/",$content,$matches)) { 
                $data['Date'] = new ResultDataField('string','Date', $matches[1], 'Дата запроса', 'Дата запроса');
            }
            if (preg_match("/Полис: ([^\s]+\s[\d]+)[^\d]+([\d.]+)[^\d]+([\d.]+)/",$content,$matches)) { 
                $data['Type'] = new ResultDataField('string','Type', 'policy', 'Тип записи', 'Тип записи');
                $data['PolicyNumber'] = new ResultDataField('string','PolicyNumber', $matches[1], 'Полис ОСАГО', 'Полис ОСАГО');
                $data['PolicyStartDate'] = new ResultDataField('string','PolicyStartDate', $matches[2], 'Дата начала', 'Дата начала полиса ОСАГО');
                $data['PolicyEndDate'] = new ResultDataField('string','PolicyEndDate', $matches[3], 'Дата окончания', 'Дата окончания полиса ОСАГО');
//                $next_date = date('d.m.Y',strtotime('-1 day',strtotime($matches[2])));
                if (date('Y-m-d',strtotime('-1 year',strtotime($swapData['date'])))>=date('Y-m-d',strtotime($matches[2])))
                    $next_date = date('d.m.Y',strtotime('-1 year',strtotime($swapData['date'])));
                else
                    $next_date = date('d.m.Y',strtotime($matches[2]));
            }
            if (preg_match("/Страховщик: ([^<]+)/",$content,$matches)) {
                $data['Company'] = new ResultDataField('string','Company', $matches[1], 'Страховая компания', 'Страховая компания');
            }
            if (preg_match("/КБМ по полису: ([\d.]+)[^\d]+([\d]+)/",$content,$matches)) { 
                $data['Class'] = new ResultDataField('string','Class', $matches[2], 'Класс', 'Класс в период действия полиса');
                $data['Kbm'] = new ResultDataField('string','Kbm', $matches[1], 'КБМ', 'КБМ в период действия полиса');
            }
            if (!isset($swapData['list']) && preg_match("/КБМ для указанных данных: ([\d.]+)[^\d]+([\d]+)/",$content,$matches)) { 
                $data['Type'] = new ResultDataField('string','Type', 'kbm', 'Тип записи', 'Тип записи');
                $data['Class'] = new ResultDataField('string','Class', $matches[2], 'Класс', 'Класс');
                $data['Kbm'] = new ResultDataField('string','Kbm', $matches[1], 'КБМ', 'КБМ');
                if (date('Y-m-d',strtotime($swapData['date']))>='2020-04-01')
                    $next_date = '31.03.2020';
            }
            if (preg_match_all("/Дата убытка: ([\d.]+)\s[^\s]+\s([^\s]+\s[\d]+)/",$content,$matches)) { 
                $data['LossCount'] = new ResultDataField('string','LossCount', sizeof($matches[1]), 'Страховых случаев', 'Страховых случаев в период действия полиса');

                foreach($matches[1] as $i => $val) {
                    $lossdata = array();
                    $lossdata['LossDate'] = new ResultDataField('string','LossDate', $matches[1][$i], 'Дата страхового случая', 'Дата страхового случая');
                    $lossdata['PolicyNumber'] = new ResultDataField('string','PolicyNumber', $matches[2][$i], 'Полис ОСАГО', 'Полис ОСАГО');
//                    $lossdata['Company'] = new ResultDataField('string','Company', $matches[3][$i], 'Страховая компания', 'Страховая компания');
                    $lossdata['Type'] = new ResultDataField('string','Type', 'loss', 'Тип записи', 'Тип записи');
                    $resultData->addResult($lossdata);
                }

            }
            if (sizeof($data)>1) {
//                $data['Type'] = new ResultDataField('string','Type', 'policy', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
            }
            if ($next_date) {
                $swapData['list'] = $resultData;
                $swapData['date'] = $next_date;
                $rContext->setSwapData($swapData);
            } else {
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            }
        } elseif ($swapData['iteration']>5) {
            if ($content) {
                file_put_contents('./logs/rsa/kbm_err_'.time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                $rContext->setFinished();
                $rContext->setError("Некорректный ответ сервиса");
            } else {
                $rContext->setFinished();
                $rContext->setError("Сервис не отвечает");
            }
            return false;
        }
    }
}

?>