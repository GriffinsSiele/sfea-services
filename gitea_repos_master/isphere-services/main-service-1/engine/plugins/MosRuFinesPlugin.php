<?php

class MosRuFinesPlugin implements PluginInterface
{
    private $names = array (
                           'Номер постановления' => array('DAPNumber', 'Номер постановления', 'Номер постановления'),
                           'Дата постановления' => array('DAPDate', 'Дата постановления', 'Дата постановления'),
                           'Дата вступления постановления в силу' => array('EffDate', 'Дата вступления в силу', 'Дата вступления постановления в силу'),
                           'Статья КоАП или закона субъекта РФ, состав правонарушения' => array('StAP', 'Статья, правонарушение', 'Статья КоАП или закона субъекта РФ, состав правонарушения'),
                           'Дата и время нарушения' => array('DatNar', 'Дата и время нарушения', 'Дата и время нарушения'),
                           'Место нарушения' => array('MestoNar', 'Место нарушения', 'Место нарушения'),
                           'Место составления документа' => array('MestoDAP', 'Место составления документа', 'Место составления документа'),
                           'Орган власти, выявивший нарушение' => array('OdpsName', 'Орган власти', 'Орган власти, выявивший нарушение'),
                           'Нарушитель' => array('FIONarush', 'Нарушитель', 'Нарушитель'),
                           'Транспортное средство' => array('GRZNarush', 'Транспортное средство', 'Транспортное средство'),
    );

    public function getName()
    {
        return 'avtokod';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск штрафов в АвтоКод',
            'avtokod_fines' => 'Автокод - поиск штрафов',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск штрафов через АвтоКод';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=43 ORDER BY lasttime limit 1");
//        $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password)proxy_auth FROM isphere.proxy WHERE status=1 ORDER BY lasttime limit 1");

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;

                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],8);

        if (($checktype=='fines') && !isset($initData['ctc']) && !isset($initData['driver_number']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (св-во о регистрации ТС или водительское удостоверение)');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $swapData['session'] = $this->getSessionData();
//        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $rContext->setSwapData($swapData);

        if(!$swapData['session']) {
            if (isset($swapData['iteration']) && ($swapData['iteration']>=10)) {
                $rContext->setFinished();
                $rContext->setError('Нет актуальных сессий');
            } else {
                (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
                $rContext->setSwapData($swapData);
                $rContext->setSleep(3);
            }
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://src2.i-sphere.ru/mosru/';
        $proxy_auth = explode(':',$swapData['session']->proxy_auth);
        $params = array(
            "proxy" => $swapData['session']->proxy,
            "pUser" => $proxy_auth[0],
            "pPasswd" => $proxy_auth[1],
        );
        $params['sts'] = isset($initData['ctc'])?$initData['ctc']:'';
        $params['vu'] = isset($initData['driver_number'])?$initData['driver_number']:'';
//        var_dump($params); echo "\n";
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_URL, $url);
        $header[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        global $total_timeout;
        curl_setopt($ch, CURLOPT_TIMEOUT,$total_timeout+15);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $error = false;
        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if (!$content) {
            $error = ($swapData['iteration']>5) && curl_error($rContext->getCurlHandler());
        } else {
//            file_put_contents('./logs/mosru/shtrafy_'.time().'.txt',$content);
            $res = json_decode($content, true);               
            if($res && isset($res['status']) && $res['status']=='success'){
                $resultData = new ResultDataList();
                if (isset($res['data']) && is_array($res['data'])) {
                    foreach($res['data'] as $fine) {
                        $data = array();
                        if (isset($fine['details']['Место нарушения'])) {
                            $arr = preg_split("/\n/",$fine['details']['Место нарушения']);
                            $fine['details']['Место нарушения'] = $arr[0];
                        }
                        if (isset($fine['details']['Дата и время нарушения'])) {
                            $arr = preg_split("/\n/",$fine['details']['Дата и время нарушения']);
                            $fine['details']['Дата и время нарушения'] = $arr[0];
                        }
                        if (isset($fine['details']) && is_array($fine['details'])) {
                            foreach($fine['details'] as $title => $text) {
                                if (isset($this->names[$title])){
                                    $field = $this->names[$title];
                                    $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $text, $field[1], $field[2]);
                                }
                            }
                        }
                        if (isset($fine['sum']) && ($fine['sum'] = preg_replace("/\D/","",$fine['sum'])))
                            $data['sum'] = new ResultDataField('float', 'Sum', $fine['sum'], 'Сумма', 'Сумма');
                        if (isset($fine['paymentPaid']))
                            $data['status'] = new ResultDataField('string', 'Status', $fine['paymentPaid']=='*'?'Не оплачен':$fine['paymentPaid'], 'Статус', 'Статус');
                        if (isset($fine['img']) && is_array($fine['img'])) {
                            foreach ($fine['img'] as $i => $img) {
                                if (strpos($img,'data:image')===0) {
                                    $data['photo'.($i?$i+1:'')] = new ResultDataField('image', 'Photo'.($i?$i+1:''), $img, 'Фото '.($i?$i+1:''), 'Фото '.($i?$i+1:''));
                                } 
                            }
                        }
                        $resultData->addResult($data);
                    }
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
            } elseif ($res && isset($res['status']) && $res['status']=='error') {
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
            } else {
                file_put_contents('./logs/mosru/shtrafy_err_'.time().'.txt',$content);
                $error = "Некорректный ответ";
            }
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>=5) {
            $error='Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        $rContext->setSleep(5);
        return true;
    }
}

?>