<?php
/*
   _____ _                     _____  _    _ _____  
  / ____| |                   |  __ \| |  | |  __ \ 
 | (___ | | ___   _ _ __   ___| |__) | |__| | |__) |
  \___ \| |/ / | | | '_ \ / _ \  ___/|  __  |  ___/ 
  ____) |   <| |_| | |_) |  __/ |    | |  | | |     
 |_____/|_|\_\\__, | .__/ \___|_|    |_|  |_|_|     
               __/ | |                              
              |___/|_|      
			  
Version: 1.2.1 by Kibioctet (admin@n-mail.fr)
GitHub : https://github.com/Kibioctet/SkypePHP
*/

class Skype {
	public $username;
	private $password, $registrationToken, $skypeToken, $expiry = 0, $logged = false, $hashedUsername, $proxy, $proxy_auth, $header_out, $post_out, $last_url;
	
	public function __construct($username, $password, $folder = "skypephp", $proxy = false, $proxy_auth = false) {
		$this->username = $username;
		$this->password = $password;
		$this->folder = $folder;
		$this->proxy = $proxy;
		$this->proxy_auth = $proxy_auth;
		$this->hashedUsername = sha1($username);
		
		if (file_exists($this->folder)) {
			if (file_exists("{$this->folder}/auth_{$this->hashedUsername}")) {
				$auth = json_decode(file_get_contents("{$this->folder}/auth_{$this->hashedUsername}"), true);
				if (time() >= $auth["expiry"])
					unset($auth);
			}
		} else {
			if (!mkdir("{$this->folder}"))
				exit(trigger_error("Skype : Unable to create the SkypePHP directoy.", E_USER_WARNING));
		}
		
		if (isset($auth)) {
			$this->skypeToken = $auth["skypeToken"];
			$this->registrationToken = $auth["registrationToken"];
			$this->expiry = $auth["expiry"];
		} else {
			$this->login();
		}
	}
	
	private function login() {
                $cookiesAll = array();

                $startURL = "https://login.skype.com/login/oauth/microsoft?client_id=572381&redirect_uri=https%3A%2F%2Fweb.skype.com%2F&username={$this->username}";
		$form = $this->web($startURL, "GET", [], true, true);
		file_put_contents("{$this->folder}/start_{$this->hashedUsername}.html", $startURL."\n\n".$this->header_out."\n\n".$form);

		preg_match_all('`Set-Cookie: (.+)=(.+);`isU', $form, $cookiesArray);
		for ($i = 0; $i <= count($cookiesArray[1])-1; $i++) $cookiesAll[$cookiesArray[1][$i]]=$cookiesArray[2][$i];
                $cookiesAll['wlidperf'] = 'FR=L&ST='.time().'000';
		
		preg_match("`urlPost:'(.+)',`isU", $form, $loginURL);
		$loginURL = $loginURL[1];
		
		if (preg_match("`name=\"PPFT\" id=\"(.+)\" value=\"(.+)\"`isU", $form, $ppft))
			$ppft = $ppft[2];
                else {
			trigger_error("Skype : Connect failed", E_USER_WARNING);
			return false;
		}

		if (preg_match("`,cZ:\'(.+)\',b`sU", $form, $ppsx))
			$ppsx = $ppsx[1];
		elseif (preg_match("`,cW:\'(.+)\',A`sU", $form, $ppsx))
			$ppsx = $ppsx[1];
		else
			$ppsx = 'Passport';
		
		$post = [
			"i13" => (int)0,
			"login" => $this->username,
			"loginfmt" => $this->username,
			"type" => 11,
			"LoginOptions" => 3,
			"lrt" => "",
			"lrtPartition" => "",
			"hisRegion" => "",
			"hisScaleUnit" => "",
			"passwd" => $this->password,
			"ps" => (int)2,
			"psRNGCDefaultType" => "",
			"psRNGCEntropy" => "",
			"psRNGCSLK" => "",
			"canary" => "",
			"ctx" => "",
			"hpgrequestid" => "",
			"PPFT" => $ppft,
			"PPSX" => $ppsx,
			"NewUser" => (int)1,
			"FoundMSAs" => "",
			"fspost" => (int)0,
			"i21" => (int)0,
			"CookieDisclosure" => (int)0,
			"IsFidoSupported" => (int)1,
			"isSignupPost" => (int)0,
			"i2" => (int)1,
			"i17" => (int)0,
			"i18" => "",
			"i19" => 10866,
		];
		
		$cookies = "";
		foreach ($cookiesAll as $key => $val) $cookies .= "{$key}={$val}; ";

		$form = $this->web($loginURL, "POST", $post, true, true, $cookies, ['Referer: '.$this->last_url]);
		file_put_contents("{$this->folder}/login_{$this->hashedUsername}.html", $loginURL."\n\n".$this->header_out."\n\n".$this->post_out."\n\n".$form);

		preg_match_all('`Set-Cookie: (.+)=(.+);`isU', $form, $cookiesArray);
		for ($i = 0; $i <= count($cookiesArray[1])-1; $i++) $cookiesAll[$cookiesArray[1][$i]]=$cookiesArray[2][$i];

		$counter = 0;
		$host = '';
		
		while ($counter++<3 && (preg_match("`<form name=\".+\" id=\".+\" action=\"(.+)\" method=\"POST\"`isU", $form, $newURL) || preg_match("`<form id=\".+\" method=\"POST\"`isU", $form))) {
                        if (isset($newURL[1])) {
				$postURL = $newURL[1];
				$newURL = false;
			}
			if (substr($postURL,0,4)=='http') {
				$parse = parse_url($postURL);
				$host = $parse['scheme'].'://'.$parse['host'];
			} else {
				$postURL = $host.$postURL;
			}

			if (!strpos($postURL,"/login/oauth/proxy") &&
                           (preg_match_all("`<input type=\"hidden\" name=\"(.+)\" id=\".+\" value=\"(.+)\"`isU", $form, $params) || 
			    preg_match_all("`<input type=\"hidden\" id=\".+\" name=\"(.+)\" value=\"(.+)\"`isU", $form, $params))) {
				$post = array();
				for ($i = 0; $i <= count($params[1])-1; $i++)
					$post[$params[1][$i]] = $params[2][$i];

				$cookies = "";
				foreach ($cookiesAll as $key => $val) $cookies .= "{$key}={$val}; ";

//				echo "Post $postURL\n";
//				foreach ($post as $key => $val) echo "Parameter {$key}={$val}\n";
				$form = $this->web($postURL, "POST", $post, true, true, $cookies, ['Referer: '.$this->last_url]);
				file_put_contents("{$this->folder}/form{$counter}_{$this->hashedUsername}.html", $postURL."\n\n".$this->header_out."\n\n".$this->post_out."\n\n".$form);


				preg_match_all('`Set-Cookie: (.+)=(.+);`isU', $form, $cookiesArray);
				for ($i = 0; $i <= count($cookiesArray[1])-1; $i++) $cookiesAll[$cookiesArray[1][$i]]=$cookiesArray[2][$i];
			}
		}

		if (preg_match("`urlPost:'(.+)',`isU", $form, $loginURL)) {
			$loginURL = $loginURL[1];

			$post = [
				"type" => 28,
				"PPFT" => $ppft,
				"LoginOptions" => 3,
				"DontShowAgain" => 'true',
				"i2" => (int)1,
				"i17" => (int)0,
				"i18" => "",
				"i19" => 556374,
			];

			$cookies = "";
			foreach ($cookiesAll as $key => $val) $cookies .= "{$key}={$val}; ";

//			echo "Confirm login $loginURL\n";
//			foreach ($post as $key => $val) echo "Parameter {$key}={$val}\n";
			$form = $this->web($loginURL, "POST", $post, true, true, $cookies, ['Referer: '.$this->last_url]);
			file_put_contents("{$this->folder}/confirm_{$this->hashedUsername}.html", $loginURL."\n\n".$this->header_out."\n\n".$this->post_out."\n\n".$form);

			preg_match_all('`Set-Cookie: (.+)=(.+);`isU', $form, $cookiesArray);
			for ($i = 0; $i <= count($cookiesArray[1])-1; $i++) $cookiesAll[$cookiesArray[1][$i]]=$cookiesArray[2][$i];
		}

		preg_match("`<input type=\"hidden\" name=\"NAP\" id=\"NAP\" value=\"(.+)\">`isU", $form, $NAP);
		preg_match("`<input type=\"hidden\" name=\"ANON\" id=\"ANON\" value=\"(.+)\">`isU", $form, $ANON);
		preg_match("`<input type=\"hidden\" name=\"t\" id=\"t\" value=\"(.+)\">`isU", $form, $t);

		if (!isset($NAP[1]) || !isset($ANON[1]) || !isset($t[1])) {
			trigger_error("Skype : Authentication failed for {$this->username}", E_USER_WARNING);
			return false;
		}
		
		$NAP = $NAP[1];
		$ANON = $ANON[1];
		$t = $t[1];
		
		$post = [
			"NAP" => $NAP,
			"ANON" => $ANON,
			"t" => $t
		];
		
		$cookies = "";
		foreach ($cookiesAll as $key => $val) $cookies .= "{$key}={$val}; ";

		$form = $this->web("https://lw.skype.com/login/oauth/proxy?client_id=578134&redirect_uri=https://web.skype.com/&site_name=lw.skype.com&wa=wsignin1.0", "POST", $post, true, true, $cookies, ['Referer: '.$this->last_url]);
		
		preg_match("`<input type=\"hidden\" name=\"t\" value=\"(.+)\"/>`isU", $form, $t);
		$t = $t[1];
		
		$post = [
			"t" => $t,
			"site_name" => "lw.skype.com",
			"oauthPartner" => 999,
			"form" => "",
			"client_id" => 578134,
			"redirect_uri" => "https://web.skype.com/"
		];
		
		
		$login = $this->web("https://login.skype.com/login/microsoft?client_id=578134&redirect_uri=https://web.skype.com/", "POST", $post);

		preg_match("`<input type=\"hidden\" name=\"skypetoken\" value=\"(.+)\"/>`isU", $login, $skypeToken);
		$this->skypeToken = $skypeToken[1];
		
		$login = $this->web("https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints", "POST", "{}", true);
		
		if (preg_match("`registrationToken=(.+);`isU", $login, $registrationToken))
			$this->registrationToken = $registrationToken[1];
		
		$expiry = time()+21600;
		
		$cache = [
			"skypeToken" => $this->skypeToken,
			"registrationToken" => $this->registrationToken,
			"expiry" => $expiry
		];
		
		$this->expiry = $expiry;
		$this->logged = true;
		
		file_put_contents("{$this->folder}/auth_{$this->hashedUsername}", json_encode($cache));
		
		return true;
	}
	
	private function web($url, $mode = "GET", $post = [], $showHeaders = false, $follow = true, $customCookies = "", $customHeaders = []) {
		if (!function_exists("curl_init"))
			exit(trigger_error("Skype : cURL is required", E_USER_WARNING));
		
		if (!empty($post) && is_array($post))
			$post = http_build_query($post);
		
		if ($this->logged && time() >= $this->expiry) {
			$this->logged = false;
			$this->login();
		}
		
		$headers = $customHeaders;
                $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
                $headers[] = 'Accept-Language: ru-RU,ru;q=0.9';
                $headers[] = 'Cache-Control: max-age=0';
                $headers[] = 'Connection: keep-alive';

                $headers[] = 'sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="96", "Google Chrome";v="96"';
                $headers[] = 'sec-ch-ua-mobile: ?0';
                $headers[] = 'sec-ch-ua-platform: "Windows"';
                $headers[] = 'Sec-Fetch-Dest: document';
                $headers[] = 'Sec-Fetch-Mode: navigate';
                $headers[] = 'Sec-Fetch-Site: same-origin';
                $headers[] = 'Sec-Fetch-User: ?1';
                $headers[] = 'Upgrade-Insecure-Requests: 1';

		$p = parse_url($url);
                $headers[] = 'Origin: '.$p['scheme'].'://'.$p['host'];

		if (isset($this->skypeToken)) {
			$headers[] = "X-Skypetoken: {$this->skypeToken}";
			$headers[] = "Authentication: skypetoken={$this->skypeToken}";
		}
		
		if (isset($this->registrationToken))
			$headers[] = "RegistrationToken: registrationToken={$this->registrationToken}";
		
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $url);
		if (!empty($headers))
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $mode);
                $this->post_out = '';
		if (!empty($post)) {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
	                $this->post_out = $post;
		}
		if ($customCookies)
			curl_setopt($curl, CURLOPT_COOKIE, preg_replace("/;\s$/","",$customCookies));
//		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
//		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36");
		curl_setopt($curl, CURLOPT_ENCODING, "");
		curl_setopt($curl, CURLOPT_HEADER, $showHeaders);
		curl_setopt($curl, CURLINFO_HEADER_OUT, $showHeaders);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $follow);
		curl_setopt($curl, CURLOPT_AUTOREFERER, $follow);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
/*
		if ($this->proxy) {
			curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
			if ($this->proxy_auth)
				curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->proxy_auth); 
		}
*/
		$result = curl_exec($curl);
		
                $this->header_out = curl_getinfo($curl, CURLINFO_HEADER_OUT);
                $this->last_url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

		curl_close($curl);
		
                sleep(5);
		return $result;
	}
	
	public function logout() {
		if (!$this->logged)
			return true;
		
		unlink("{$this->folder}/auth_{$this->username}");
		unset($this->skypeToken);
		unset($this->registrationToken);
		
		return true;
	}
	
	private function URLToUser($url) {
		$url = explode(":", $url, 2);
		
		return end($url);
	}
	
	private function timestamp() {
		return str_replace(".", "", microtime(1));
	}
	
	public function sendMessage($user, $message) {
		$user = $this->URLtoUser($user);
		$mode = strstr($user, "thread.skype") ? 19 : 8;
		$messageID = $this->timestamp();
		$post = [
			"content" => $message,
			"messagetype" => "RichText",
			"contenttype" => "text",
			"clientmessageid" => $messageID
		];
		
		$req = json_decode($this->web("https://client-s.gateway.messenger.live.com/v1/users/ME/conversations/$mode:$user/messages", "POST", json_encode($post)), true);
		
		return isset($req["OriginalArrivalTime"]) ? $messageID : 0;
	}
	
	public function getMessagesList($user, $size = 100) {
		$user = $this->URLtoUser($user);
		if ($size > 199 or $size < 1)
			$size = 199;
		$mode = strstr($user, "thread.skype") ? 19 : 8;
		
		$req = json_decode($this->web("https://client-s.gateway.messenger.live.com/v1/users/ME/conversations/$mode:$user/messages?startTime=0&pageSize=$size&view=msnp24Equivalent&targetType=Passport|Skype|Lync|Thread"), true);
		
		return !isset($req["message"]) ? $req["messages"] : [];
	}
	
	public function createGroup($users = [], $topic = "") {
		$users = [];
		
		foreach ($users as $user)
			$members["members"][] = ["id" => "8:".$this->URLtoUser($user), "role" => "User"];
		
		$members["members"][] = ["id" => "8:{$this->username}", "role" => "Admin"];
		
		$req = $this->web("https://client-s.gateway.messenger.live.com/v1/threads", "POST", json_encode($members), true);
		preg_match("`19\:(.+)\@thread.skype`isU", $req, $group);
		
		$group = isset($group[1]) ? "{$group[1]}@thread.skype" : "";
		
		if (!empty($topic) && !empty($group))
			$this->setGroupTopic($group, $topic);
		
		return $group;
	}
	
	public function setGroupTopic($group, $topic) {
		$group = $this->URLtoUser($group);
		$post = [
			"topic" => $topic
		];
		
		$this->web("https://client-s.gateway.messenger.live.com/v1/threads/19:$group/properties?name=topic", "PUT", json_encode($post));
	}
	
	public function getGroupInfo($group) {
		$group = $this->URLtoUser($group);
		$req = json_decode($this->web("https://client-s.gateway.messenger.live.com/v1/threads/19:$group?view=msnp24Equivalent", "GET"), true);
		
		return !isset($req["code"]) ? $req : [];
	}
	
	public function addUserToGroup($group, $user) {
		$user = $this->URLtoUser($user);
		$post = [
			"role" => "User"
		];
		
		$req = $this->web("https://client-s.gateway.messenger.live.com/v1/threads/19:$group/members/8:$user", "PUT", json_encode($post));
		
		return empty($req);
	}
	
	public function kickUser($group, $user) {
		$user = $this->URLtoUser($user);
		$req = $this->web("https://client-s.gateway.messenger.live.com/v1/threads/19:$group/members/8:$user", "DELETE");
		
		return empty($req);
	}
	
	public function leaveGroup($group) {
		$req = $this->kickUser($group, $this->username);
		
		return $req;
	}
	
	public function ifGroupHistoryDisclosed($group, $historydisclosed) {
		$group = $this->URLtoUser($group);
		$post = [
			"historydisclosed" => $historydisclosed
		];
		
		$req = $this->web("https://client-s.gateway.messenger.live.com/v1/threads/19:$group/properties?name=historydisclosed", "PUT", json_encode($post));
		
		return empty($req);
	}
	
	public function getContactsList() {
		$req = json_decode($this->web("https://contacts.skype.com/contacts/v1/users/{$this->username}/contacts?\$filter=type%20eq%20%27skype%27%20or%20type%20eq%20%27msn%27%20or%20type%20eq%20%27pstn%27%20or%20type%20eq%20%27agent%27&reason=default"), true);
		
		return isset($req["contacts"]) ? $req["contacts"] : [];
	}
	
	public function readProfile($list) {
		$contacts = "";
		foreach ($list as $contact)
			$contacts .= "contacts[]=$contact&";
		
		$req = json_decode($this->web("https://api.skype.com/users/self/contacts/profiles", "POST", $contacts), true);
		
		return !empty($req) ? $req : [];
	}
	
	public function readMyProfile() {
		$req = json_decode($this->web("https://api.skype.com/users/self/profile"), true);
		
		return !empty($req) ? $req : [];
	}
	
	public function searchSomeone($username) {
		$username = $this->URLtoUser($username);
		$req = json_decode($this->web("https://skypegraph.skype.com/search/v1.1/namesearch/swx/?requestid=skype.com-1.63.51&searchstring=$username"), true);
		
		return !empty($req) ? $req : [];
	}
	
	public function addContact($username, $greeting = "Hello, I would like to add you to my contacts.") {
		$username = $this->URLtoUser($username);
		$post = [
			"greeting" => $greeting
		];
		
		$req = $this->web("https://api.skype.com/users/self/contacts/auth-request/$username", "PUT", $post);
		$data = json_decode($req, true);
		
		return isset($data["code"]) && $data["code"] == 20100;
	}
	
	public function skypeJoin($id) {
		$post = [
			"shortId" => $id,
			"type" => "wl"
		];
		$group = $this->web("https://join.skype.com/api/v2/conversation/", "POST", json_encode($post), false, false, false, ["Content-Type: application/json"]);
		$group = json_decode($group, true);
		
		if (!isset($group["Resource"]))
			return "";
		
		$group = str_replace("19:", "", $group["Resource"]);
		
		return $this->addUserToGroup($group, $this->username);
	}

	public function getToken() {
		return $this->skypeToken;
	}
	
}