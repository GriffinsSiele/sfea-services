<?php

class TESTPlugin implements PluginInterface
{
    public function getName()
    {
        return 'TEST';
    }

    public function getTitle()
    {
        return 'TEST';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $params = array(
                'blah' => $initData['blah'],
            );

        $url = 'https://i-sphere.ru/some/blah1.php';

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();


        $error = curl_error($rContext->getCurlHandler());

        if(!$error)
        {
            $content = curl_multi_getcontent($rContext->getCurlHandler());

                if (substr($content,0,2)=='OK')
                {
                    //$swapData['smsc_id'] = str_between($content . ',','ID - ',',');
		    $data['blah'] = new ResultDataField('string','blah', substr($content, 2), 'blah', 'BLAH');
		    $rContext->setResultData($data);
                    $rContext->setFinished();
                }
                else
                {
                    $error = 'ERROR';
                    $rContext->setError($error);
                    $rContext->setFinished();

                    return false;
                }

        }

        return true;
    }
}

?>