<?php

// Одобрение при найденном профиле OK
$rulesfunc['ok_person_approve_found'] = function () use (&$xml) {
    $resp = [
        'result' => 'ERROR',
        'message' => 'No data for rule',
    ];
    if (isset($xml->Source)) {
        foreach ($xml->Source as $source) {
            if ('ok_person' == $source['checktype']) {
                if (isset($source->ResultsCount)) {
                    if ((int) $source->ResultsCount > 0) {
                        $resp['result'] = 'APPROVE';
                        $resp['message'] = 'OK profile found';
                    } else {
                        $resp['result'] = 'NEXT';
                        $resp['message'] = 'OK profile not found';
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
