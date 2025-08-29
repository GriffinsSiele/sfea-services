<?php

if ($_SERVER['REQUEST_METHOD']!='POST') {
    exit();
}

$xml = file_get_contents('php://input');
file_put_contents('logs/'.time().'.xml',$xml);

echo '<ok/>';

