<?php

use PhpOffice\PhpSpreadsheet\Writer\Csv;

function is_inn($inn) {
//	if(!preg_match("/^\d{12}$/", $inn)){
//		return false;
//	}
	if (preg_match("/^(\d{12})$/", $inn, $m)) {
		$inn = $m[0];
		$code11 = (($inn[0] * 7 + $inn[1] * 2 + $inn[2] * 4 + $inn[3] *10 + $inn[4] * 3 + $inn[5] * 5 + $inn[6] * 9 + $inn[7] * 4 + $inn[8] * 6 + $inn[9] * 8) % 11 ) % 10;
		$code12 = (($inn[0] * 3 + $inn[1] * 7 + $inn[2] * 2 + $inn[3] * 4 + $inn[4] *10 + $inn[5] * 3 + $inn[6] * 5 + $inn[7] * 9 + $inn[8] * 4 + $inn[9] * 6 + $inn[10]* 8) % 11 ) % 10;
		if ($code11 == $inn[10] && $code12 == $inn[11]) return $inn;
	}
	return false;
}

function is_orginn($inn) {
//	if(!preg_match("/^\d{10}$/", $inn)){
//		return false;
//	}
	if (preg_match("/^(\d{10})$/", $inn, $m)) {
		$inn = $m[0];
		$code10 = (($inn[0] * 2 + $inn[1] * 4 + $inn[2] *10 + $inn[3] * 3 + $inn[4] * 5 + $inn[5] * 9 + $inn[6] * 4 + $inn[7] * 6 + $inn[8] * 8) % 11 ) % 10;
		if ($code10 == $inn[9]) return $inn;
	}
	return false;
}

function is_birthDay($str){
	if(preg_match("/^\d{5}$/", trim($str))){
		require_once 'vendor/autoload.php';
		$str =  date('d.m.Y', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp(trim($str)));
	}

	if(strtotime($str) && (time() - strtotime($str) > 567648000)){
		return 1;
	}
	return 0;
}

function is_passport($str){
	if(preg_match("/^\d{10}$/", preg_replace("/\s+/", '', $str))){
		return 1;
	}
	return 0;
}

function is_passportNumber($str){
	if(preg_match("/^\d{6}$/", preg_replace("/\s+/", '', $str))){
		return 1;
	}
	return 0;
}

function is_passportSerial($str){
	if(preg_match("/^\d{4}$/", preg_replace("/\s+/", '', $str))){
		return 1;
	}
	return 0;
}

function is_region($str){
	if(preg_match("/^\d{,2}$/", preg_replace("/\s+/", '', $str))){
		return 1;
	}
	return 0;
}

function is_LastName($str){
	if(preg_match("/^[а-яё]+(ова?|ева?|ёва?|ина?|ына?|их|ых|ский|цкий|ская|цкая|юк|ук|нко|дзе|швили|ян|ик|ко|ан)$/ui", trim($str))){
		return 1;
	}
	return 0;
}

function is_Patronymic($str){
	if(preg_match("/^[а-яё]+(вич|вна|чна|ьич)$/ui", trim($str))){
		return 1;
	}
	return 0;
}

function is_FirstName($str){
	if(strlen(trim($str))>=3 && preg_match("/^[а-яё]+$/ui", trim($str)) && !is_LastName($str) && !is_Patronymic($str)){
		return 1;
	}
	return 0;
}

function is_FIO($str){
	$str = preg_replace("/\s+/", ' ', trim($str));
	if(preg_match("/^[а-яё]+(ова?|ева?|ёва?|ина?|ына?|их|ых|ский|цкий|ская|цкая|юк|ук|нко|дзе|швили|ян|ик|ко|ан) [а-яё]+ [а-яё]+(вич|вна|чна|ьич)$/ui", trim($str))){
		return 1;
	}
	return 0;
}

function is_IOF($str){
	$str = preg_replace("/\s+/", ' ', trim($str));
	if(preg_match("/^[а-яё]+ [а-яё]+(вич|вна|чна|ьич) [а-яё]+(ова?|ева?|ёва?|ина?|ына?|их|ых|ский|цкий|ская|цкая|юк|ук|нко|дзе|швили|ян|ик|ко|ан)$/ui", trim($str))){
		return 1;
	}
	return 0;
}

function is_phone($str){
    $str = preg_replace("/[\s\(\)\-]+/","",trim($str));
    if (!preg_match("/^[\+]*[\d]+$/",$str)) return 0;
    $plus = substr($str,0,1)=='+';
    if (!$plus && (strlen($str)==11) && (substr($str,0,1)=='8')) $str = '7'.substr($str,1);
    if (!$plus && (strlen($str)==10)) $str = '7'.$str;
    return strlen($str)>=11 && strlen($str)<=13? 1 : 0;  
}

function is_email($str)
{
    $str = trim(strtr(mb_strtolower($str),array('~'=>'','№'=>'','!'=>'','#'=>'','$'=>'','%'=>'','^'=>'','&'=>'','*'=>'','('=>'',')'=>'','['=>'',']'=>'','{'=>'','}'=>'','+'=>'','='=>'','"'=>'',"'"=>'','`'=>'','<'=>'','>'=>'','/'=>'','|'=>'','\\'=>'',','=>'',';'=>'',':'=>'','?'=>'',' '=>'','​'=>'','а'=>'a','в'=>'b','с'=>'c','е'=>'e','н'=>'h','к'=>'k','м'=>'m','о'=>'o','п'=>'n','р'=>'p','т'=>'t','у'=>'y','х'=>'x')));
    return preg_match("/^[0-9a-z][0-9a-z\-\._]*\@[0-9a-z][0-9a-z\-\._]+$/",$str) ? 1 : 0;
}

function is_vin($str)
{
    $str = trim(strtr(mb_strtoupper(html_entity_decode($str,ENT_COMPAT,"UTF-8")),array('ОТСУТСТВУЕТ'=>'',' '=>'','​'=>'','I'=>'1','O'=>'0','Q'=>'0','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'0','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'0','р'=>'p','т'=>'t','у'=>'y','х'=>'x')));
    return preg_match("/^[A-HJ-NPR-Z0-9]{17}$/i",$str) ? 1 : 0;
}

function is_other_title($str) {
   return (($str=='Партнер') || ($str=='Номер соглашения'));
}

function is_id($str) {
    static $ids = array();
    $unique = !array_search($str,$ids);
    if ($unique && sizeof($ids)<10000) $ids[] = $str;
    return strlen($str)<=20 && (preg_match("/\d{3}/", $str) || preg_match("/^[\d]+$/", $str)) && $unique;
}

function preWork($workDir){
	if(is_dir($workDir)){
//		foreach($sources as $source){
//			@mkdir('data/'.$source.'/'.$workDir);
//			@mkdir('data/'.$source.'/'.$workDir.'/data');
//		}
		$orgDir = __DIR__;
		chdir($workDir);
		$xlsxFiles = glob("request.*");
//		print_r($xlsxFiles);

//		$xlsx = '';
		$xlsx = $xlsxFiles[0];

//			if(count($xlsxFiles)){
//				$xlsx = $xlsxFiles[0];
//			}else{
//				$xlsxFiles = glob("*.csv");
//				$xlsx = $xlsxFiles[0];
//			}

		if(!$xlsx){
			echo "Нет файла!!!  Аварийный выход...\n";
			chdir($orgDir);
			exit;
		}

		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $xlsx );
		$worksheet = $spreadsheet->getActiveSheet();
		$rows = $worksheet->toArray(null,false);

		$typesArray = array();
		$typesKeys = array('id', 'other', 'lastName', 'firstName', 'patronymic', 'fio', 'iof', 'bDate', 'region_id', 'serial', 'number', 'passport', 'inn', 'orginn', 'phone', 'email', 'vin');

		while(isset($rows[0]) && is_array($rows[0]) && !preg_match("/\d{4}/", implode('', $rows[0])) && !preg_match("/@/", implode('', $rows[0]))) { // Заголовки и пустые строки
			foreach($rows[0] as $key => $item)
				if (is_other_title($item)) $typesArray[$key]['other'] = 0; // Проверяем заголовок на доп.идентификатор
//			file_put_contents($workDir.'titles.txt', implode(',', $rows[0]));
			$rows = array_slice($rows, 1); // Удаляем заголовок из данных
		}
//		print_r($typesArray);
//		echo "<br>\n";
//		file_put_contents($workDir.'fields_titles.txt', json_encode($typesArray));

		foreach($rows as $i => $row) {
			if ($i<=10000) foreach($row as $key => $item){
                                $item=trim($item);
				if(is_region($item)){
					@$typesArray[$key]['region_id']++;
				}elseif(is_vin($item)){
					@$typesArray[$key]['vin']++;
				}elseif(is_inn($item)){
					@$typesArray[$key]['inn']++;
				}elseif(is_orginn($item)){
					@$typesArray[$key]['orginn']++;
				}elseif(is_passportSerial($item)){
					@$typesArray[$key]['serial']++;
				}elseif(is_passportNumber($item)){
					@$typesArray[$key]['number']++;
				}elseif(is_passport($item)){
					@$typesArray[$key]['passport']++;
				}elseif(is_birthDay($item)){
					@$typesArray[$key]['bDate']++;
				}elseif(is_Patronymic($item)){
					@$typesArray[$key]['patronymic']++;
				}elseif(is_LastName($item)){
					@$typesArray[$key]['lastName']++;
				}elseif(is_FirstName($item)){
					@$typesArray[$key]['firstName']++;
				}elseif(is_FIO($item)){
					@$typesArray[$key]['fio']++;
				}elseif(is_IOF($item)){
					@$typesArray[$key]['iof']++;
				}elseif(is_phone($item)){
					@$typesArray[$key]['phone']++;
				}elseif(is_email($item)){
					@$typesArray[$key]['email']++;
				}elseif(is_id($item)){
					@$typesArray[$key]['id']++;
				}elseif(isset($typesArray[$key]['other'])){
					@$typesArray[$key]['other']++;
				}elseif($item){
					@$typesArray[$key]['trash']+=@$typesArray[$key]['id']+1;
					@$typesArray[$key]['id']=0;
				}
			}
		}
//		print_r($typesArray);
//		echo "<br>\n";
		file_put_contents($workDir.'fields_auto.txt', json_encode($typesArray));

		foreach($typesArray as $key => $val){
			arsort($val);
//			echo "<br>\n";
//			print_r($val);
			$typesArray[$key] = array_slice($val, 0, 1);
		}
//		print_r($typesArray);
//		echo "<br>\n";
		file_put_contents($workDir.'fields_auto_sorted.txt', json_encode($typesArray));

		$columns = array();
		$check = array();
                $other = 0;
		foreach($typesKeys as $tk){
			foreach($typesArray as $k => $arr){
				if(isset($arr[$tk])){
					if($tk=='other' || !isset($columns[$tk]) || ($arr[$tk] > $check[$tk])){
						$t = $tk.($tk=='other'?$other++:'');
						$columns[$t] = $k;
						$check[$t] = isset($arr[$t])?$arr[$t]:0;
					}
				}
			}
		}
//		print_r($columns);
//		echo "<br>\n";
		file_put_contents($workDir.'fields_detected.txt', json_encode($columns));
/*
		$columns['id'] = 0;
		foreach($columns as $cKey => $cVal){
			if($cVal == 0 && $cKey != 'id'){
				if($cKey == 'fio' || $cKey == 'iof' || $cKey == 'lastName' || $cKey == 'firstName' || $cKey == 'inn' || $cKey == 'orginn' || $cKey == 'phone' || $cKey == 'email' || $cKey == 'vin' ){
//					unset($columns[$cKey]);
					unset($columns['id']);
				}else{
					unset($columns[$cKey]);
//					unset($columns['id']);
				}
			}
		}
*/
		file_put_contents($workDir.'fields.txt', json_encode($columns));

		$writer = new Csv($spreadsheet);
		$writer->setLineEnding("\r\n");
		$writer->setPreCalculateFormulas(false);
		$writer->save($workDir.'pre.csv');

		$data = explode("\r\n", file_get_contents($workDir.'pre.csv'));
		$newContent = '';
		foreach($data as $string)
			$newContent .= preg_replace("/\n/","; ",$string)."\n";
		file_put_contents($workDir.'pre.csv', $newContent);

		$forSample=array_slice(explode("\n", file_get_contents($workDir.'pre.csv')),0,10);
		$sample = array();
		foreach($forSample as $string){
			if(preg_match_all( "/\d{1,2}\/\d{1,2}\/\d{4}/", $string, $matches)){
				foreach($matches[0] as $v){
					$string = str_replace($v, date('d.m.Y',strtotime($v)), $string);
				}
			}
			$sample[] = str_getcsv(trim($string));
		}


//		$to_return = array_slice($rows, 0, 10);
//		print_r($to_return);

		//$content = file_get_contents($workDir.'pre.csv');
		//if(preg_match("/\d{1,2}\/\d{1,2}\/\d{4}/", $content)){
		//	//echo "Excel style dates\n";
//			$sample = array();
		//	$data = explode("\n", file_get_contents($workDir.'.csv'));
//			foreach($to_return as $string){
//				if(preg_match_all( "/\d{1,2}\/\d{1,2}\/\d{4}/", $string, $matches)){
//					foreach($matches[0] as $v){
//						$string = str_replace($v, date('d.m.Y',strtotime($v)), $string);
//					}
//				}
//				$sample[] = $string;
//				//file_put_contents($workDir.'pre.csv', $newContent);
//			}
		//}
		//print_r($sample);

		chdir($orgDir);
		return $sample;
	}else{
		//echo "goal doesnt exists!!\n";
		return false;
	}
}

function _process($info, $config, $code, $sources, $key, $dir, $queue){

$xml ="
<Request>
        <UserID>{$config['id']}</UserID>
        <Password>{$config['passwd']}</Password>
        <requestId>".htmlspecialchars($info['id'])."</requestId>
        <requestType>bulk</requestType>
        <timeout>590</timeout>
        <sources>".$sources."</sources>" .
(!isset($info['last_name']) && !isset($info['number']) && !isset($info['inn']) ? "" : "
        <PersonReq>" .
(!isset($info['last_name']) ? "" : "
            <paternal>{$info['last_name']}</paternal>"
) . (!isset($info['first_name']) ? "" : "
            <first>{$info['first_name']}</first>"
) . (!isset($info['patronymic']) ? "" : "
            <middle>{$info['patronymic']}</middle>"
) . (!isset($info['date']) || !$info['date'] ? "" : "
            <birthDt>{$info['date']}</birthDt>"
) . (!isset($info['number']) || !$info['number'] ? "" : "
            <passport_series>{$info['serial']}</passport_series>
            <passport_number>{$info['number']}</passport_number>"
) . (!isset($info['issue_date']) || !$info['issue_date'] ? "" : "
            <issueDate>{$info['issue_date']}</issueDate>"
//) . (!isset($info['region_id']) || !$info['region_id'] ? "" : "
//            <region_id>{$info['region_id']}</region_id>"
) . (!isset($info['inn']) || !$info['inn'] ? "" : "
            <inn>{$info['inn']}</inn>"
) . "
        </PersonReq>"
) . (!isset($info['orginn']) ? "" : "
        <OrgReq>" .
(!isset($info['orginn']) || !$info['orginn'] ? "" : "
            <inn>{$info['orginn']}</inn>"
) . "
        </OrgReq>" 
) . (!isset($info['mobile_phone']) || !$info['mobile_phone'] ? "" : "
        <PhoneReq>
            <phone>{$info['mobile_phone']}</phone>
        </PhoneReq>"
) . (!isset($info['home_phone']) || !$info['home_phone'] ? "" : "
        <PhoneReq>
            <phone>{$info['home_phone']}</phone>
        </PhoneReq>"
) . (!isset($info['work_phone']) || !$info['work_phone'] ? "" : "
        <PhoneReq>
            <phone>{$info['work_phone']}</phone>
        </PhoneReq>"
) . (!isset($info['additional_phone']) || !$info['additional_phone'] ? "" : "
        <PhoneReq>
            <phone>{$info['additional_phone']}</phone>
        </PhoneReq>"
) . (!isset($info['email']) || !$info['email'] ? "" : "
        <EmailReq>
            <email>{$info['email']}</email>
        </EmailReq>"
) . (!isset($info['vin']) || !$info['vin'] ? "" : "
        <CarReq>" .
(is_vin($info['vin']) ? "
            <vin>{$info['vin']}</vin>" : "
            <bodynum>{$info['vin']}</bodynum>"
) . "
        </CarReq>" 
) . "
</Request>";
	file_put_contents('/opt/forReq/'.$queue.'/'.$dir.'-row'.str_pad($key+1,7,'0',STR_PAD_LEFT).'___'.$code.'___'.($key+1).'.qwe', $xml);
	return true;
}

function getConf(){
	$files = glob("*.conf");
	if(count($files) != 1){
		return false;
	}
	return $files[0];
}

function getConfig($confFile){
	require($confFile);
	$config = array();
	$config['id'] = $id;
	$config['passwd'] = $passwd;
	$config['limit'] = $limit;
	$config['serviceurl'] = $serviceurl;
	$config['sources'] = explode(',', $sources);
	$config['codes'] = array();
	$config['checktypes'] = array();
	foreach ($config['sources'] as $source) {
		preg_match("/^([^_]+)/",$source,$matches);
		$code = $matches[1];
		if (in_array($code,array('bankrot','facebook','fns','fssp','google','kad','minjust','notariat','ok','samsung','skype','vk','zakupki'))) {
			if (!isset($config['checktypes'][$code])) {
				$config['codes'][] = $code;
				$config['checktypes'][$code] = $source;
			} else {
				$config['checktypes'][$code] .= ','.$source;
			}
		} elseif (!isset($config['checktypes'][$source])) {
			$config['codes'][] = $source;
			$config['checktypes'][$source] = $source;
		}
	}
	return $config;
}

function telegramMsg($msg){
	$serviceurl = "https://api.telegram.org/bot2103347962:AAHMdZY-Bh6ELR-NB7qOapnnD7sbh2c3bsQ/sendMessage?chat_id=-1001662664995";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $serviceurl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "text=".urlencode($msg));
	curl_setopt($ch, CURLOPT_POST, 1);

	$data = curl_exec($ch);
	$answer = $data;
	curl_close($ch);
}

function checkSource($source, $fields=array()){
//	echo $source."<br>";
//	print_r($fields);
	$sources = array();
	if((isset($fields['passport']) || (isset($fields['serial']) && isset($fields['number']))) && isset($fields['bDate']) && 
	   (isset($fields['fio']) || isset($fields['iof']) || (isset($fields['lastName']) && isset($fields['firstName']) && isset($fields['patronymic'])))){
		$sources = array_merge($sources,array('fns','fns_inn','fns_bi','fns_rmsp','fns_invalid','fns_npd'));
	}
        if(isset($fields['bDate']) && (isset($fields['fio']) || isset($fields['iof']) || (isset($fields['lastName']) && isset($fields['firstName'])))){
		$sources = array_merge($sources,array('bankrot','bankrot_person','fssp','fssp_person','notariat','notariat_person','mvd_wanted','fsin_person','minjust_person','terrorist_person','ok_person','ok_url','ok_urlcheck','vk_person','vk_url','reestrzalogov_person'));
	}
	if(isset($fields['inn'])){
		$sources = array_merge($sources,array('2gis_inn','bankrot','bankrot_inn','egrul_person','fns','fns_rmsp','fns_npd','fns_invalid','kad','kad_person','cbr_person','gosuslugi_inn'));
	}
	if(isset($fields['passport']) || (isset($fields['serial']) && isset($fields['number']))){
		$sources = array_merge($sources,array('gosuslugi_passport','fms','fms_passport'));
	}
	if(isset($fields['orginn'])){
		$sources = array_merge($sources,array('2gis_inn','bankrot','bankrot_org','fns','fns_bi','fns_rmsp','fssp','fssp_org','fssp_inn','kad','kad_org','cbr_org','egrul_org','vestnik_org','reestrzalogov_org', 'rsa_org'));
	}
	if(isset($fields['phone'])){
		$sources = array_merge($sources,array('2gis_phone','aeroflot_phone','announcement_phone','apple_phone','avito_phone','boards_phone','callapp_phone','emt_phone','eyecon_phone','facebook_phone','facebook_phoneurl','fotostrana_phone','getcontact_phone','getcontacttags_phone','google_phone','gosuslugi_phone','hlr_phone','honor_phone','huawei_phone','instagram_phone','krasnoebeloe_phone','listorg_phone','microsoft_phone','names_phone','numbuster_phone','ok_phone','ok_phoneapp','ok_phonecheck','ok_url','ok_urlcheck','papajohns_phone','petrovich_phone','phones_phone','pochta_phone','rosneft_phone','rossvyaz_phone','sber_phone','simpler_phone','skype','skype_phone','smsc_phone','twitter_phone','truecaller_phone','viber_phone','vk_phone','vk_phonecheck','vk_url','whatsapp_phone','winelab_phone','yamap_phone','yoomoney_phone','xiaomi_phone'));
	}
        if(isset($fields['phone']) && (isset($fields['fio']) || isset($fields['iof']) || (isset($fields['lastName']) && isset($fields['firstName'])))){
		$sources = array_merge($sources,array('google','google_name'));
	}
        if(isset($fields['phone']) && isset($fields['bDate']) && (isset($fields['fio']) || isset($fields['iof']) || (isset($fields['lastName']) && isset($fields['firstName'])))){
		$sources = array_merge($sources,array('samsung','samsung_name'));
	}
	if(isset($fields['email'])){
		$sources = array_merge($sources,array('aeroflot_email','apple_email','avito_email','facebook_email','facebook_emailurl','fotostrana_email','googleplus_email','google_email','gosuslugi_email','honor_email','huawei_email','instagram_email','mailru_email','microsoft_email','ok_email','ok_emailapp','ok_emailcheck','ok_url','ok_urlcheck','petrovich_email','rzd_email','samsung_email','sber_email','skype','skype_email','twitter_email','vk_email','vk_emailcheck','vk_url','winelab_email','yoomoney_email','xiaomi_email'));
	}
        if(isset($fields['email']) && (isset($fields['fio']) || isset($fields['iof']) || (isset($fields['lastName']) && isset($fields['firstName'])))){
		$sources = array_merge($sources,array('google','google_name'));
	}
        if(isset($fields['email']) && isset($fields['bDate']) && (isset($fields['fio']) || isset($fields['iof']) || (isset($fields['lastName']) && isset($fields['firstName'])))){
		$sources = array_merge($sources,array('samsung','samsung_name'));
	}
	if(isset($fields['vin'])){
		$sources = array_merge($sources,array('gibdd','gibdd_register','gibdd_history','gibdd_aiusdtp','gibdd_restricted','gibdd_wanted','gibdd_diagnostic','reestrzalogov_auto','rsa_policy','elpts','carinfo'));
	}
	return in_array($source,$sources);
}

function doResult($workDir, &$columns, &$data){
	$orgDir = __DIR__;
	chdir($workDir);

	$recordtype = array(
		'reestrzalogov_auto' => 'Type',
		'reestrzalogov_person' => 'Type',
		'gibdd_history' => 'RecordType',
		'numbuster_phone' => 'Type',
		'ok_phoneapp' => 'Type',
		'ok_emailapp' => 'Type',
		'fns_bi' => 'Type',
		'egrul_person' => 'Type',
	);
	$recordtype_merge = false;
	//$recordtype_merge = true;
	$values_delim = ',';

	$dirs = glob("*",GLOB_ONLYDIR);

	$files = array();
	$fields = array();
	$errors = array();
	$startFields = array('row'=>'row');
//	$startFields = array('row'=>'№ строки');

	if(isset($columns['id'])){
	}elseif(isset($columns['inn'])){
		$columns['id'] = $columns['inn'];
	}elseif(isset($columns['orginn'])){
		$columns['id'] = $columns['orginn'];
	}elseif(isset($columns['passport'])){
		$columns['id'] = $columns['passport'];
	}elseif(isset($columns['phone'])){
		$columns['id'] = $columns['phone'];
	}elseif(isset($columns['email'])){
		$columns['id'] = $columns['email'];
	}elseif(isset($columns['vin'])){
		$columns['id'] = $columns['vin'];
	}
	$tmp = str_getcsv($data[0]);
	foreach($columns as $name => $column) {
		if ($name=='id' || substr($name,0,5)=='other') $startFields['_'.$name.'_'] = isset($tmp[$column])?$tmp[$column]:'';
	}

	foreach($dirs as $dir){
		$file = $dir.'/fResult.txt';
		if (!file_exists($file) || !filesize($file)) {
			$fileerr = $dir.'/fResult.err';
			if (!file_exists($fileerr) || !filesize($fileerr)) {
				echo "$file or $fileerr not found\n";
				continue; //exit;
			}
			$file = $fileerr;
		}
		$row = intval($dir);

		$files[$row] = $file;

		echo "Checking $workDir/$file\n";
		$text = file_get_contents($file);
		$text = strtr($text,array('&bull;'=>'','&deg;'=>'',chr(12)=>'','⁨'=>'','⁩'=>'','￿'=>''));
//раскомментировать на случай если в requestId попал мусор, потом вернуть как было
$text = preg_replace("/<requestId>[^<]+<\/requestId>/", "<requestId></requestId>", $text);
$text = preg_replace("/request_id=\"[^>]+/", "request_id=\"\"", $text);
		$xml = new SimpleXMLElement($text);

		foreach($xml->Source as $source){
			$checkType = strval($source['checktype']);
			$sourceName = $checkType;
			if ($sourceName=='bankrot_inn') $sourceName='bankrot_person';
			if(!isset($fields[$sourceName])){
				$fields[$sourceName] = $startFields;

			}

			foreach($source->Record as $record){
				foreach($record->Field as $field){
					if(isset($recordtype[$checkType]) && $field->FieldName==$recordtype[$checkType] && !$recordtype_merge){
						$sourceName = $checkType.'_'.$field->FieldValue;
						if(!isset($fields[$sourceName])){
							$fields[$sourceName] = $startFields;
						}
					}
				}
				foreach($record->Field as $field){
					$fieldName = strval($field->FieldName);
					if(!isset($fields[$sourceName][$fieldName]) && !(isset($recordtype[$checkType]) && $field->FieldName==$recordtype[$checkType])){
						$fields[$sourceName][$fieldName] = $fieldName;
					}
				}
			}

			if (isset($source->Error)) $errors[$sourceName] = '';
		}
	}

//	if (!is_dir('results')) mkdir('results');
/*
	foreach($fields as $sourceName => $sourceFields){
		if (!isset($errors[$sourceName])) unset($fields[$sourceName]['error']);
	}
*/
	foreach($errors as $sourceName => $count){
		$fields[$sourceName.'_errors'] = $startFields;
                $fields[$sourceName.'_errors']['error'] = 'Ошибка';
	}
	foreach($fields as $sourceName => $sourceFields){
		if (!isset($recordtype[$sourceName]) || sizeof($sourceFields)>sizeof($startFields))	{
			file_put_contents($sourceName.'.csv','"'.implode('";"', $sourceFields)."\"\n");
			echo "Created $sourceName.csv\n";
		} else {
			echo "Skipped $sourceName.csv\n";
                }
		foreach ($sourceFields as $fieldName => $val) $fields[$sourceName][$fieldName] = '';
		$content[$sourceName] = '';
		$rows[$sourceName] = 0;
	}

	ksort($files);
	foreach($files as $row => $file){
		echo "Processing $workDir/$file\n";
		$text = file_get_contents($file);
		$text = strtr($text,array('&bull;'=>'','&deg;'=>'',chr(12)=>'','⁨'=>'','⁩'=>'','￿'=>''));
//раскомментировать на случай если в requestId попал мусор, потом вернуть как было
$text = preg_replace("/<requestId>[^<]+<\/requestId>/", "<requestId></requestId>", $text);
$text = preg_replace("/request_id=\"[^>]+/", "request_id=\"\"", $text);
		$xml = new SimpleXMLElement($text);

		foreach($xml->Source as $source){
			$checkType = strval($source['checktype']);
			$sourceName = $checkType;
			$merge = false;
			$i=0;
			if ($sourceName=='bankrot_inn') $sourceName='bankrot_person';
			foreach($source->Record as $record){
				foreach($record->Field as $field){
					if(isset($recordtype[$checkType]) && $field->FieldName==$recordtype[$checkType]){
						if ($recordtype_merge) $merge = true;
						else $sourceName = $checkType.'_'.$field->FieldValue;
					}
				}
				if (!$merge || $i++==0) {
					$values = $fields[$sourceName];
					$values['row'] = $row;
					foreach($columns as $name => $column) {
						if ($name=='id' || substr($name,0,5)=='other') {
							$tmp = str_getcsv($data[$row-1]);
							$values['_'.$name.'_'] = isset($tmp[$column])?$tmp[$column]:'';
						}
					}
				}
				foreach($record->Field as $field){
					$fieldName = strval($field->FieldName);
					if (substr($field->FieldValue,0,10)=='data:image') $field->FieldValue='*';
					if (substr($field->FieldValue,0,1)=='=') $field->FieldValue=' '.$field->FieldValue;
					if (substr($field->FieldValue,strlen($field->FieldValue)-1,1)=='\\') $field->FieldValue=$field->FieldValue.' ';
					$value = strval($field->FieldValue);
					if (!(isset($recordtype[$checkType]) && $field->FieldName==$recordtype[$checkType])/* && (strlen($value)>strlen($values[$fieldName]) || $value!=substr($values[$fieldName],strlen($values[$fieldName])-strlen($value),strlen($value)))*/)
						$values[$fieldName] .= ($values[$fieldName]?$values_delim:'').$value;
				}
				if (!$merge) {
					foreach($values as $fieldName => $value) {
						$values[$fieldName] = '"'.strtr(preg_replace("/\s+/si",' ',$value),array('"'=>'""')).'"';
					}
					$content[$sourceName] .= implode(';',$values)."\n";
				}
			}
			if (isset($source->Error)) {
				$sourceName = $checkType.'_errors';
				$merge = true;
				$values = $fields[$sourceName];
				$values['row'] = $row;
				foreach($columns as $name => $column) {
					if ($name=='id' || substr($name,0,5)=='other') {
						$tmp = str_getcsv($data[$row-1]);
						$values['_'.$name.'_'] = isset($tmp[$column])?$tmp[$column]:'';
					}
				}
				$values['error'] = $source->Error;
			}
			if ($merge) {
				foreach($values as $fieldName => $value) {
					$values[$fieldName] = '"'.strtr(preg_replace("/\s+/si",' ',$value),array('"'=>'""')).'"';
				}
				$content[$sourceName] .= implode(';',$values)."\n";
			}
			if (++$rows[$sourceName]>=1000/* || $row==$maxrow*/) {
				file_put_contents($sourceName.'.csv',$content[$sourceName],FILE_APPEND);
				$content[$sourceName] = '';
				$rows[$sourceName] = 0;
			}
		}
	}
	foreach($fields as $sourceName => $sourceFields)
		if ($content[$sourceName]) file_put_contents($sourceName.'.csv',$content[$sourceName],FILE_APPEND);

	//chdir('results');
	//shell_exec('tar --gzip -c -f results.tar.gz *.csv');
	chdir($orgDir);
	return;
}

function numFormNum($array){
	$result = array();

	if(preg_match("/\"\;\"/", $array[0])){
		$separator = ';';
	}else{
		$separator = '';
	}
	foreach($array as $str){
		if($separator != ''){
			$tmpArr = str_getcsv(trim($str), ';');
		}else{
			$tmpArr = str_getcsv(trim($str));
		}
		foreach($tmpArr as $k => $v){
			if(preg_match("/^\d{12,}$/", trim($v)) && !in_array($k, $result)){
				$result[] = $k;
			}
		}
	}
	return $result;
}

?>