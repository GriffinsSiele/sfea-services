<?php

function parse_cookies($header) {
	$cookies = array();
	foreach ($header as $line) {
		$cookie_str = 'Set-Cookie:';
		$pos = strpos($line,$cookie_str);
		if ($pos===false) {
			$cookie_str = 'set-cookie:';
			$pos = strpos($line,$cookie_str);
                }
		if ($pos!==false) {
			$line = substr($line,$pos+strlen($cookie_str));
			$pos = strpos($line,';');
			if ($pos) $line = trim(substr($line,0,$pos));
			$pos = strpos($line,'=');
			if ($pos) $cookies[substr($line,0,$pos)] = substr($line,$pos+1);
		}
	}
	return $cookies;
}

function cookies_header($cookies) {
	if (sizeof($cookies)) {
		$names = array_keys($cookies);
		$cookie = array();
		foreach ($cookies as $key => $val) {
			if ($val!=='DELETED') $cookie[] = $key.'='.$val;
		}
		if (count($cookie)) {
			return "Cookie: " . trim(implode('; ',$cookie)) . ";\r\n";
		}
	}
	return '';
}

function cookies_str($cookies) {
	$cookie = array();
	foreach ($cookies as $key => $val) {
		if ($val!=='DELETED') $cookie[] = $key.'='.$val;
	}
	return trim(implode('; ',$cookie));
}

function str_cookies($str) {
	$cookies = array();
	$cookie = explode('; ',$str);
	foreach ($cookie as $line) {
		$pos = strpos($line,'=');
		if ($pos) $cookies[substr($line,0,$pos)] = substr($line,$pos+1);
	}
	return $cookies;
}

?>