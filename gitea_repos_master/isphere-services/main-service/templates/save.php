<?php

if ('POST' != $_SERVER['REQUEST_METHOD']) {
    return;
}

$xml = \file_get_contents('php://input');
\file_put_contents('logs/'.\time().'.xml', $xml);

echo '<ok/>';
