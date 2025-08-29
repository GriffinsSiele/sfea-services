<?php

function pass()
{
    $pass = [];
    $letters = [];
    for ($i = 0; $i < 26; ++$i) {
        $letters[] = \chr(65 + $i);
    }
    $digits = [];
    for ($i = 0; $i < 10; ++$i) {
        $digits[] = \chr(48 + $i);
    }
    $symbols = ['-', '.', '_', '*', '%', '$', '#', '@', '!', '(', ')', '+', '='];
    for ($i = 0; $i < 6; ++$i) {
        $p = \rand(0, \count($pass));
        $j = \rand(0, \count($letters) - 1);
        $c = $letters[$j];
        if (\rand(0, 99) < $i * 20) {
            $c = \strtolower($c);
        }
        \array_splice($pass, $p, 0, $c);
        \array_splice($letters, $j, 1);
    }
    //    for($i=0; $i<1; $i++) {
    $p = \rand(0, \count($pass));
    $j = \rand(0, \count($digits) - 1);
    \array_splice($pass, $p, 0, $digits[$j]);
    //        array_splice($digits,$j,1);
    //    }
    //    for($i=0; $i<1; $i++) {
    $p = \rand(1, \count($pass));
    $j = \rand(0, \count($symbols) - 1);
    \array_splice($pass, $p, 0, $symbols[$j]);
    //        array_splice($symbols,$j,1);
    //    }
    return \implode('', $pass);
}

echo pass();
