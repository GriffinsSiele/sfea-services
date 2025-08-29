<?php

class CommercePlugin implements PluginInterface
{
    private $titles = array (
           'id' => array('ID','ID'),
	   'fio' => array('ФИО','ФИО'),
	   'name' => array('ФИО','ФИО'),
	   'fullname' => array('ФИО','ФИО'),
	   'last_name' => array('Фамилия','Фамилия'),
	   'first_name' => array('Имя','Имя'),
	   'middle_name' => array('Отчество','Отчество'),
	   'phone' => array('Телефон','Телефон','phone'),
	   'mobile' => array('Мобильный телефон','Мобильный телефон','phone'),
	   'mobile_phone' => array('Мобильный телефон','Мобильный телефон','phone'),
	   'workphone' => array('Рабочий телефон','Рабочий телефон','phone'),
	   'user_email' => array('Личный e-mail','Личный e-mail','email'),
	   'work_email' => array('Рабочий e-mail','Рабочий e-mail','email'),
	   'email' => array('E-mail','E-mail','email'),
	   'skype' => array('Skype','Skype','skype'),
	   'address' => array('Адрес','Адрес'),
	   'birthday' => array('День рождения','День рождения'),
	   'birthdate' => array('Дата рождения','Дата рождения'),
	   'nic' => array('Псевдоним','Псевдоним'),
	   'city' => array('Город','Город'),
	   'gender' => array('Пол','Пол'),
	   'photo' => array('Фото','Фото'),
	   'registered' => array('Зарегистрирован','Зарегистрирован'),
    );

    public function getName()
    {
        return 'Commerce';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск по утечкам коммерческих данных',
            'commerce_phone' => 'Поиск телефона в утечках пользовательских данных',
            'commerce_email' => 'Поиск email в утечках пользовательских данных',
            'commerce_skype' => 'Поиск логина skype в утечках пользовательских данных',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск по утечкам коммерческих данных';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['phone']) && !isset($initData['email']) && !isset($initData['skype']) /*&& (!isset($initData['last_name']) || !isset($initData['first_name']))*/) {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (телефон, email или skype)');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();
        $url = 'https://ya.ru';
        curl_setopt($ch, CURLOPT_URL, $url);
        $rContext->setCurlHandler($ch);
        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $resultData = new ResultDataList();

        if (isset($initData['phone'])) {
            $phone_number = $initData['phone'];
            if ($phone_number)
                $phone_number = preg_replace("/\D/","",$phone_number);
            if ($phone_number && (strlen($phone_number)==11) && (substr($phone_number,0,1)=='8'))
                $phone_number = '7'.substr($phone_number,1);
            if ($phone_number && (strlen($phone_number)==10))
                $phone_number = '7'.$phone_number;
//             $data['phone'] = new ResultDataField('string','PhoneNumber', $phone_number, 'Номер', 'Номер телефона');
        } else {
            $phone_number = '(N/A)';
        }

        $email = isset($initData['email']) ? $initData['email'] : '(N/A)';

        $skype = isset($initData['skype']) ? $initData['skype'] : '(N/A)';

        $result = $mysqli->query("SELECT fio,phone,mobile,workphone,email,address FROM announcement.wildberries WHERE phone='$phone_number' or mobile='$phone_number' or workphone='$phone_number' or email='$email'");
        if ($result) {
            while( $row = $result->fetch_assoc()){
                $data = array();
                foreach( $row as $key => $val ){
                    $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
                    if ($val)
                        $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
		}
		$resultData->addResult($data);
            }
            $result->close();
        }

        $result = $mysqli->query("SELECT name,phone,mobile,birthdate,email,address FROM announcement.kupivip WHERE phone='$phone_number' or mobile='$phone_number' or email='$email'");
        if ($result) {
            while( $row = $result->fetch_assoc()){
                $data = array();
                foreach( $row as $key => $val ){
                    $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
                    if ($key=='birthdate') {
                        if (substr($val,0,4)=='0000') 
                            $val=false;
                        else
                            $val = date("d.m.Y",strtotime($val));
                    }
                    if ($val)
                        $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
		}
		$resultData->addResult($data);
            }
            $result->close();
        }

        $result = $mysqli->query("SELECT last_name,first_name,middle_name,mobile_phone,user_email,work_email FROM announcement.lamoda WHERE mobile_phone='$phone_number' OR user_email='$email' OR work_email='$email'");
        if ($result) {
            while( $row = $result->fetch_assoc()){
                $data = array();
                foreach( $row as $key => $val ){
                    $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
                    if ($val)
                        $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
		}
		$resultData->addResult($data);
            }
            $result->close();
        }
        
        $result = $mysqli->query("SELECT nic,user_email,name,phone FROM announcement.fl WHERE phone='$phone_number' OR user_email='$email'");
        if ($result) {
            while( $row = $result->fetch_assoc()){
                $data = array();
                foreach( $row as $key => $val ){
                    $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
                    if ($val)
                        $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                }
                $resultData->addResult($data);
            }
            $result->close();
        }

        $result = $mysqli->query("SELECT substring(birth,6) as birthday,birth as birthdate,city,email,fullname,gender,phone,skype FROM announcement.sprash WHERE phone='$phone_number' OR email='$email' OR skype='$skype'");
//        $result = $mysqli->query("SELECT substring(birth,6) as birthday,birth as birthdate,city,email,facebook_id,fullname,gender,phone,skype,twitter,vkontakte FROM announcement.sprash WHERE phone='$phone_number' OR email='$email' OR skype='$skype'");
        if ($result) {
            while( $row = $result->fetch_assoc()){
                $data = array();
                foreach( $row as $key => $val ){
                    $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
                    if ($key=='birthdate') {
                        if (substr($val,0,4)=='0000') 
                            $val=false;
                        else
                            $val = date("d.m.Y",strtotime($val));
                    }
                    if ($val)
                        $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                }
                $resultData->addResult($data);
            }
            $result->close();
        }

        $rContext->setResultData($resultData);
        $rContext->setFinished();

        return true;
    }
}

?>