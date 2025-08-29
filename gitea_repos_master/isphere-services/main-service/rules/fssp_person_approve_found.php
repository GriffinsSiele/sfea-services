<?php

// Одобрение при найденном ИП в ФССП
$rulesfunc['fssp_person_approve_found'] = function () use (&$xml) {
    $resp = [
        'result' => 'ERROR',
        'message' => 'No data for rule',
    ];
    if (isset($xml->Source)) {
        foreach ($xml->Source as $source) {
            if ('fssp_person' == $source['checktype']) {
                if (isset($source->ResultsCount)) {
                    if ((int) $source->ResultsCount > 0) {
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
