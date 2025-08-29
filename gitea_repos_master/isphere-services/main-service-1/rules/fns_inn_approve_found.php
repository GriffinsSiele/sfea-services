<?php

// Одобрение при найденном ИНН
$rulesfunc['fns_inn_approve_found'] = function() use (&$xml) {
     $resp = array(
         'result' => 'ERROR',
         'message' => 'No data for rule',
     );
     if(isset($xml->Source)){
           foreach($xml->Source as $source){
                if($source['checktype'] == 'fns_inn'){
                     $resp['result'] = 'ERROR';
                     $resp['message'] = 'Error while searching INN';
                     foreach($source->Record as $record){
                          foreach($record->Field as $field){
                               if($field->FieldName == 'ResultCode') {
                                    if ($field->FieldValue == 'FOUND') {
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

?>