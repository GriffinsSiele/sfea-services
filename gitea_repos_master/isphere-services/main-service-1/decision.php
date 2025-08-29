<?php

function make_decision($xmlstr,$rules){
    @ $xml = new SimpleXMLElement($xmlstr);
    if(!$xml){
           return false;
    }
    $decision = $xml->addChild('Decision');
    $result = '';

    $ruleslog = $decision->addChild('RulesLog');
    foreach($rules as $rule_code){
        $rule = $ruleslog->addChild('Rule');
        if(file_exists(__DIR__.'/rules/'.$rule_code.'.php')){
            require_once(__DIR__.'/rules/'.$rule_code.'.php');
            $rule->addChild('RuleCode',$rule_code);
            if (isset($rulesfunc[$rule_code])) {
                if ($ruleres=$rulesfunc[$rule_code]()) {
                    $rule->addChild('RuleResult',isset($ruleres['result'])?$ruleres['result']:'');
                    $rule->addChild('RuleMessage',isset($ruleres['message'])?$ruleres['message']:'');
                } else {
                    $rule->addChild('RuleResult','ERROR');
                    $rule->addChild('RuleMessage','Error while executing rule');
                }
                if (isset($ruleres['result']) && ($ruleres['result']=='APPROVE' || $ruleres['result']=='DECLINE')) {
                    $result = $ruleres['result'];
                    break;
                }
            } else {
                $rule->addChild('RuleCode',$rule_code);
                $rule->addChild('RuleResult','ERROR');
                $rule->addChild('RuleMessage','Rule was not loaded');
            }
        } else {
            $rule->addChild('RuleCode',$rule_code);
            $rule->addChild('RuleResult','ERROR');
            $rule->addChild('RuleMessage','Invalid rule');
        }
    }

    $decision->addChild('Result', $result);
    return $xml->saveXML();
}

?>