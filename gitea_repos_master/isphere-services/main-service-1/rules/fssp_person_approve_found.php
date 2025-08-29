<?php

// Одобрение при найденном ИП в ФССП
$rulesfunc['fssp_person_approve_found'] = function() use (&$xml) {
    $resp = array(
        'result' => 'ERROR',
        'message' => 'No data for rule',
    );
    if(isset($xml->Source)){
        foreach($xml->Source as $source){
            if($source['checktype'] == 'fssp_person'){
                if (isset($source->ResultsCount)) {
                    if (intval($source->ResultsCount)>0) {
                        $resp['result'] = 'APPROVE';
                        $resp['message'] = 'FSSP data found';
                     } else {
                        $resp['result'] = 'NEXT';
                        $resp['message'] = 'FSSP data not found';
                     }
                } else {
                    $resp['result'] = 'ERROR';
                    $resp['message'] = 'Error while processing FSSP data';
                }
            }
        }
    }
    return $resp;
};

?>