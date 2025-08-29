<?php

// Одобрение при найденном профиле VK
$rulesfunc['vk_person_approve_found'] = function () use (&$xml) {
    $resp = [
        'result' => 'ERROR',
        'message' => 'No data for rule',
    ];
    if (isset($xml->Source)) {
        foreach ($xml->Source as $source) {
            if ('vk_person' == $source['checktype']) {
                if (isset($source->ResultsCount)) {
                    if ((int) $source->ResultsCount > 0) {
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
