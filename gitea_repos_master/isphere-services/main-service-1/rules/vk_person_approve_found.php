<?php

// Одобрение при найденном профиле VK
$rulesfunc['vk_person_approve_found'] = function() use (&$xml) {
    $resp = array(
        'result' => 'ERROR',
        'message' => 'No data for rule',
    );
    if(isset($xml->Source)){
        foreach($xml->Source as $source){
            if($source['checktype'] == 'vk_person'){
                if (isset($source->ResultsCount)) {
                    if (intval($source->ResultsCount)>0) {
                        $resp['result'] = 'APPROVE';
                        $resp['message'] = 'VK profile found';
                     } else {
                        $resp['result'] = 'NEXT';
                        $resp['message'] = 'VK profile not found';
                     }
                } else {
                    $resp['result'] = 'ERROR';
                    $resp['message'] = 'Error while searching profile';
                }
            }
        }
    }
    return $resp;
};

?>