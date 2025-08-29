<?php

class FedresursPlugin implements PluginInterface
{
    public function getName()
    {
        return 'fedresurs';
    }

    public function getTitle()
    {
        return 'Поиск в Федрерурс';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['inn']) && !isset($initData['ogrn'])) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ИНН или ОГРН)');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        if (!isset($swapData['url'])) {
            $url = 'https://fedresurs.ru/search/entity?code='.(isset($initData['inn'])?$initData['inn']:$initData['ogrn']);
        } else {
            $url = $swapData['url'];
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = ($swapData['iteration']>5) && curl_error($rContext->getCurlHandler());
        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());

            if (!isset($swapData['ogrn'])) {
                file_put_contents('./logs/fedresurs/fedresurs_search_'.time().'.html',$content);
                $resultData = new ResultDataList();

                if(preg_match("/<tbody>(.*?)<\/tbody>/sim", $content, $matches)){
                    $parts = preg_split("/<\/tr>/",$matches[1]);
                    array_pop($parts);
                    foreach ($parts as $i => $dataPart) {
                        $data = array();
                        if(preg_match("/<a itemprop=\"legalName\" href='\/company\/ul\/([0-9]+)_([0-9]+)[^>]+>([^<]+)<br[^<]+<span[^>]+>([^<]+)<\/span><\/a>/", $dataPart, $matches)){
                            $swapData['ogrn'] = trim($matches[1]);
                            $rContext->setSwapData($swapData);
                            return true;
                        }
                    }
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } else {
//                file_put_contents('./logs/gks/gks_'.time().'.html',$content);
                $resultData = new ResultDataList();
                $totals = array();

                $rows = preg_split("/<tr>/",$content);
                array_shift($rows);
                foreach ($rows as $i => $row) {
                    if(preg_match_all("/<td>([^<]+)<\/td>/", $row, $matches) && sizeof($matches[1])==6){
                        foreach ($matches[1] as $j => $val) {
                            $totals[$j][] = strtr($val,array(' '=>'',','=>'.'));
                        }
                    }
                }
                
                foreach($totals as $j => $total) {
                    if ($j) {
                        $data = array();
                        $data['year'] = new ResultDataField('string', 'year', 2017-$j, 'Год', 'Год');
//                        echo 'Год: '.(2017-$j)."\n";
                        foreach($total as $i => $val) {
                            if (isset($this->names[$i])) {
                                $field = $this->names[$i];
                                if ($val && ($val!=='-')) {
                                    $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $val, $field[1], $field[2]);
//                                    echo $field[0].' '.$field[1].': '.$val."\n";
                                }
                            }
                        }
                        if(sizeof($data)>1)
                            $resultData->addResult($data);
                    }
                }

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