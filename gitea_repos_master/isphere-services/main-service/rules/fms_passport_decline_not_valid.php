<?php

// Отказ при недействительном паспорте
$rulesfunc['fms_passport_decline_not_valid'] = function () use (&$xml) {
    $resp = [
        'result' => 'ERROR',
        'message' => 'No data for rule',
    ];
    if (isset($xml->Source)) {
        foreach ($xml->Source as $source) {
            if ('FMS' == $source->Name) {
                $resp['result'] = 'ERROR';
                $resp['message'] = 'Error while checking passport';
                foreach ($source->Record as $record) {
                    foreach ($record->Field as $field) {
                        if ('ResultCode' == $field->FieldName) {
                            if ('NOT_VALID' == $field->FieldValue) {
                                $resp['result'] = 'DECLINE';
                                $resp['message'] = 'Passport is not valid';
                            } else {
                                $resp['result'] = 'NEXT';
                                $resp['message'] = 'Passport is valid';
                            }
                        }
                    }
                }
            }
        }
    }

    return $resp;
};
