<?php
require('config.php');
require_once('routines.php');
set_time_limit(300);

require_once __DIR__ . '/vendor/autoload.php';

define('APPLICATION_NAME', 'People API PHP Quickstart');
define('CREDENTIALS_PATH', 'google/quickstart.json');
define('CLIENT_SECRET_PATH', 'google/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/people.googleapis.com-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_PeopleService::CONTACTS)
));

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfig(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');
  $client->setRedirectUri('http://localhost');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, json_encode($accessToken));
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Подключаемся к базе данных
$db = db_connect($database);
if (!$db) {
    sleep(5);
    die("Connection to SQL server failed\r\n");
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_PeopleService($client);

$cre = json_decode(file_get_contents(CREDENTIALS_PATH),true);
$token = $cre['access_token'];

if ($token) {
    // Проверяем нет ли уже сессии с таким токеном
    $count = db_select($db,"SELECT COUNT(*) count FROM session WHERE sourceid=17 AND sessionstatusid=2 AND token='$token'");
    if ($count==0) {
        // Завершаем старые сессии
        db_execute($db,"UPDATE session SET sessionstatusid=3, endtime=now() WHERE sourceid=17 AND sessionstatusid IN (2,6,7)");
        // Создаём новую сессию
        db_execute($db,"INSERT INTO session (sourceid,cookies,starttime,lasttime,sessionstatusid,captcha,token,server,sourceaccessid,proxyid) VALUES (17,'',now(),now(),2,'','$token','',NULL,NULL)");
    }
}

// Отключаемся от базы данных
db_close($db);
sleep(5);
