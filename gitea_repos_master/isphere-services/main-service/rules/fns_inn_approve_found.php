<?php

// Одобрение при найденном ИНН
$rulesfunc['fns_inn_approve_found'] = function () use (&$xml) {
    $resp = [
        'result' => 'ERROR',
        'message' => 'No data for rule',
    ];
    if (isset($xml->Source)) {
        foreach ($xml->Source as $source) {
            if ('fns_inn' == $source['checktype']) {
                $resp['result'] = 'ERROR';
                $resp['message'] = 'Error while searching INN';
                foreach ($source->Record as $record) {
                    foreach ($record->Field as $field) {
                        if ('ResultCode' == $field->FieldName) {
                            if ('FOUND' == $field->FieldValue) {
                                $resp['result'] = 'APPROVE';
                                $resp['message'] = 'INN found';
                            } else {
                                $resp['result'] = 'NEXT';
                                $resp['message'] = 'INN not found';
                            }
                        }
                    }
                }
            }
        }
    }

    return $resp;
};
