<?php

class CheckWAPlugin implements PluginInterface
{

    public function __construct()
    {
    }

    public function getName()
    {
        return 'CheckWA';
    }

    public function getTitle()
    {
        return 'Поиск в WhatsApp через CheckWA';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['phone']))
        {
            $rContext->setFinished();
            $rContext->setError('Не задан номер телефона');

            return false;
        }

        if (strlen($initData['phone'])==10)
            $initData['phone']='7'.$initData['phone'];
        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            $initData['phone']='7'.substr($initData['phone'],1);

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        if(!isset($swapData['cookies'])){
            $swapData['cookies'] = time().'_'.rand(100,1000);
            $rContext->setSwapData($swapData);
        }

        $ch = $rContext->getCurlHandler();

        $url = 'http://www.checkwa.com/';
        if(!isset($swapData['step'])){
            $url .= 'inc/inc.request.php';
            $post = array('cod'=>substr($initData['phone'],0,1),'num'=>substr($initData['phone'],1));
        } elseif($swapData['step']=='retrieve') {
            $url .= 'inc/inc.retrieve.php';
            $post = array('num'=>$initData['phone']);
        } elseif($swapData['step']=='photo') {
            $url .= $swapData['photo'];
            $post = false;
        } else/*if($swapData['step']=='last')*/ {
            $url .= 'inc/inc.retrievelast.php';
            $post = array('num'=>$initData['phone']);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_COOKIEFILE, './logs/cookies/'.$swapData['cookies'].'_cookies.txt');
	curl_setopt($ch, CURLOPT_COOKIEJAR, './logs/cookies/'.$swapData['cookies'].'_cookies.txt');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $curlError = curl_error($rContext->getCurlHandler());
        if($curlError){
            $rContext->setFinished();
            $rContext->setError($curlError==''? 'Unknown Error' : $curlError); 
            return false;
        }
							    
        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if(!isset($swapData['step'])){
            $swapData['step'] = 'retrieve';
        } elseif($swapData['step']=='retrieve'){
            if (($content=='NO') || ($content=='')) {
                $rContext->setSleep(1);
                $error = "Запрос в процессе обработки";
                return true;
            } else {
//                file_put_contents('./logs/wa/wa'.time().'.html',$content);

                if (preg_match("/<div id=\"wtextok\">/", $content, $matches)){
                    if (preg_match("/<div id=\"wlast\">(.*?)<\/div>/", $content, $matches)) {
                        $data['status'] = new ResultDataField('string','Status',trim(strip_tags($matches[1])),'Статус','Статус');
                    }
                    if (preg_match("/<div id=\"wimagen\"[^<]<img src=\"([^\"]+)\">/", $content, $matches)) {
                        $swapData['photo'] = trim($matches[1]);
                    }
                    $swapData['step'] = $swapData['photo'] ? 'photo' : 'last';
                    $swapData['data'] = $data;
                } elseif (preg_match("/<div id=\"wtextko\">/", $content, $matches)){
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    return true;
                } else {
                    $rContext->setError("Невозможно отправить запрос");
                    $rContext->setFinished();
                    return false;
                }
            }
        } elseif($swapData['step']=='photo'){
            global $serviceurl;
            $data = $swapData['data'];
	    $img = 'logs/wa/wa_'.time().'.jpg';
            file_put_contents('./'.$img,$content);
            $data['image'] = new ResultDataField('image', 'Image', $serviceurl.$img, 'Изображение', 'Изображение');
            $swapData['step'] = 'last';
            $swapData['data'] = $data;
        } else {
            $data = $swapData['data'];
//            file_put_contents('./logs/wa/walast'.time().'.html',$content);
            if (strpos($content,': ')) {
                $data['lastactivity'] = new ResultDataField('string','LastActivity',substr($content,strpos($content,': ')+2),'Время последней активности','Время последней активности');
            }
            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return false;
        }

        $rContext->setSwapData($swapData);

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        if(isset($swapData['iteration']) && $swapData['iteration']>3)
        {
            $rContext->setFinished();
            $rContext->setError($error==''?'Превышено количество попыток получения ответа':$error);

            return false;
        }

        return true;

    }
}

?>