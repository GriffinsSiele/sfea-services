<?php

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

ini_set('memory_limit', '3000M');
require('functions.php');
require 'vendor/autoload.php';
require('../config.php');

function cycle($database){
	$files = glob("/opt/bulk/*/status.txt");

	if(count($files) < 1){
		sleep(30);
		exit;
	}

	foreach($files as $file){
		$tmp = explode('/', $file);
		$dir = $tmp[3];
//		echo $dir."\n";
//		echo $file."\n";
		$status = file_get_contents($file);
		if($status == 0 && file_exists("/opt/bulk/$dir/pre.csv")){ // свежезалитый файл...
			echo "=== Preprocessing $dir ===\n";
			file_put_contents("/opt/bulk/$dir/pre.csv", trim(file_get_contents("/opt/bulk/$dir/pre.csv")));
			$content = file_get_contents("/opt/bulk/$dir/pre.csv");
			if(preg_match("/(^|\s)(\"\"\,)+\"\"\s/", $content)){
				echo "empty lines\n";
				$data = explode("\n", $content);
				$newContent = '';
				foreach($data as $string){
					if(!preg_match("/^(\"\"\,)+\"\"/", $string)){
						$newContent .= $string."\n";
					}
				}
				file_put_contents("/opt/bulk/$dir/pre.csv", $newContent);
			}
			$content = file_get_contents("/opt/bulk/$dir/pre.csv");
			if(preg_match("/\d{1,2}\/\d{1,2}\/\d{4}/", $content)){
				echo "Excel style dates\n";
				$data = explode("\n", $content);
				$newContent = '';
				foreach($data as $string){
					if(preg_match_all( "/\d{1,2}\/\d{1,2}\/\d{4}/", $string, $matches)){
						foreach($matches[0] as $v){
							$string = str_replace($v, date('d.m.Y',strtotime($v)), $string);
						}
					}
					$newContent .= $string."\n";
				}
				file_put_contents("/opt/bulk/$dir/pre.csv", $newContent);
			}
			file_put_contents($file, '10'); // преобработка
		}elseif($status == 10 && file_exists("/opt/bulk/$dir/the.conf")){ // формируем очередь запросов
			echo "=== Creating requests for $dir ===\n";
			$config = getConfig("/opt/bulk/$dir/the.conf");
			$queue = file_exists("/opt/bulk/$dir/queue.txt")?file_get_contents("/opt/bulk/$dir/queue.txt"):0;
			if(!is_dir("/opt/forReq/$queue")) {
				mkdir("/opt/forReq/$queue");
				file_put_contents("/opt/forReq/$queue/url.txt",$config['serviceurl']);
			}
			if (true /*sizeof(glob("/opt/forReq/$queue/*.qwe"))==0*/) {
				if (!file_exists("/opt/forReq/$queue/limit.txt")) file_put_contents("/opt/forReq/$queue/limit.txt",$config['limit']);

				$fields = json_decode(file_get_contents("/opt/bulk/$dir/fields.txt"), true);
				$list = explode("\n", file_get_contents("/opt/bulk/$dir/pre.csv"));
//				print_r($config);
//				exit;
				foreach($list as $key => $str){
					if(!preg_match("/\d{4}/", $str) && !preg_match("/@/", $str)){
						continue;
					}
					foreach($config['codes'] as $source){
						if(!is_dir('/opt/bulk/'.$dir.'/'.$source)) @mkdir('/opt/bulk/'.$dir.'/'.$source);
						if(file_exists('/opt/bulk/'.$dir.'/'.$source.'/'.($key+1).'/fResult.txt') && filesize('/opt/bulk/'.$dir.'/'.$source.'/'.($key+1).'/fResult.txt')){
//							echo "response exists\n";
							continue;
						}
						$info = array();

						$tmpArr = str_getcsv(trim($str));

						if(isset($fields['id'])){
							$info['id'] = isset($tmpArr[$fields['id']])?trim($tmpArr[$fields['id']]):"";
						}elseif(isset($fields['inn'])){
							$info['id'] = isset($tmpArr[$fields['inn']])?trim($tmpArr[$fields['inn']]):"";
						}elseif(isset($fields['orginn'])){
							$info['id'] = isset($tmpArr[$fields['orginn']])?trim($tmpArr[$fields['orginn']]):"";
						}elseif(isset($fields['passport'])){
							$info['id'] = isset($tmpArr[$fields['passport']])?trim($tmpArr[$fields['passport']]):"";
						}elseif(isset($fields['phone'])){
							$info['id'] = isset($tmpArr[$fields['phone']])?trim($tmpArr[$fields['phone']]):"";
						}elseif(isset($fields['email'])){
							$info['id'] = isset($tmpArr[$fields['email']])?trim($tmpArr[$fields['email']]):"";
						}elseif(isset($fields['vin'])){
							$info['id'] = isset($tmpArr[$fields['vin']])?trim($tmpArr[$fields['vin']]):"";
						}else{
							$info['id'] = $key+1;
						}

						if(isset($fields['fio']) && isset($tmpArr[$fields['fio']])){
							if(preg_match("/\([^\(\)]+\)/", $tmpArr[$fields['fio']])){
								$tmpArr[$fields['fio']] = preg_replace("/\([^\(\)]+\)/", ' ', $tmpArr[$fields['fio']]);
							}
							$fio = explode(' ', preg_replace("/\s+/", ' ', trim($tmpArr[$fields['fio']])));
							$info['last_name'] = $fio[0];
							$info['first_name'] = sizeof($fio) > 1 ? $fio[1] : '';
							$info['patronymic'] = sizeof($fio) > 2 ? $fio[2] : '';
							if(sizeof($fio)>3){
								$info['patronymic'] .= ' '.$fio[3];
							}
						}

						if(isset($fields['iof']) && isset($tmpArr[$fields['iof']])){
							if(preg_match("/\([^\(\)]+\)/", $tmpArr[$fields['iof']])){
								$tmpArr[$fields['iof']] = preg_replace("/\([^\(\)]+\)/", ' ', $tmpArr[$fields['iof']]);
							}
							$fio = explode(' ', preg_replace("/\s+/", ' ', trim($tmpArr[$fields['iof']])));
							$info['last_name'] = $fio[sizeof($fio)-1];
							$info['first_name'] = sizeof($fio) > 1 ?  $fio[0] : '';
							$info['patronymic'] = sizeof($fio) > 2 ?  $fio[1] : '';
							if(sizeof($fio)>3){
								$info['patronymic'] .= ' '.$fio[2];
							}
						}

						if(isset($fields['lastName']) && isset($tmpArr[$fields['lastName']])){
							$info['last_name'] = trim($tmpArr[$fields['lastName']]);
							if(isset($fields['firstName'])){
								$info['first_name'] = trim($tmpArr[$fields['firstName']]);
							}
							if(isset($fields['patronymic'])){
								$info['patronymic'] = trim($tmpArr[$fields['patronymic']]);
							}
						}

						if(isset($fields['bDate']) && isset($tmpArr[$fields['bDate']])){
							if(preg_match("/^\d{5}$/", trim($tmpArr[$fields['bDate']]))){
								require_once 'vendor/autoload.php';
								$info['date'] =  date('d.m.Y', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp(trim($tmpArr[$fields['bDate']])));
							}else{
								$info['date'] = trim($tmpArr[$fields['bDate']]);
							}
						}

						if(isset($fields['inn']) && isset($tmpArr[$fields['inn']])){
							$info['inn'] = preg_replace("/\D/", "", trim($tmpArr[$fields['inn']]));
							if(strlen($info['inn']) == 11){
								$info['inn'] = '0'.$info['inn'];
							}
						}

						if(isset($fields['orginn']) && isset($tmpArr[$fields['orginn']])){
							$info['orginn'] = preg_replace("/\D/", "", trim($tmpArr[$fields['orginn']]));
							if(strlen($info['orginn']) == 9){
								$info['orginn'] = '0'.$info['orginn'];
							}
						}

						if(isset($fields['phone']) && isset($tmpArr[$fields['phone']])){
							$info['mobile_phone'] = $tmpArr[$fields['phone']];
						}

						if(isset($fields['email']) && isset($tmpArr[$fields['email']])){
							$info['email'] = $tmpArr[$fields['email']];
						}

						if(isset($fields['vin']) && isset($tmpArr[$fields['vin']])){
							$info['vin'] = preg_replace("/_2$/","",$tmpArr[$fields['vin']]);
						}

						if(isset($fields['region_id']) && isset($tmpArr[$fields['region_id']])){
							$info['region_id'] = preg_replace("/\D+/", '', $tmpArr[$fields['region_id']]);
						}

						if(isset($fields['passport']) && isset($tmpArr[$fields['passport']])){
							$tmpPassport = preg_replace("/\D+/", '', $tmpArr[$fields['passport']]);
							while(strlen($tmpPassport) > 6 && strlen($tmpPassport) < 10 ){
								$tmpPassport = '0' . $tmpPassport;
							}
							$info['serial'] = substr($tmpPassport,0,4);
							$info['number'] = substr($tmpPassport,4,6);
						}

						if(isset($fields['serial']) && isset($tmpArr[$fields['serial']])){
							$info['serial'] = preg_replace("/\D+/", '', $tmpArr[$fields['serial']]);
							while(strlen($info['serial']) < 4 ){
								$info['serial'] = '0'.$info['serial'];
							}
						}

						if(isset($fields['number']) && isset($tmpArr[$fields['number']])){
							$info['number'] = preg_replace("/\D+/", '', $tmpArr[$fields['number']]);
							while(strlen($info['number']) < 6 ){
								$info['number'] = '0'.$info['number']."\n";
							}
						}

						if($source == 'bankrot' ){
							if(isset($fields['inn']) && isset($info['inn']) && strlen($info['inn']) == 12){
								unset($info['patronymic']);
								unset($info['first_name']);
								unset($info['last_name']);
								unset($info['date']);
							}
						}

						$wDir = '/opt/bulk/'.$dir.'/'.$source.'/'.($key+1); //$info['id'];
        	                                if(!is_dir($wDir)) @mkdir($wDir);
/*
                	                        if(!file_exists($wDir.'/fResult.txt') || !filesize($wDir.'/fResult.txt')){
// Проверка на одновременную обработку
	                                            $file = fopen($wDir.'/lock.txt', "w"); 
        	                                    if ($file) {
                	                                if (flock($file,LOCK_EX|LOCK_NB)) {
*/
                        	                            _process($info, $config, $source, $config['checktypes'][$source], $key, $dir, $queue);
/*
	                                                    fclose($file);
        	                                            unlink($wDir.'/lock.txt');
                	                                } else {
                        	                            fclose($file);
	                                                }
        	                                    }
                	                        }
*/
					}
				}
//				exit;
				file_put_contents("/opt/forReq/$queue/reload.txt","");
			}
			file_put_contents($file, '20'); // ждать готовности ответов
			$mysqli = mysqli_connect ($database['server'],$database['login'],$database['password'], $database['name']) or die(mysqli_errno($mysqli) . ": " . mysqli_error($mysqli));
			if ($mysqli) {
				mysqli_query($mysqli, "Set character set utf8");
				mysqli_query($mysqli, "Set names 'utf8'");
			}else{
				echo "!!! mysql error !!!";
			}

			$total = sizeof($list) - 1;
			$mysqli->query("UPDATE isphere.Bulk SET status=0, total_rows='".$total."' WHERE id='".$dir."'");
			$mysqli->close();
		}elseif($status == 20 && file_exists("/opt/bulk/$dir/the.conf")){  // проверяем готовность ответов
			$config = getConfig("/opt/bulk/$dir/the.conf");
			$queue = file_exists("/opt/bulk/$dir/queue.txt")?file_get_contents("/opt/bulk/$dir/queue.txt"):0;
			if (!file_exists("/opt/forReq/$queue/limit.txt")) file_put_contents("/opt/forReq/$queue/limit.txt",$config['limit']);

			$reqFiles = glob("/opt/forReq/*/$dir-row*.*");
			if(count($reqFiles) < 1){ // очередь запросов пустая, проверяем ответы на предмет ошибок
				echo "=== Checking results for $dir ===\n";
				$final = true;
				$errors = array();
				foreach($config['codes'] as $source){
					$respFiles = glob("/opt/bulk/$dir/$source/*/fResult.txt");
					//print_r($files);
					foreach($respFiles as $respFile){
						$rContent = file_get_contents($respFile);
						if($rContent == '' || preg_match("/<Error>/si", $rContent) || preg_match("/<message>/si", $rContent) || preg_match("/<center>nginx/si", $rContent)){
							(!isset($errors[$source]))?$errors[$source]=1:$errors[$source]++;
                                                        $inc = 0;
							$incFile = strtr($respFile,array('.txt'=>'.inc'));
                                                        $inc = file_exists($incFile)?file_get_contents($incFile):0;
							if($inc<10 && preg_match("/(<center>nginx|Некорректный ответ|Превышено время|Превышено количество|Нет актуальных сессий|Сервис временно недоступен|Сервис не отвечает|Ошибка при выполнении запроса|Не удалось выполнить поиск|Сервис выключен|Внутренняя ошибка|Произошла внутренняя ошибка|Ошибка получения|Технические работы|Ошибка при обработке запроса|Слишком много запросов|Сервис перегружен запросами)/", $rContent)){
								// надо будет вместо текста ошибки проверять коды
								copy($respFile,strtr($respFile,array('.txt'=>'.err')));
								unlink($respFile);
								file_put_contents($incFile,++$inc);
								$final = false;
							}
						}
					}
				}
				if($final){
					if(is_dir("/opt/forReq/$queue")) {
						if (file_exists("/opt/forReq/$queue/limit.txt")) unlink("/opt/forReq/$queue/limit.txt");
						if (sizeof(glob("/opt/forReq/$queue/*.qwe"))==0 && sizeof(glob("/opt/forReq/$queue/*.pause"))==0) { // удаляем пустую очередь
							if (file_exists("/opt/forReq/$queue/url.txt")) unlink("/opt/forReq/$queue/url.txt");
							if (file_exists("/opt/forReq/$queue/reload.txt")) unlink("/opt/forReq/$queue/reload.txt");
							@rmdir("/opt/forReq/$queue");
						}
					}
					file_put_contents($file, '30');  // сделать результаты источников
					if(sizeof($errors)) {
						$comment = '';
						foreach ($errors as $source => $count)
							$comment .= ($comment?', ':'').$source.' '.$count;
						$comment = 'Не удалось обработать часть запросов: '.$comment;
                                        } else {
						$comment = 'Все запросы обработаны без ошибок';
					}
					file_put_contents("/opt/bulk/$dir/comment.txt", $comment);
				}else{
					file_put_contents($file, '10'); // повторить генерацию запросов
				}
			}else{
//				echo "=== Waiting results for $dir ===\n";
			}
		}elseif($status == 30 && file_exists("/opt/bulk/$dir/the.conf")){ // выгружаем csv
			echo "=== Creating csv files for $dir ===\n";
			$config = getConfig("/opt/bulk/$dir/the.conf");
			$fields = json_decode(file_get_contents("/opt/bulk/$dir/fields.txt"), true);
//			file_put_contents('/opt/bulk/'.$dir.'/list.csv', trim(file_get_contents("/opt/bulk/$dir/pre.csv")));
			$orgData = explode("\n", trim(file_get_contents("/opt/bulk/$dir/pre.csv")));

			foreach($config['codes'] as $source){
				if (file_exists('/opt/bulk/'.$dir.'/'.$source)) doResult('/opt/bulk/'.$dir.'/'.$source, $fields, $orgData);
			}

			// FNS
			if(in_array('fns_inn', $config['sources']) && (file_exists($csv = '/opt/bulk/'.$dir.'/fns/fns_inn.csv') || file_exists($csv = '/opt/bulk/'.$dir.'/fns_inn/fns_inn.csv'))){
				$data = explode("\n", file_get_contents($csv));
				$position = array_search('"INN"', explode(';', $data[0]));

				$tmpData = array();
				foreach($data as $key => $val){
					$tmp = str_getcsv($val, ';');
					$tmpData[$tmp[0]] = ',"'.($position!==false && isset($tmp[$position])?$tmp[$position]:'').'"';
				}
//				print_r($tmpData);

				foreach($orgData as $k => $v){
					if(isset($tmpData[$k+1])){
						$orgData[$k] = $v.$tmpData[$k+1].'';
					}else{
						if($k == 0){
							$orgData[$k]  = $v.',"INN"';
						}else{
							$orgData[$k]  = $v.',""';
						}
					}
				}
			}
			if(in_array('fns_invalid', $config['sources']) && (file_exists($csv = '/opt/bulk/'.$dir.'/fns/fns_invalid.csv') || file_exists($csv = '/opt/bulk/'.$dir.'/fns_invalid/fns_invalid.csv'))){
				$data = explode("\n", file_get_contents($csv));
				$position = array_search('"ResultCode"', explode(';', $data[0]));

				$tmpData = array();
				foreach($data as $key => $val){
					$tmp = str_getcsv($val, ';');
					$tmpData[$tmp[0]] = ',"'.($position!==false && isset($tmp[$position])?$tmp[$position]:'').'"';
				}
//				print_r($tmpData);

				foreach($orgData as $k => $v){
					if(isset($tmpData[$k+1])){
						$orgData[$k] = $v.$tmpData[$k+1].'';
					}else{
						if($k == 0){
							$orgData[$k]  = $v.',"Статус ИНН"';
						}else{
							$orgData[$k]  = $v.',""';
						}
					}
				}
			}
			if(in_array('fns_npd', $config['sources']) && (file_exists($csv = '/opt/bulk/'.$dir.'/fns/fns_npd.csv') || file_exists($csv = '/opt/bulk/'.$dir.'/fns_npd/fns_npd.csv'))){
				$data = explode("\n", file_get_contents($csv));
				$position = array_search('"ResultCode"', explode(';', $data[0]));

				$tmpData = array();
				foreach($data as $key => $val){
					$tmp = str_getcsv($val, ';');
					$tmpData[$tmp[0]] = ',"'.($position!==false && isset($tmp[$position])?$tmp[$position]:'').'"';
				}
//				print_r($tmpData);

				foreach($orgData as $k => $v){
					if(isset($tmpData[$k+1])){
						$orgData[$k] = $v.$tmpData[$k+1].'';
					}else{
						if($k == 0){
							$orgData[$k]  = $v.',"Статус НПД"';
						}else{
							$orgData[$k]  = $v.',""';
						}
					}
				}
			}

			// BANKROT
			if(in_array('bankrot', $config['codes']) && (file_exists($csv = '/opt/bulk/'.$dir.'/bankrot/bankrot_person.csv') || file_exists($csv = '/opt/bulk/'.$dir.'/bankrot/bankrot_org.csv'))){
				$data = explode("\n", file_get_contents($csv));
				$position = array_search('"publicationdate"', explode(';', $data[0]));

				$tmpData = array();
				foreach($data as $key => $val){
					$tmp = str_getcsv($val, ';');
					if(isset($tmp[$position]) && strlen($tmp[$position]) > 5){
						$tmpData[$tmp[0]] = $tmp[$position];
					}
				}
//				print_r($tmpData);

				foreach($orgData as $k => $v){
					if(isset($tmpData[$k+1])){
						$orgData[$k] = $v.',"1"';
					}else{
						if($k == 0){
							$orgData[$k]  = $v.',"Банкрот"';
						}else{
							$orgData[$k]  = $v.',"0"';
						}
					}
				}
			}

			//FSSP
			if(in_array('fssp', $config['codes']) && (file_exists($csv = '/opt/bulk/'.$dir.'/fssp/fssp_person.csv') || file_exists($csv = '/opt/bulk/'.$dir.'/fssp/fssp_inn.csv'))){
				$data = explode("\n", file_get_contents($csv));
				// "CloseDate", "Total", "BailiffTotal", "CostsTotal", "FineTotal"

				$workTmp = array();
				$posArr = explode(';', $data[0]);
				$ps = array();
				if(array_search('"row"', $posArr) !== false){
					$ps['rPos'] = array_search('"row"', $posArr);
				}
				if(array_search('"CaseDate"', $posArr) !== false){
					$ps['cdPos'] = array_search('"CaseDate"', $posArr);
				}
				if(array_search('"CloseDate"', $posArr) !== false ){
					$ps['clPos'] = array_search('"CloseDate"', $posArr);
				}
				if(array_search('"Total"', $posArr) !== false ){
					$ps['tPos'] = array_search('"Total"', $posArr);
				}
				if(array_search('"BailiffTotal"', $posArr) !== false ){
					$ps['btPos'] = array_search('"BailiffTotal"', $posArr);
				}
				if(array_search('"CostsTotal"', $posArr) !== false ){
					$ps['ctPos'] = array_search('"CostsTotal"', $posArr);
				}
				if(array_search('"FineTotal"', $posArr) !== false ){
					$ps['ftPos'] = array_search('"FineTotal"', $posArr);
				}
//				print_r($ps);
//				exit;

				foreach($data as $key => $val){
					$tmp = str_getcsv($val, ';');
					if(count($tmp) < 3){
						continue;
                                        }
					if(isset($ps['rPos']) && $tmp[$ps['rPos']] == 'row'){
						$workTmp[$key+1] = array('Кол-во ИП', 'Сумма ИП', 'Завершено ИП');
					}elseif(isset($ps['rPos']) && isset($ps['cdPos'])){
//						foreach($ps as  $somenumber){
//							echo $tmp[$somenumber]." => ".$somenumber."\n";
//						}

						if(!isset($workTmp[$tmp[$ps['rPos']]])){

							$sum = 0;

							if(isset($ps['tPos']) && isset($tmp[$ps['tPos']]) && floatval(trim($tmp[$ps['tPos']])) > 0){
								$sum += floatval(trim($tmp[$ps['tPos']]));
							}

							if(isset($ps['btPos']) && isset($tmp[$ps['btPos']]) && floatval(trim($tmp[$ps['btPos']])) > 0){
								$sum += floatval(trim($tmp[$ps['btPos']]));
							}

							if(isset($ps['ctPos']) && isset($tmp[$ps['ctPos']]) && floatval(trim($tmp[$ps['ctPos']])) > 0){
								$sum += floatval(trim($tmp[$ps['ctPos']]));
							}

							if(isset($ps['ftPos']) && isset($tmp[$ps['ftPos']]) && floatval(trim($tmp[$ps['ftPos']])) > 0){
								$sum += floatval(trim($tmp[$ps['ftPos']]));
							}

							$workTmp[$tmp[$ps['rPos']]] = array($tmp[$ps['cdPos']] ? 1 : 0, $sum, ( isset($ps['clPos']) && isset($tmp[$ps['clPos']]) && preg_match("/\d{2}\.\d{2}\.\d{4}/", $tmp[$ps['clPos']]) ? 1 : 0 ) );

						}else{
							$quan = $workTmp[$tmp[$ps['rPos']]][0];
							$closed = $workTmp[$tmp[$ps['rPos']]][2];

							if($tmp[$ps['cdPos']]){
								$quan++;
							}

							if(isset($ps['clPos']) && isset($tmp[$ps['clPos']]) && preg_match("/\d{2}\.\d{2}\.\d{4}/", $tmp[$ps['clPos']])){
								$closed++;
							}

							$sum = floatval(trim($workTmp[$tmp[$ps['rPos']]][1]));

							if(isset($ps['tPos']) && isset($tmp[$ps['tPos']]) && floatval(trim($tmp[$ps['tPos']])) > 0){
								$sum += floatval(trim($tmp[$ps['tPos']]));
							}

							if(isset($ps['btPos']) && isset($tmp[$ps['btPos']]) && floatval(trim($tmp[$ps['btPos']])) > 0){
								$sum += floatval(trim($tmp[$ps['btPos']]));
							}

							if(isset($ps['ctPos']) && isset($tmp[$ps['ctPos']]) && floatval(trim($tmp[$ps['ctPos']])) > 0){
								$sum += floatval(trim($tmp[$ps['ctPos']]));
							}

							if(isset($ps['ftPos']) && isset($tmp[$ps['ftPos']]) && floatval(trim($tmp[$ps['ftPos']])) > 0){
								$sum += floatval(trim($tmp[$ps['ftPos']]));
							}

							$workTmp[$tmp[$ps['rPos']]] = array($quan, $sum, $closed);
						}
						//print_r($workTmp);
					}
				}

				foreach($workTmp as $key => $val){
					if(preg_match("/\./", $val[1])){
						$workTmp[$key][1] = preg_replace("/\./", ',', $val[1]);
					}
				}

				foreach($orgData as $k => $v){
					$tmp = str_getcsv($v);
					if(isset($workTmp[$k+1])){
						$orgData[$k] = $v.',"'.implode('","', $workTmp[$k+1]).'"';
					}else{
						$orgData[$k] = $v.',"","",""';
					}
				}
			}

			// NOTARIAT
			if(in_array('notariat', $config['codes']) && file_exists($csv = '/opt/bulk/'.$dir.'/notariat/notariat_person.csv')){
				$data = explode("\n", file_get_contents($csv));
				$position = array_search('"CaseDate"', explode(';', $data[0]));

				$tmpData = array();
				foreach($data as $key => $val){
					$tmp = str_getcsv($val, ';');
					if(isset($tmp[$position]) && strlen($tmp[$position]) > 5){
						$tmpData[$tmp[0]] = $tmp[$position];
					}
				}
//				print_r($tmpData);

				foreach($orgData as $k => $v){
					if(isset($tmpData[$k+1])){
						$orgData[$k] = $v.',"1"';
					}else{
						if($k == 0){
							$orgData[$k]  = $v.',"Наследодатель"';
						}else{
							$orgData[$k]  = $v.',"0"';
						}
					}
				}
			}

			// KAD
			if(in_array('kad', $config['codes']) && (file_exists($csv = '/opt/bulk/'.$dir.'/kad/kad_person.csv') || file_exists($csv = '/opt/bulk/'.$dir.'/kad/kad_org.csv'))){
				$data = explode("\n", file_get_contents($csv));
//				$position = array_search('"caseType"', $posArr);
				$posArr = explode(';', $data[0]);
				if(array_search('"caseType"', $posArr) !== false){
					$ps['ctPos'] = array_search('"caseType"', $posArr);
				}
				if(array_search('"isRespondent"', $posArr) !== false){
					$ps['irPos'] = array_search('"isRespondent"', $posArr);
				}

				$tmpData = array();
				foreach($data as $key => $val){
					$tmp = str_getcsv($val, ';');
//					if(isset($tmp[$position]) && $tmp[$position]=='bankruptcy'){
					if(isset($ps['ctPos']) && isset($tmp[$ps['ctPos']]) && $tmp[$ps['ctPos']]=='bankruptcy' && isset($ps['irPos']) && isset($tmp[$ps['irPos']]) && $tmp[$ps['irPos']]=='true'){
						$tmpData[$tmp[0]] = isset($tmpData[$tmp[0]])?$tmpData[$tmp[0]]+1:1;
					}
				}
//				print_r($tmpData);

				foreach($orgData as $k => $v){
					if(isset($tmpData[$k+1])){
						$orgData[$k] = $v.',"'.$tmpData[$k+1].'"';
					}else{
						if($k == 0){
							$orgData[$k]  = $v.',"Дел о банкротстве"';
						}else{
							$orgData[$k]  = $v.',"0"';
						}
					}
				}
			}
/*
			// WHATSAPP
			if(in_array('whatsapp_phone', $config['sources']) && file_exists($csv = '/opt/bulk/'.$dir.'/whatsapp/whatsapp_phone.csv')){
				$data = explode("\n", file_get_contents($csv));

				$tmpData = array();
				foreach($data as $key => $val){
					$tmp = str_getcsv($val, ';');
					$tmpData[$tmp[0]] = 1;
				}
//				print_r($tmpData);

				foreach($orgData as $k => $v){
					if(isset($tmpData[$k+1])){
						$orgData[$k] = $v.',"1"';
					}else{
						if($k == 0){
							$orgData[$k]  = $v.',"Whatsapp"';
						}else{
							$orgData[$k]  = $v.',"0"';
						}
					}
				}
			}
*/
			file_put_contents('/opt/bulk/'.$dir.'/list.csv', implode("\n", $orgData));
			file_put_contents($file, '40');
		}elseif($status == 40 && file_exists("/opt/bulk/$dir/the.conf")){ // создаем xls
			echo "=== Creating xls for $dir ===\n";
			$config = getConfig("/opt/bulk/$dir/the.conf");
                        $xls = '/opt/bulk/'.$dir.'/'.$dir.'.xlsx';
			if (file_exists($xls)) unlink($xls);

			echo "Loading list.csv\n";
			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( '/opt/bulk/'.$dir.'/list.csv' );
			$worksheet = $spreadsheet->getActiveSheet();
			$worksheet->setTitle('list');

			$spreadsheet->getActiveSheet()->setAutoFilter(
			$spreadsheet->getActiveSheet()->calculateWorksheetDimension());

			foreach ($worksheet->getColumnIterator() as $column) {
				$worksheet->getStyle($column->getColumnIndex())->getNumberFormat()->setFormatCode('#');
				$worksheet->getStyle($column->getColumnIndex())->getAlignment()->setHorizontal('left');
				$worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
			}

			/////////////////////////////////////////////////////////////////////////////////

			foreach($config['codes'] as $source) {
				$csvs = glob('/opt/bulk/'.$dir.'/'.$source.'/*.csv');
				foreach ($csvs as $csv) {
					preg_match("/\/([a-z0-9_]+)\.csv$/", $csv, $matches);
					$code = $matches[1];

					echo "Loading $code.csv\n";
					$sourceSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($csv);
					$sourceWorksheet = $sourceSpreadsheet->getActiveSheet();
					$sourceWorksheet->setTitle($code);

					$sourceWorksheet->setAutoFilter(
					$sourceWorksheet->calculateWorksheetDimension());

					foreach ($sourceWorksheet->getColumnIterator() as $column) {
						$sourceWorksheet->getStyle($column->getColumnIndex())->getNumberFormat()->setFormatCode('#');
						$sourceWorksheet->getStyle($column->getColumnIndex())->getAlignment()->setHorizontal('left');
						$sourceWorksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
					}

					$spreadsheet->addSheet($sourceWorksheet);
				}
			}

			///////////////////////////////////////////////////////////////////////////////////////

			echo "Saving results\n";
			$writer = new Xlsx($spreadsheet);
			$writer->setPreCalculateFormulas(false);
			$writer->save($xls);
			shell_exec("zip -j /opt/bulk/".$dir."/result.zip /opt/bulk/".$dir."/".$dir.".xlsx");

			$mysqli = mysqli_connect ($database['server'],$database['login'],$database['password'], $database['name']) or die(mysqli_errno($mysqli) . ": " . mysqli_error($mysqli));
			if ($mysqli) {
				mysqli_query($mysqli, "Set character set utf8");
				mysqli_query($mysqli, "Set names 'utf8'");
			}else{
				echo "!!! mysql error !!!";
			}

			$pr = count(explode("\n", file_get_contents("/opt/bulk/$dir/pre.csv"))) - 1;

                        $comment = file_exists("/opt/bulk/$dir/comment.txt")?file_get_contents("/opt/bulk/$dir/comment.txt"):'';
			$mysqli->query("UPDATE isphere.Bulk SET ".($config['id']=='___avtoexpress'?"":"status=1,")."processed_at=NOW(),processed_rows='".$pr."',results_note='$comment' WHERE id='".$dir."'");
			$mysqli->close();

			$msg = 'Обработан реестр '.$dir.' от '.$config['id'];
			telegramMsg($msg);

			unlink($file);
		}elseif($status == 90 && file_exists("/opt/bulk/$dir/the.conf")){ // на паузе
//			echo "=== Pause for $dir ===\n";
		}else{
			echo "=== Unknown state or bad files for $dir ===\n";
		}
//                break;
	}
//	echo "cycle end\n";
	sleep(5);
//	exit;
//	cycle($database);
}

cycle($database);

?>