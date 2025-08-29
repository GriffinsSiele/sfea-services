<?php

// Отказ в оcтальных случях
$rulesfunc['decline_other'] = function() use (&$xml) {
    $resp = array(
        'result' => 'DECLINE',
        'message' => 'Approval conditions are not met',
    );
    return $resp;
};

?>