<?php

class CROINFORMPlugin implements PluginInterface
{
    private $passport_region_series = array(
        '79' => '01',
        '84' => '02',
        '80' => '03',
        '81' => '04',
        '82' => '05',
        '26' => '06',
        '83' => '07',
        '85' => '08',
        '91' => '09',
        '86' => '10',
        '87' => '11',
        '88' => '12',
        '89' => '13',
        '98' => '14',
        '90' => '15',
        '92' => '16',
        '93' => '17',
        '94' => '18',
        '95' => '19',
        '96' => '20',
        '97' => '21',
        '01' => '22',
        '03' => '23',
        '04' => '24',
        '05' => '25',
        '07' => '26',
        '08' => '27',
        '10' => '28',
        '11' => '29',
        '12' => '30',
        '14' => '31',
        '15' => '32',
        '17' => '33',
        '18' => '34',
        '19' => '35',
        '20' => '36',
        '24' => '37',
        '25' => '38',
        '27' => '39',
        '29' => '40',
        '30' => '41',
        '32' => '42',
        '33' => '43',
        '34' => '44',
        '37' => '45',
        '38' => '46',
        '41' => '47',
        '42' => '48',
        '44' => '49',
        '46' => '50',
        '47' => '51',
        '22' => '52',
        '49' => '53',
        '50' => '54',
        '52' => '55',
        '53' => '56',
        '54' => '57',
        '56' => '58',
        '57' => '59',
        '58' => '60',
        '60' => '61',
        '61' => '62',
        '36' => '63',
        '63' => '64',
        '64' => '65',
        '65' => '66',
        '66' => '67',
        '68' => '68',
        '28' => '69',
        '69' => '70',
        '70' => '71',
        '71' => '72',
        '73' => '73',
        '75' => '74',
        '76' => '75',
        '78' => '76',
        '45' => '77',
        '40' => '78',
        '99' => '79',
        '43' => '80',
        '48' => '81',
        '51' => '82',
        '55' => '83',
        '59' => '84',
        '62' => '85',
        '67' => '86',
        '77' => '87',
        '72' => '88',
        '74' => '89',
    );

    public function getName()
    {
        return 'CROINFORM';
    }

    public function getTitle()
    {
        return 'Проверка в системе Экспертиза Кронос-Информ';
    }

    public function getRegionCode($code)
    {
        return $this->passport_region_series[$code];
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(
            !isset($initData['passport_series'])
            || !isset($initData['passport_number'])
	    || !isset($initData['last_name']) 
	    || !isset($initData['first_name'])
	    || !isset($initData['patronymic'])
	    || !isset($initData['date'])
        )
        {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (серия и номер паспорта, фамилия, имя, дата рождения)');

            return false;
        }

        $initData = $rContext->getInitData();
	

        $postvars = array();
        $postvars['SurName'] =  $initData['last_name'];
        $postvars['FirstName'] =  $initData['first_name'];
        $postvars['MiddleName'] =  $initData['patronymic'];
        $postvars['DateOfBirth'] =  $initData['date']; if(!$postvars['DateOfBirth']) $postvars['DateOfBirth'] = '01.01.1980';
        $postvars['Seria'] =  $initData['passport_series'];
        $postvars['Number'] = $initData['passport_number'];
        $postvars['Exp'] = 1;
        $postvars['RegionExp'] =  77;
        $postvars['CityExp'] =  isset($_REQUEST['reg_city']) ? $_REQUEST['reg_city'] : '-';
        $postvars['StreetExp'] =  isset($_REQUEST['reg_street']) ? $_REQUEST['reg_street'] : '-';
        $postvars['HouseExp'] =  22;
        $postvars['OrgExp'] =  0;
	
							

        $ch = $rContext->getCurlHandler();
	
	$url = 'http://91.203.194.58:8032';
	if($ch){
                curl_setopt($ch, CURLOPT_URL, $url);
//              curl_setopt($ch, CURLOPT_REFERER, $url);
				
	        curl_setopt($ch, CURLOPT_POST, true);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postvars));
					
	        $rContext->setCurlHandler($ch);
        }


        return true;
    }

    public function computeRequest(&$rContext)
    {
             $curlError = curl_error($rContext->getCurlHandler());
             if($curlError){
                   $rContext->setFinished();
                   $rContext->setError($curlError==''? 'Unknown Error' : $curlError);
                   return false;
             }

             $content = curl_multi_getcontent($rContext->getCurlHandler());

             global $serviceurl;
             $data['Crourl'] = new ResultDataField('url','Cronos', $serviceurl.'cronos/cronos.php?file='.$content.'.xml', 'Cronos', 'Cronos');

             $resultData = new ResultDataList();
             $resultData->addResult($data);
             $rContext->setResultData($resultData);
             $rContext->setFinished();
   }
}

?>