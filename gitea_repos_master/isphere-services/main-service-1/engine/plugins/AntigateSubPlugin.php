<?php

class AntigateSubPlugin implements PluginInterface
{
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getName()
    {
        return 'antigate';
    }

    public function getTitle()
    {
        return 'Распознавание капч';
    }

    public function prepareRequest(&$rContext)
    {
        $swapData = $rContext->getSwapData();

        $ch = $rContext->getCurlHandler();

        if(!isset($swapData['mode']))
            $swapData['mode'] = 'send_captcha';

        switch($swapData['mode'])
        {
            case 'send_captcha' : // send captcha

                $params = isset($swapData['ag_params']) ?
                    $swapData['ag_params'] :
                    array(
                        'method'     => 'base64',
                        'ext'        => 'jpg',
                        'phrase'     => 0,
                        'regsense'   => 0,
                        'numeric'    => 1,
                        'min_len'    => 5,
                        'max_len'    => 5,
                        'is_russian' => 0,
                    );

                $params['body']=base64_encode($swapData['captcha_data']);
                $params['key']=$this->apiKey;

                //print '<img src="data:image/gif;base64,'.base64_encode($swapData['captcha_data']).'"/>';
                //exit();

                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                curl_setopt($ch, CURLOPT_URL, 'http://antigate.com/in.php');

                unset($swapData['captcha_data']);

                break;

            case 'get_captcha_value' : // get captcha value
                curl_setopt($ch, CURLOPT_POST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 7);
                curl_setopt($ch, CURLOPT_URL, "http://antigate.com/res.php?key=".$this->apiKey.'&action=get&id='.$swapData['captcha_id']);

                break;
        }

        $rContext->setCurlHandler($ch);
        $rContext->setSwapData($swapData);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $swapData = $rContext->getSwapData();

        $curlError = curl_error($rContext->getCurlHandler());

        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if(!empty($curlError))
        {
            $rContext->setFinished();
            $rContext->setError($curlError);
        }
        else
            switch($swapData['mode'])
            {
                case 'send_captcha' :

                    $swapData['mode'] = 'get_captcha_value';

                    if (strpos($content, "ERROR")!==false) {
                        $rContext->setFinished();
                        $rContext->setError($content);
                        return false;
                    }

                    $ex = explode("|", $content);
                    $swapData['captcha_id'] = $ex[1];

                    break;

                case 'get_captcha_value' :

                    if (strpos($content, 'ERROR')!==false) {

                        $rContext->setFinished();
                        $rContext->setError($content);
                    }

                    if ($content=="CAPCHA_NOT_READY") {

                        $rContext->setSleep(1);

                        return true;
                    }

                    $rContext->setSleep(0);

                    $ex = explode('|', $content);

                    $swapData['captcha_value'] = trim($ex[1]);
                    $swapData['mode'] = 'captcha_ready';

                    break;
            }

        $rContext->setSwapData($swapData);
        return true;
    }
}

?>