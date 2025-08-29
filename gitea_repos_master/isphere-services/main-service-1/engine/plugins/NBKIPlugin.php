<?php

class NBKIPlugin implements PluginInterface
{
    public function getName()
    {
        return 'NBKI';
    }

    public function getTitle()
    {
        return 'Кредитная история НБКИ';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
//        $swapData = $rContext->getSwapData();

        if( !isset($initData['passport_series']) ){
	      $initData['passport_series'] = '0000';
	}
	if( !isset($initData['passport_number']) ){
	      $initData['passport_number'] = '000000';
        }
	if(!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']))
        {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (фамилия, имя, дата рождения)');

            return false;
        }
	if(!preg_match("/^\d{2}\.\d{2}\.\d{4}$/", $initData['date'])){
            $rContext->setFinished();
            $rContext->setError('Указана неверная дата рождения');

            return false;
	} else {
            $tmparr = explode('.',$initData['date']);
            $initData['date'] = $tmparr[2].'-'.$tmparr[1].'-'.$tmparr[0];
        }

        $tmpArray = array();
        $homeaddressArr = array();
	$regaddressArr = array();
//	print_r($initData);

        if(isset($initData['homeaddressArr'])){
	          $tmpArray = json_decode($initData['homeaddressArr'], true);
	}
	
	$homeaddressArr['prov'] = isset($tmpArray['kladr_id']) ? substr($tmpArray['kladr_id'],0,2) : '';
	$homeaddressArr['postal'] = ( isset($tmpArray['postal_code']) && preg_match("/^\d{6}$/", $tmpArray['postal_code'] )) ? $tmpArray['postal_code'] : '000000';
	$homeaddressArr['city'] = ( isset($tmpArray['city']) && $tmpArray['city'] != '') ? $tmpArray['city'] : ( (isset($tmpArray['settlement']) && $tmpArray['settlement'] != '') ? $tmpArray['settlement'] : '-' );
	$homeaddressArr['street'] = ( isset($tmpArray['street']) && $tmpArray['street'] != '') ? $tmpArray['street'] : '-';
	$homeaddressArr['block'] = ( isset($tmpArray['block']) && $tmpArray['block'] != '') ? $tmpArray['block'] : '';
	$homeaddressArr['district'] = ( isset($tmpArray['area']) && $tmpArray['area'] != '') ? $tmpArray['area'] : '';
	$homeaddressArr['houseNumber'] = ( isset($tmpArray['house']) && $tmpArray['house'] != '') ? $tmpArray['house'] : '18';
	$homeaddressArr['apartment'] = ( isset($tmpArray['flat']) && $tmpArray['flat'] != '') ? $tmpArray['flat'] : '12';
	
	if(isset($initData['regaddressArr'])){
	          $tmpArray = json_decode($initData['regaddressArr'], true);
	}
	else{
	       $tmpArray = array();
	}

       $regaddressArr['prov'] = isset($tmpArray['kladr_id']) ? substr($tmpArray['kladr_id'],0,2) : '';
       $regaddressArr['postal'] = ( isset($tmpArray['postal_code']) && preg_match("/^\d{6}$/", $tmpArray['postal_code'])) ? $tmpArray['postal_code'] : '000000';
       $regaddressArr['city'] = ( isset($tmpArray['city']) && $tmpArray['city'] != '') ? $tmpArray['city'] : ( (isset($tmpArray['settlement']) && $tmpArray['settlement'] != '') ? $tmpArray['settlement'] : '-' );
       $regaddressArr['street'] = ( isset($tmpArray['street']) && $tmpArray['street'] != '') ? $tmpArray['street'] : '-';
       $regaddressArr['block'] = ( isset($tmpArray['block']) && $tmpArray['block'] != '') ? $tmpArray['block'] : '';
       $regaddressArr['district'] = ( isset($tmpArray['area']) && $tmpArray['area'] != '') ? $tmpArray['area'] : '';
       $regaddressArr['houseNumber'] = ( isset($tmpArray['house']) && $tmpArray['house'] != '') ? $tmpArray['house'] : '18';
       $regaddressArr['apartment'] = ( isset($tmpArray['flat']) && $tmpArray['flat'] != '') ? $tmpArray['flat'] : '12';

//       print_r($homeaddressArr);
//       print_r($regaddressArr);
//       exit;

       if(isset($initData['issueDate']) && preg_match("/^\d{2}\.\d{2}\.\d{4}$/", $initData['issueDate'])){
             $tmpDatArr = explode('.', $initData['issueDate']);
	     $initData['issueDate'] = $tmpDatArr[2].'-'.$tmpDatArr[1].'-'.$tmpDatArr[0];
       }
       else{
             $initData['issueDate'] = '2014-03-08';
       }

        $params = array(
            'idNum' => $initData['passport_number'],
            'seriesNumber' => $initData['passport_series'],
            'name1' => $initData['last_name'],
            'first' => $initData['first_name'],
            'paternal' => $initData['patronymic'],
	    'birthDt' => $initData['date'],
	    'placeOfBirth' => ( isset( $initData['placeOfBirth'] ) && $initData['placeOfBirth'] != '' ) ? $initData['placeOfBirth'] : '-',
	    'issueDate' => $initData['issueDate'],
	    'issueAuthority' => ( isset( $initData['issueAuthority'] ) && $initData['issueAuthority'] ) ? $initData['issueAuthority'] : '-' ,
	    'homeADDRESS' => $homeaddressArr,
	    'regADDRESS' => $regaddressArr,
        );


        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'http://91.203.194.58/nbki/nbki.php';
        if($ch){

        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_REFERER, $url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $rContext->setCurlHandler($ch);
        }
        return true;
    }

    public function computeRequest(&$rContext)
    {
        $curlError = curl_error($rContext->getCurlHandler());

        if($curlError)
        {
            $rContext->setFinished();
            $rContext->setError($curlError==''?'Превышено количество попыток получения ответа':$curlError);

            return false;
        }

        $content = curl_multi_getcontent($rContext->getCurlHandler());
	if(@ $xml = simplexml_load_string($content) ){
	     $dir = 'nbkiDATA/'.date('d-m-Y');
	     if(!is_dir($dir)){
	        @  mkdir($dir);
	     }
	     $file_name = time().'_'.rand(1000, 10000).'.xml';
	     file_put_contents($dir.'/'.$file_name, $content);
             global $serviceurl;
	     $data['xml_link'] = new ResultDataField('url','xml_link',$serviceurl.$dir.'/'.$file_name,'Ссылка на XML','Ссылка на ответ в xml');
	     $data['html_link'] = new ResultDataField('url','html_link',$serviceurl.'nbki/nbki.php?date='.date('d-m-Y').'&nbkiF='.$file_name,'Ссылка на просмотр','Ссылка на просмотр кредитной истории');
	     if( isset($xml->preply ) ){
	            if( isset ( $xml->preply->err->ctErr ) && !isset ( $xml->preply->report ) ){
		              $rContext->setFinished();			      
		              $rContext->setError('NBKI Error: Code '.$xml->preply->err->ctErr->Code.', Text'.$xml->preply->err->ctErr->Text);
			      return false;
		    }
		    else{
		              $status = 'none';
			      		    
		              $report = $xml->preply->report;
			      foreach ($report->PersonReply as $person) {
			        if (!isset($data['doc_birthplace']) && (strlen($person->placeOfBirth)>2))
				  $data['doc_birthplace'] = new ResultDataField('string','doc_birthplace',$person->placeOfBirth,'Место рождения','Место рождения');
			      }
			      
			      foreach ($report->IdReply as $id) {
    			        if ($id->seriesNumber)
			          $id->seriesNumber = preg_replace("/ /","",$id->seriesNumber);
			        if ($id->idNum)
			          $id->idNum = preg_replace("/ /","",$id->idNum);
			        if (($id->idType==21) && !isset($data['doc_number']) && (strlen($id->issueAuthority)>2)) {
				  $data['doc_series'] = new ResultDataField('string','doc_series',$id->seriesNumber,'Серия паспорта','Серия паспорта');
				  $data['doc_number'] = new ResultDataField('string','doc_number',$id->idNum,'Номер паспорта','Номер паспорта');
				  $data['doc_date'] = new ResultDataField('string','doc_date',$id->issueDate,'Дата выдачи паспорта','Дата выдачи паспорта');
				  $data['doc_issuer'] = new ResultDataField('string','doc_issuer',$id->issueAuthority,'Кем выдан паспорт','Кем выдан паспорт');
			        }
			        if (($id->idType==31) && !isset($data['drv_number']) && (strlen($id->issueAuthority)>2)) {
				  $data['drv_number'] = new ResultDataField('string','drv_number', $id->seriesNumber.$id->idNum,'Номер в/у','Номер водительского удостоверения');
				  $data['drv_date'] = new ResultDataField('string','drv_date',$id->issueDate,'Дата выдачи в/у','Дата выдачи водительского удостоверения');
				  $data['drv_issuer'] = new ResultDataField('string','drv_issuer',$id->issueAuthority,'Кем выдано в/у','Кем выдано водительское удостоверение');
			        }
			      }
			      
			      foreach ($report->AddressReply as $address) {
			        if (($address->addressType==1) && !isset($data['reg_fulladdress']))
				  $data['reg_fulladdress'] = new ResultDataField('string','reg_fulladdress',trim($address->provText . /*' ' . $address->city .*/ ' ' . $address->street . ' ' . $address->houseNumber . ' ' .$address->apartment),'Адрес регистрации','Адрес регистрации');
			        if (($address->addressType==2) && !isset($data['home_fulladdress']))
				  $data['home_fulladdress'] = new ResultDataField('string','home_fulladdress',trim($address->provText . /*' ' . $address->city .*/ ' ' . $address->street . ' ' . $address->houseNumber . ' ' .$address->apartment),'Адрес места жительства','Адрес места жительства');
			      }
			      
			      foreach ($report->PhoneReply as $phone) {
			        if ($phone->number)
			          $phone->number = preg_replace("/\D/","",$phone->number);
			        if ($phone->number && (strlen($phone->number)==11) && ((substr($phone->number,0,1)=='7') || (substr($phone->number,0,1)=='8')))
			          $phone->number = substr($phone->number,1);
			        if (($phone->phoneType==2) && !isset($data['home_phone']))
				  $data['home_phone'] = new ResultDataField('string','home_phone',$phone->number,'Домашний телефон','Домашний телефон');
			        if (($phone->phoneType==1) && !isset($data['work_phone']))
				  $data['work_phone'] = new ResultDataField('string','work_phone',$phone->number,'Рабочий телефон','Рабочий телефон');
			      }
			      
			      foreach ($report->AccountReply as $account) {
			        $last=substr(strtr($account->paymtPat,array('X'=>'')),0,1);
			      //  if ((strpos($account->paymtPat,'555')!==false) ||
			        if (($last=='5') ||
			            (strpos($account->paymtPat,'7')!==false) ||
			            (strpos($account->paymtPat,'8')!==false) ||
			            (strpos($account->paymtPat,'9')!==false))
			          $status = 'bad';
			      }
			        if ($status=='none') $status = 'good';
				$data['status'] = new ResultDataField('string','status',$status,'Статус','Статус');
			      $rules_result = '';
			      if(isset($report->AccountReply)){
			            require(__DIR__.'/rules/config.php');
			            foreach( $rulenames as $rulename ){
			                  @include(__DIR__.'/rules/'.$rulename.'.php');
			                  if(isset($rules[$rulename])){
			                         $rules_result .= $rulename.': '.$rules[$rulename]().';';
			                  }
			                  else{
			                         $rules_result .= $rulename.': cant process this rule';
			                  }
			            }
				    $data['rules_result'] = new ResultDataField('string','rules_result',$rules_result,'Результат','Результат');
			      }

                              $resultData = new ResultDataList();
                              $resultData->addResult($data);
                              $rContext->setResultData($resultData);
	                      $rContext->setFinished();						      
		    }
	     }
	     else{
	             $rContext->setFinished();
		     $rContext->setError('Trash from NBKI');
	     }
	}
	else{
	     $rContext->setFinished();
	     $rContext->setError('Trash from NBKI');
	}
    }
}

?>