<?php

include('metrika.html');
include('jivosite.html');
include('reformal.html');

//echo "<!-- metrics.i-sphere.ru -->\n";
echo "<img src=\"https://metrics.i-sphere.ru/watch/12345?u=".(isset($_SERVER['PHP_AUTH_USER'])?$_SERVER['PHP_AUTH_USER']:'')."\" style=\"position:absolute; left:-9999px;\" alt=\"\" />\n";
//echo "<!-- /metrics.i-sphere.ru -->\n";
