<?php

function pass()
{
    $pass = array();
    $letters = [];
    for($i=0; $i<26; $i++) $letters[]=chr(65+$i);
    $digits = array();
    for($i=0; $i<10; $i++) $digits[]=chr(48+$i);
    $symbols = array('-','.','_','*','%','$','#','@','!','(',')','+','=');
    for($i=0; $i<6; $i++) {
        $p=rand(0,sizeof($pass));
        $j=rand(0,sizeof($letters)-1);
        $c=$letters[$j];
        if (rand(0,99)<$i*20) $c=strtolower($c);
        array_splice($pass,$p,0,$c);
        array_splice($letters,$j,1);
    }
//    for($i=0; $i<1; $i++) {
        $p=rand(0,sizeof($pass));
        $j=rand(0,sizeof($digits)-1);
        array_splice($pass,$p,0,$digits[$j]);
//        array_splice($digits,$j,1);
//    }
//    for($i=0; $i<1; $i++) {
        $p=rand(1,sizeof($pass));
        $j=rand(0,sizeof($symbols)-1);
        array_splice($pass,$p,0,$symbols[$j]);
//        array_splice($symbols,$j,1);
//    }
    return implode('',$pass);
}

echo pass();
