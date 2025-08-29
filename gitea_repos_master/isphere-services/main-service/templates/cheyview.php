<?php

function mysqli_result($res, $row = 0, $col = 0)
{
    $numrows = \mysqli_num_rows($res);
    if ($numrows && $row <= ($numrows - 1) && $row >= 0) {
        \mysqli_data_seek($res, $row);
        $resrow = (\is_numeric($col)) ? \mysqli_fetch_row($res) : \mysqli_fetch_assoc($res);
        if (isset($resrow[$col])) {
            return $resrow[$col];
        }
    }

    return false;
}

include 'config.php';
include 'auth.php';
include 'xml.php';

//     $mysqli = mysqli_connect ($database['server'],$database['login'],$database['password'],$database['name']);

$coda = '';
$userid = get_user_id($mysqli);
if ($userid && 14 != $userid && 24 != $userid) {
    $coda = " AND `user_id`='".$userid."'";
}

$id = (isset($_REQUEST['id']) && \preg_match("/^[1-9]\d+$/", $_REQUEST['id'])) ? $_REQUEST['id'] : '';
if (!$id) {
    echo 'Nothing to do';
    return;
}

$sql = "SELECT response FROM RequestIndex WHERE id='".$id."'".$coda.' LIMIT 1';
$res = $mysqli->query($sql);

if (!$res) {
    $result['error'] = $mysql->error;
} else {
    $data = mysqli_result($res);
    $res->close();
    $xml = \simplexml_load_string($data);
    $result['requestid'] = (string) $xml['id'];
    $result['phone'] = $xml->Request->PhoneReq->phone;
    if ('800' == \substr($result['phone'], 0, 3)) {
        $result['type'] = 'org';
    }
    $social = [];
    $messenger = [];
    foreach ($xml->Source as $source) {
        if (('Facebook' == $source->Name) && (string) $source->ResultsCount) {
            $social[] = 'facebook';
            foreach ($source->Record->Field as $field) {
                if (('Name' == $field->FieldName) && !isset($result['name'])) {
                    $result['name'] = (string) $field->FieldValue;
                    $pos = \strpos($result['name'], '(');
                    if ($pos) {
                        $result['name'] = \substr($result['name'], 0, $pos);
                    }
                }
                if ('Photo' == $field->FieldName) {
                    $result['image'] = (string) $field->FieldValue;
                }
                if ('Profile' == $field->FieldName) {
                    $result['facebook'] = (string) $field->FieldValue;
                }
                if ('Type' == $field->FieldName && (!$result['type'])) {
                    $result['type'] = 'user' == (string) $field->FieldValue ? 'person' : 'org';
                }
                if ('livingplace' == $field->FieldName) {
                    $result['location'] = (string) $field->FieldValue;
                }
                if ('birthdate' == $field->FieldName) {
                    $result['birthdate'] = (string) $field->FieldValue;
                }
                if ('gender' == $field->FieldName) {
                    $result['gender'] = (string) $field->FieldValue;
                }
                if ('job' == $field->FieldName) {
                    $result['info'] = ($result['info'] ? $result['info'].'; ' : '').
                                      (string) $field->FieldValue;
                }
                if ('website' == $field->FieldName) {
                    $result['url'] = (string) $field->FieldValue;
                }
                if ('presence' == $field->FieldName) {
                    if ('mobile' == (string) $field->FieldValue) {
                        $result['smartphone'] = 1;
                    }
                }
            }
        } elseif (('VK' == $source->Name) && (string) $source->ResultsCount) {
            $result['type'] = 'person';
            if (!\in_array('vk', $social)) {
                $social[] = 'vk';
            }
            foreach ($source->Record->Field as $field) {
                if (('Name' == $field->FieldName) && !isset($result['name'])) {
                    $result['name'] = (string) $field->FieldValue;
                    $pos = \strpos($result['name'], '(');
                    if ($pos) {
                        $result['name'] = \substr($result['name'], 0, $pos);
                    }
                }
                if ('Photo' == $field->FieldName) {
                    $result['image'] = (string) $field->FieldValue;
                }
                if ('Link' == $field->FieldName) {
                    $result['vk'] = (string) $field->FieldValue;
                }
                if ('livingplace' == $field->FieldName) {
                    $result['location'] = (string) $field->FieldValue;
                }
                if ('birthdate' == $field->FieldName) {
                    $result['birthdate'] = (string) $field->FieldValue;
                }
                if ('job' == $field->FieldName) {
                    $result['info'] = ($result['info'] ? $result['info'].'; ' : '').
                                      (string) $field->FieldValue;
                }
                if ('website' == $field->FieldName) {
                    $result['url'] = (string) $field->FieldValue;
                }
                if ('presence' == $field->FieldName) {
                    if ('mobile' == (string) $field->FieldValue) {
                        $result['smartphone'] = 1;
                    }
                }
            }
        } elseif (('Beholder' == $source->Name) && (string) $source->ResultsCount) {
            $result['type'] = 'person';
            if (!\in_array('vk', $social)) {
                $social[] = 'vk';
            }
            foreach ($source->Record->Field as $field) {
                if (('Name' == $field->FieldName) && !isset($result['name'])) {
                    $result['name'] = (string) $field->FieldValue;
                    $pos = \strpos($result['name'], '(');
                    if ($pos) {
                        $result['name'] = \substr($result['name'], 0, $pos);
                    }
                }
                if ('Photo' == $field->FieldName) {
                    $result['image'] = (string) $field->FieldValue;
                }
                if ('Link' == $field->FieldName) {
                    $result['vk'] = (string) $field->FieldValue;
                }
            }
        } elseif (('VK-Phone' == $source->Name) && (string) $source->ResultsCount) {
            $result['type'] = 'person';
            if (!\in_array('vk', $social)) {
                $social[] = 'vk';
            }
        } elseif (('HH' == $source->Name) && (string) $source->ResultsCount) {
            $result['type'] = 'person';
            foreach ($source->Record->Field as $field) {
                if ('Name' == $field->FieldName) {
                    $result['name'] = (string) $field->FieldValue;
                }
                if ('Photo' == $field->FieldName) {
                    $result['image'] = (string) $field->FieldValue;
                }
                if ('Age' == $field->FieldName) {
                    $result['age'] = (string) $field->FieldValue;
                }
                if ('BirthDate' == $field->FieldName) {
                    $result['birthdate'] = (string) $field->FieldValue;
                }
                if ('Gender' == $field->FieldName) {
                    $result['gender'] = (string) $field->FieldValue;
                }
                if (('City' == $field->FieldName) && (string) $field->FieldValue) {
                    $result['location'] = (string) $field->FieldValue;
                }
                if (('Metro' == $field->FieldName) && (string) $field->FieldValue) {
                    $result['location'] .= ',ì.'.(string) $field->FieldValue;
                }
                if ('Occupation' == $field->FieldName) {
                    $result['info'] = (isset($result['info']) ? $result['info'].'; ' : '').
                                      (string) $field->FieldValue;
                }
            }
        } elseif (('Announcement' == $source->Name) && (string) $source->ResultsCount) {
            //            $result['type'] = 'person';
            foreach ($source->Record->Field as $field) {
                if (('contact_name' == $field->FieldName) && !isset($result['name'])) {
                    $result['name'] = (string) $field->FieldValue;
                }
                if (('region' == $field->FieldName) && !isset($result['location'])) {
                    $result['location'] = (string) $field->FieldValue;
                }
                if (('city' == $field->FieldName) && (string) $field->FieldValue) {
                    $result['location'] = (string) $field->FieldValue;
                }
                if (('metro' == $field->FieldName) && (string) $field->FieldValue) {
                    $result['location'] .= ',ì.'.(string) $field->FieldValue;
                }
                if (('address' == $field->FieldName) && (string) $field->FieldValue) {
                    $result['location'] .= ','.(string) $field->FieldValue;
                }
            }
        } elseif (('2GIS' == $source->Name) && (string) $source->ResultsCount) {
            if (!$result['type']) {
                $result['type'] = 'org';
            }
            if ('org' == $result['type']) {
                foreach ($source->Record->Field as $field) {
                    if (('name' == $field->FieldName) && !isset($result['name'])) {
                        $result['name'] = (string) $field->FieldValue;
                    }
                    if (('categories' == $field->FieldName) && !isset($result['info'])) {
                        $result['info'] = (string) $field->FieldValue;
                    }
                    if ('address' == $field->FieldName) {
                        $result['location'] = (string) $field->FieldValue;
                    }
                    if (('website' == $field->FieldName) && !isset($result['url'])) {
                        $result['url'] = (string) $field->FieldValue;
                    }
                }
            }
        } elseif (('YaMap' == $source->Name) && (string) $source->ResultsCount) {
            if (!$result['type']) {
                $result['type'] = 'org';
            }
            if ('org' == $result['type']) {
                foreach ($source->Record->Field as $field) {
                    if (('name' == $field->FieldName) && !isset($result['name'])) {
                        $result['name'] = (string) $field->FieldValue;
                    }
                    if (('categories' == $field->FieldName) && !isset($result['info'])) {
                        $result['info'] = (string) $field->FieldValue;
                    }
                    if ('address' == $field->FieldName) {
                        $result['location'] = (string) $field->FieldValue;
                    }
                    if (('url' == $field->FieldName) && !isset($result['url'])) {
                        $result['url'] = (string) $field->FieldValue;
                    }
                }
            }
        } elseif (('TC' == $source->Name) && (string) $source->ResultsCount) {
            foreach ($source->Record->Field as $field) {
                if (('Name' == $field->FieldName) && !isset($result['name'])) {
                    $result['name'] = (string) $field->FieldValue;
                }
                if (('Address' == $field->FieldName) && !isset($result['location'])) {
                    //                    $result['location'] = strval($field->FieldValue);
                }
                if ('Website' == $field->FieldName) {
                    if (!$result['type']) {
                        $result['type'] = 'org';
                    }
                    if (!isset($result['url'])) {
                        $result['url'] = (string) $field->FieldValue;
                    }
                }
            }
        } elseif (('NumBuster' == $source->Name) && (string) $source->ResultsCount) {
            $first_name = '';
            foreach ($source->Record->Field as $field) {
                if (('first_name' == $field->FieldName) && !isset($result['name'])) {
                    $first_name = (string) $field->FieldValue;
                }
                if (('last_name' == $field->FieldName) && !isset($result['name'])) {
                    $result['name'] = (string) $field->FieldValue.' '.$first_name;
                }
            }
        } elseif (('PhoneNumber' == $source->Name) && (string) $source->ResultsCount) {
            foreach ($source->Record->Field as $field) {
                if (('Name' == $field->FieldName) && !isset($result['name'])) {
                    $result['name'] = (string) $field->FieldValue;
                }
                if (('Address' == $field->FieldName) && !isset($result['location'])) {
                    $result['location'] = (string) $field->FieldValue;
                }
            }
        } elseif (('Sberbank' == $source->Name) && (string) $source->ResultsCount) {
            $result['type'] = 'person';
            foreach ($source->Record->Field as $field) {
                if (('name' == $field->FieldName) && !isset($result['name'])) {
                    $result['name'] = (string) $field->FieldValue;
                }
            }
        } elseif ('Rossvyaz' == $source->Name) {
            foreach ($source->Record->Field as $field) {
                if ('PhoneOperator' == $field->FieldName) {
                    $result['operator'] = (string) $field->FieldValue;
                }
                if ('PhoneStandart' == $field->FieldName) {
                    $result['standart'] = (string) $field->FieldValue;
                }
                if ('PhoneRegion' == $field->FieldName) {
                    $result['region'] = \trim((string) $field->FieldValue);
                    //                    if (!isset($result['location']))
                    //                        $result['location'] = trim(strval($field->FieldValue));
                }
                if ('Operator' == $field->FieldName) {
                    $result['s_operator'] = (string) $field->FieldValue;
                }
            }
        } elseif (('Skype' == $source->Name) && (string) $source->ResultsCount) {
            $result['type'] = 'person';
            $messenger[] = 'skype';
            foreach ($source->Record->Field as $field) {
                if (('Avatar' == $field->FieldName) && !isset($result['name'])) {
                    $result['image'] = (string) $field->FieldValue;
                }
            }
        } elseif (('WhatsApp' == $source->Name) && (string) $source->ResultsCount) {
            $result['type'] = 'person';
            $messenger[] = 'whatsapp';
            $result['smartphone'] = 1;
            foreach ($source->Record->Field as $field) {
                if ('Image' == $field->FieldName) {
                    $result['image'] = (string) $field->FieldValue;
                }
            }
        }
        /*
                elseif(($source->Name == 'CheckWA') && strval($source->ResultsCount)){
        //            $result['type'] = 'person';
                    $messenger[] = 'whatsapp';
                    $result['smartphone'] = 1;
                    foreach($source->Record->Field as $field){
                        if($field->FieldName == 'Image'){
                            $result['image'] = strval($field->FieldValue);
                        }
                    }
            }
        */
        elseif (('Telegram' == $source->Name) && (string) $source->ResultsCount) {
            $result['type'] = 'person';
            $messenger[] = 'telegram';
            $result['smartphone'] = 1;
        }
    }
}
if (\count($social)) {
    $result['social'] = \implode(',', $social);
}
if (\count($messenger)) {
    $result['messenger'] = \implode(',', $messenger);
}

// if ($result['name'] && !$result['type']) $result['type'] = 'person';

if (isset($_REQUEST['mode']) && 'json' == $_REQUEST['mode']) {
    \header('Content-Type: application/json; charset=utf-8');
    $answer = \json_encode($result);
} elseif (isset($_REQUEST['mode']) && 'xml' == $_REQUEST['mode']) {
    \header('Content-Type: text/xml; charset=utf-8');
    //    $answer = xml_encode(array('response'=>$result));
    $answer = '<?xml version="1.0" encoding="utf-8"?><response>';
    foreach ($result as $var => $val) {
        $answer .= '<'.$var.'>'.$val.'</'.$var.'>';
    }
    $answer .= '</response>';
} elseif (isset($_REQUEST['mode']) && 'text' == $_REQUEST['mode']) {
    \header('Content-Type: text/plain; charset=utf-8');
    $answer = '';
    foreach ($result as $var => $val) {
        $answer .= $var.': '.\html_entity_decode($val)."\n";
    }
} else {
    \header('Content-Type: text/html; charset=utf-8');
    $answer .= "<table>\n";
    foreach ($result as $var => $val) {
        $answer .= "<tr><td>$var</td><td>".('image' == $var ? "<img src=\"$val\"/>" : (false === \strpos($val, 'http') ? '' : "<a href=\"$val\">").$val.(false === \strpos($val, 'http') ? '' : '</a>'))."</td></tr>\n";
    }
    $answer .= "</table>\n";
}
echo $answer;
