<?php

use App\Controller\DefaultController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

require_once 'xml.php';

\header('Access-Control-Allow-Origin: https://itkom.amocrm.ru');

if ('POST' != $_SERVER['REQUEST_METHOD'] && 'GET' != $_SERVER['REQUEST_METHOD']) {
    return;
}

$mainRequest = $request;

\set_time_limit($total_timeout + $http_timeout + 15);

$result = [];
$error = false;
//$sources = 'rossvyaz,facebook,vk,ok,instagram,announcement,boards,commerce,skype,viber,whatsapp,getcontact,numbuster,emt,truecaller,callapp';
$sources = implode(',', array_keys($_REQUEST['sources']));
// $sources .= ',yamap,2gis,hh,telegram';
$_REQUEST = json_decode($request->getContent(), true);

if (!isset($_REQUEST['phone'])) {
    $error = 'Указаны не все обязательные параметры (телефон)';
}

if (!isset($_REQUEST['userid']) || !isset($_REQUEST['password'])) {
    $error = 'Указаны не все обязательные параметры (логин и пароль)';
}

//if (isset($_REQUEST['type']) && ('extended' == $_REQUEST['type'])) {
//    $sources .= ',infobip';
//}

if (!$error) {
    if ('+7' == \substr($_REQUEST['phone'], 0, 2)) {
        $_REQUEST['phone'] = \substr($_REQUEST['phone'], 2);
    }
    if ((11 == \strlen($_REQUEST['phone'])) && (('8' == \substr($_REQUEST['phone'], 0, 1)) || ('8' == \substr($_REQUEST['phone'], 0, 1)))) {
        $_REQUEST['phone'] = \substr($_REQUEST['phone'], 1);
    }

    $xml = "<Request>
              <UserIP>{$_SERVER['REMOTE_ADDR']}</UserIP>
              <UserID>{$user->getUserIdentifier()}</UserID>
              <Password>{$user->getPassword()}</Password>
              <requestId>" . \time() . "</requestId>
              <requestType>chey</requestType>
              <sources>{$sources}</sources>
              <PhoneReq><phone>{$_REQUEST['phone']}</phone></PhoneReq>
            </Request>";

    $subRequest = Request::create($urlGenerator->generate(DefaultController::NAME), Request::METHOD_POST, content: $xml);
    $subRequest->attributes->set('_controller', DefaultController::class);
    $subRequest->setSession($mainRequest->getSession());
    $response = $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    $data = $response->getContent();
}

if ($error) {
    $result['error'] = $error;
} else {
    $data = \substr($data, \strpos($data, '<?xml'));
    try {
        $xml = \simplexml_load_string($data);
    } catch (\Throwable $e) {
        $logger->error('Failed XML', [
            'file' => __FILE__,
            'xml' => $data,
        ]);

        throw $e;
    }
    $result['requestid'] = (string)$xml['id'];
    $result['phone'] = $_REQUEST['phone'];
    $result['image'] = '';
    if ('800' == \substr($_REQUEST['phone'], 0, 3)) {
        $result['type'] = 'org';
    }
    $social = [];
    $messenger = [];
    foreach ($xml->Source as $source) {
        if (('Facebook' == $source->Name) && (string)$source->ResultsCount) {
            if (!\in_array('facebook', $social)) {
                $social[] = 'facebook';
            }
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    $pos = \strpos($name, '(');
                    //                    if($pos) $name = trim(substr($name,0,$pos));
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'Name') && !isset($result['name'])){
                                    $result['name'] = strval($field->FieldValue);
                                    $pos = strpos($result['name'],'(');
                                    if($pos) $result['name'] = substr($result['name'],0,$pos);
                                }
                */
                if ('Photo' == $field->FieldName) {
                    $result['image'] = (string)$field->FieldValue;
                }
                if ('Profile' == $field->FieldName) {
                    if (!isset($result['facebook']) || false === \array_search((string)$field->FieldValue, $result['facebook'])) {
                        $result['facebook'][] = (string)$field->FieldValue;
                    }
                }
                if ('Type' == $field->FieldName && (!$result['type'])) {
                    $result['type'] = 'user' == (string)$field->FieldValue ? 'person' : 'org';
                }
                if ('livingplace' == $field->FieldName) {
                    $result['location'][] = (string)$field->FieldValue;
                }
                if ('birthdate' == $field->FieldName) {
                    $result['birthdate'] = (string)$field->FieldValue;
                }
                if ('gender' == $field->FieldName) {
                    $result['gender'] = (string)$field->FieldValue;
                }
                if ('job' == $field->FieldName) {
                    $result['info'] = ($result['info'] ? $result['info'] . '; ' : '') .
                        (string)$field->FieldValue;
                }
                if ('website' == $field->FieldName) {
                    $result['url'] = (string)$field->FieldValue;
                }
                if ('presence' == $field->FieldName) {
                    if ('mobile' == (string)$field->FieldValue) {
                        $result['smartphone'] = 1;
                    }
                }
            }
        } elseif (('VK' == $source->Name) && (string)$source->ResultsCount) {
            $result['type'] = 'person';
            if (!\in_array('vk', $social)) {
                $social[] = 'vk';
            }
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'Name') && !isset($result['name'])){
                                    $result['name'] = strval($field->FieldValue);
                                    $pos = strpos($result['name'],'(');
                                    if($pos) $result['name'] = substr($result['name'],0,$pos);
                                }
                */
                if ('Photo' == $field->FieldName) {
                    $result['image'] = (string)$field->FieldValue;
                }
                if ('Link' == $field->FieldName) {
                    if (!isset($result['vk']) || false === \array_search((string)$field->FieldValue, $result['vk'])) {
                        $result['vk'][] = (string)$field->FieldValue;
                    }
                }
                if ('livingplace' == $field->FieldName) {
                    $result['location'][] = (string)$field->FieldValue;
                }
                if ('birthday' == $field->FieldName) {
                    $result['birthdate'] = (string)$field->FieldValue;
                }
                if ('job' == $field->FieldName) {
                    $result['info'] = ($result['info'] ? $result['info'] . '; ' : '') .
                        (string)$field->FieldValue;
                }
                if ('website' == $field->FieldName) {
                    $result['url'] = (string)$field->FieldValue;
                }
                if ('presence' == $field->FieldName) {
                    if ('mobile' == (string)$field->FieldValue) {
                        $result['smartphone'] = 1;
                    }
                }
            }
        } elseif (('OK' == $source->Name) && (string)$source->ResultsCount) {
            $result['type'] = 'person';
            if (!\in_array('ok', $social)) {
                $social[] = 'ok';
            }
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    $pos = \strpos($name, '**');
                    //                    if($pos) $name = trim(substr($name,0,$pos));
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'Name') && !isset($result['name'])){
                                    $result['name'] = strval($field->FieldValue);
                                    $pos = strpos($result['name'],'(');
                                    if($pos) $result['name'] = substr($result['name'],0,$pos);
                                }
                */
            }
        } elseif (('Instagram' == $source->Name) && (string)$source->ResultsCount) {
            $result['type'] = 'person';
            if (!\in_array('instagram', $social)) {
                $social[] = 'instagram';
            }
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                if ('Image' == $field->FieldName) {
                    $result['image'] = (string)$field->FieldValue;
                }
                if ('Link' == $field->FieldName) {
                    if (!isset($result['instagram']) || false === \array_search((string)$field->FieldValue, $result['instagram'])) {
                        $result['instagram'][] = (string)$field->FieldValue;
                    }
                }
                if ('Website' == $field->FieldName) {
                    $result['url'] = (string)$field->FieldValue;
                }
            }
            $result['smartphone'] = 1;
        } elseif (('Beholder' == $source->Name) && (string)$source->ResultsCount) {
            $result['type'] = 'person';
            if (!\in_array('vk', $social)) {
                $social[] = 'vk';
            }
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'Name') && !isset($result['name'])){
                                    $result['name'] = strval($field->FieldValue);
                                    $pos = strpos($result['name'],'(');
                                    if($pos) $result['name'] = substr($result['name'],0,$pos);
                                }
                */
                if ('Photo' == $field->FieldName) {
                    //                    $result['image'] = strval($field->FieldValue);
                }
                if ('Link' == $field->FieldName) {
                    //                    $result['vk'][] = strval($field->FieldValue);
                }
            }
        } elseif (('VK-Phone' == $source->Name) && (string)$source->ResultsCount) {
            $result['type'] = 'person';
            if (!\in_array('vk', $social)) {
                $social[] = 'vk';
            }
        } elseif (('HH' == $source->Name) && (string)$source->ResultsCount) {
            $result['type'] = 'person';
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if($field->FieldName == 'Name'){
                                    $result['name'] = strval($field->FieldValue);
                                }
                */
                if ('Photo' == $field->FieldName) {
                    $result['image'] = (string)$field->FieldValue;
                }
                if ('Age' == $field->FieldName) {
                    $result['age'] = (string)$field->FieldValue;
                }
                if ('BirthDate' == $field->FieldName) {
                    $result['birthdate'] = (string)$field->FieldValue;
                }
                if ('Gender' == $field->FieldName) {
                    $result['gender'] = (string)$field->FieldValue;
                }
                if (('City' == $field->FieldName) && (string)$field->FieldValue) {
                    $result['location'][] = (string)$field->FieldValue;
                }
                if (('Metro' == $field->FieldName) && (string)$field->FieldValue) {
                    $result['location'][\count($result['location']) - 1] .= ', м.' . (string)$field->FieldValue;
                }
                if ('Occupation' == $field->FieldName) {
                    $result['info'] = (isset($result['info']) ? $result['info'] . '; ' : '') .
                        (string)$field->FieldValue;
                }
            }
        } elseif (('Announcement' == $source->Name) && (string)$source->ResultsCount) {
            //            $result['type'] = 'person';
            foreach ($source->Record->Field as $field) {
                if ('contact_name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'contact_name') && !isset($result['name'])){
                                    $result['name'] = strval($field->FieldValue);
                                }
                */
                if ('region' == $field->FieldName/* && !isset($result['location']) */) {
                    $result['location'][] = (string)$field->FieldValue;
                }
                if (('city' == $field->FieldName) && (string)$field->FieldValue) {
                    $result['location'][\count($result['location']) - 1] .= ',' . (string)$field->FieldValue;
                }
                if (('metro' == $field->FieldName) && (string)$field->FieldValue) {
                    $result['location'][\count($result['location']) - 1] .= ',м.' . (string)$field->FieldValue;
                }
                if (('address' == $field->FieldName) && (string)$field->FieldValue) {
                    $result['location'][\count($result['location']) - 1] .= ',' . (string)$field->FieldValue;
                }
            }
        } elseif (('boards' == $source->Name) && (string)$source->ResultsCount) {
            //            $result['type'] = 'person';
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                if (('Location' == $field->FieldName) && (string)$field->FieldValue) {
                    $result['location'][] = (string)$field->FieldValue;
                }
            }
        } elseif (('2GIS' == $source->Name) && (string)$source->ResultsCount) {
            if (!$result['type']) {
                $result['type'] = 'org';
            }
            if ('org' == $result['type']) {
                foreach ($source->Record->Field as $field) {
                    if ('name' == $field->FieldName) {
                        $name = (string)$field->FieldValue;
                        if (isset($result['name'])) {
                            if (false === \array_search($name, $result['name'])) {
                                $result['name'][] = $name;
                            }
                        } else {
                            $result['name'][] = $name;
                        }
                    }
                    /*
                                    if(($field->FieldName == 'name') && !isset($result['name'])) {
                                        $result['name'] = strval($field->FieldValue);
                                    }
                    */
                    if (('categories' == $field->FieldName) && !isset($result['info'])) {
                        $result['info'] = (string)$field->FieldValue;
                    }
                    if ('address' == $field->FieldName) {
                        $result['location'][] = (string)$field->FieldValue;
                    }
                    if (('website' == $field->FieldName) && !isset($result['url'])) {
                        $result['url'] = (string)$field->FieldValue;
                    }
                }
            }
        } elseif (('YaMap' == $source->Name) && (string)$source->ResultsCount) {
            if (!$result['type']) {
                $result['type'] = 'org';
            }
            if ('org' == $result['type']) {
                foreach ($source->Record->Field as $field) {
                    if ('name' == $field->FieldName) {
                        $name = (string)$field->FieldValue;
                        if (isset($result['name'])) {
                            if (false === \array_search($name, $result['name'])) {
                                $result['name'][] = $name;
                            }
                        } else {
                            $result['name'][] = $name;
                        }
                    }
                    /*
                                    if(($field->FieldName == 'name') && !isset($result['name'])) {
                                        $result['name'] = strval($field->FieldValue);
                                    }
                    */
                    if (('categories' == $field->FieldName) && !isset($result['info'])) {
                        $result['info'] = (string)$field->FieldValue;
                    }
                    if ('address' == $field->FieldName) {
                        $result['location'][] = (string)$field->FieldValue;
                    }
                    if (('url' == $field->FieldName) && !isset($result['url'])) {
                        $result['url'] = (string)$field->FieldValue;
                    }
                }
            }
        } elseif (('TrueCaller' == $source->Name) && (string)$source->ResultsCount) {
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'Name') && !isset($result['name'])) {
                                    $result['name'] = strval($field->FieldValue);
                                }
                */
                if ('Address' == $field->FieldName/* && !isset($result['location']) */) {
                    //                    $result['location'][] = strval($field->FieldValue);
                }
                if ('Website' == $field->FieldName) {
                    if (!$result['type']) {
                        $result['type'] = 'org';
                    }
                    if (!isset($result['url'])) {
                        $result['url'] = (string)$field->FieldValue;
                    }
                }
            }
        } elseif (('emt' == $source->Name) && (string)$source->ResultsCount) {
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'Name') && !isset($result['name'])) {
                                    $result['name'] = strval($field->FieldValue);
                                }
                */
            }
        } elseif (('NumBuster' == $source->Name) && (string)$source->ResultsCount) {
            $first_name = '';
            foreach ($source->Record->Field as $field) {
                if ('first_name' == $field->FieldName) {
                    $first_name = (string)$field->FieldValue;
                }
                if ('last_name' == $field->FieldName) {
                    $name = \trim($first_name . ' ' . (string)$field->FieldValue);
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'last_name') && !isset($result['name'])) {
                                    $result['name'] = strval($field->FieldValue) . " " . $first_name;
                                }
                */
            }
        } elseif (('GetContact' == $source->Name) && (string)$source->ResultsCount) {
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'Name') && !isset($result['name'])) {
                                    $result['name'] = strval($field->FieldValue);
                                }
                */
            }
        } elseif (('PhoneNumber' == $source->Name) && (string)$source->ResultsCount) {
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'Name') && !isset($result['name'])) {
                                    $result['name'] = strval($field->FieldValue);
                                }
                */
                if ('Address' == $field->FieldName/* && !isset($result['location']) */) {
                    $result['location'][] = (string)$field->FieldValue;
                }
            }
        } elseif (('Sberbank' == $source->Name) && (string)$source->ResultsCount) {
            $result['type'] = 'person';
            foreach ($source->Record->Field as $field) {
                if ('name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'name') && !isset($result['name'])) {
                                    $result['name'] = strval($field->FieldValue);
                                }
                */
            }
        } elseif (('Tinkoff' == $source->Name) && (string)$source->ResultsCount) {
            $result['type'] = 'person';
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    $pos = \strpos($name, '**');
                    //                    if($pos) $name = trim(substr($name,0,$pos));
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'name') && !isset($result['name'])) {
                                    $result['name'] = strval($field->FieldValue);
                                }
                */
            }
        } elseif ('Rossvyaz' == $source->Name) {
            foreach ($source->Record->Field as $field) {
                if ('PhoneOperator' == $field->FieldName) {
                    $result['operator'] = (string)$field->FieldValue;
                }
                if ('PhoneStandart' == $field->FieldName) {
                    $result['standart'] = (string)$field->FieldValue;
                }
                if ('PhoneRegion' == $field->FieldName) {
                    $result['region'] = \trim((string)$field->FieldValue);
                }
                if ('Operator' == $field->FieldName) {
                    $result['s_operator'] = (string)$field->FieldValue;
                }
            }
        } elseif (('Skype' == $source->Name) && (string)$source->ResultsCount) {
            $result['type'] = 'person';
            if (!\in_array('skype', $messenger)) {
                $messenger[] = 'skype';
            }
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                /*
                                if(($field->FieldName == 'Name') && !isset($result['name'])) {
                                    $result['name'] = strval($field->FieldValue);
                                }
                */
                if ('Login' == $field->FieldName) {
                    if (!isset($result['skype']) || false === \array_search((string)$field->FieldValue, $result['skype'])) {
                        $result['skype'][] = (string)$field->FieldValue;
                    }
                }
                if ('Birthday' == $field->FieldName) {
                    $result['birthdate'] = (string)$field->FieldValue;
                }
                if (('City' == $field->FieldName) && (string)$field->FieldValue) {
                    $result['location'][] = (string)$field->FieldValue;
                }
                if (('Avatar' == $field->FieldName) && !$result['image']) {
                    $result['image'] = (string)$field->FieldValue;
                }
            }
        } elseif (('WhatsApp' == $source->Name) && (string)$source->ResultsCount) {
            $result['type'] = 'person';
            if (!\in_array('whatsapp', $messenger)) {
                $messenger[] = 'whatsapp';
            }
            $result['smartphone'] = 1;
            foreach ($source->Record->Field as $field) {
                if ('Photo' == $field->FieldName) {
                    $result['image'] = (string)$field->FieldValue;
                }
            }
        } elseif (('Viber' == $source->Name) && (string)$source->ResultsCount) {
            $result['type'] = 'person';
            if (!\in_array('viber', $messenger)) {
                $messenger[] = 'viber';
            }
            $result['smartphone'] = 1;
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                if ('Photo' == $field->FieldName) {
                    $result['image'] = (string)$field->FieldValue;
                }
            }
        } /*
                elseif(($source->Name == 'CheckWA') && strval($source->ResultsCount)){
                    $result['type'] = 'person';
                    if (!in_array('whatsapp',$messenger)) $messenger[] = 'whatsapp';
                    $result['smartphone'] = 1;
                    foreach($source->Record->Field as $field){
                        if($field->FieldName == 'Image'){
                            $result['image'] = strval($field->FieldValue);
                        }
                    }
            }
        */
        elseif (('Telegram' == $source->Name) && (string)$source->ResultsCount) {
            $result['type'] = 'person';
            if (!\in_array('telegram', $messenger)) {
                $messenger[] = 'telegram';
            }
            $result['smartphone'] = 1;
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $name = (string)$field->FieldValue;
                    if (isset($result['name'])) {
                        if (false === \array_search($name, $result['name'])) {
                            $result['name'][] = $name;
                        }
                    } else {
                        $result['name'][] = $name;
                    }
                }
                if ('Photo' == $field->FieldName) {
                    $result['image'] = (string)$field->FieldValue;
                }
            }
        }
    }
}
if (isset($result['name'])) {
    $result['name'] = \implode('; ', $result['name']);
}
if (isset($result['location'])) {
    $result['location'] = \implode('; ', $result['location']);
}
if (\count($social ?? [])) {
    $result['social'] = \implode(',', $social);
}
if (\count($messenger ?? [])) {
    $result['messenger'] = \implode(',', $messenger);
}
if (isset($result['facebook'])) {
    $result['facebook'] = \implode('; ', $result['facebook']);
}
if (isset($result['vk'])) {
    $result['vk'] = \implode('; ', $result['vk']);
}
if (isset($result['instagram'])) {
    $result['instagram'] = \implode('; ', $result['instagram']);
}
if (isset($result['skype'])) {
    $result['skype'] = \implode('; ', $result['skype']);
}

// if ($result['name'] && !$result['type']) $result['type'] = 'person';

$answer = null;
if ('json' == $_REQUEST['mode']) {
    \header('Content-Type: application/json; charset=utf-8');
    $answer = \json_encode($result);
} elseif ('xml' == $_REQUEST['mode']) {
    \header('Content-Type: text/xml; charset=utf-8');
    //    $answer = xml_encode(array('response'=>$result));
    $answer = '<?xml version="1.0" encoding="utf-8"?><response>';
    foreach ($result as $var => $val) {
        $answer .= '<' . $var . '>' . $val . '</' . $var . '>';
    }
    $answer .= '</response>';
} elseif ('html' == $_REQUEST['mode']) {
    \header('Content-Type: text/html; charset=utf-8');
    $answer .= "<table class='table table-striped'>\n";
    foreach ($result as $var => $val) {
        if (empty($val)) {
            continue;
        }

        $answer .= "<tr><th>$var</th><td>" . ('image' == $var ? "<img src=\"$val\"/>" : (false === \strpos($val, 'http') ? '' : "<a href=\"$val\">") . $val . (false === \strpos($val, 'http') ? '' : '</a>')) . "</td></tr>\n";
    }
    $answer .= "</table>\n";
} else {
    \header('Content-Type: text/plain; charset=utf-8');
    $answer = '';
    foreach ($result as $var => $val) {
        $answer .= $var . ': ' . \html_entity_decode($val) . "\n";
    }
}
echo $answer;
