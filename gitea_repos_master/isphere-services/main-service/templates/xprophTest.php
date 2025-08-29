<?php

xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

foreach (\range(1, 1000) as $int) {
    $int * 2;
}

$xhprof_data = xhprof_disable();

include_once '/usr/share/php/xhprof_lib/utils/xhprof_lib.php';
include_once '/usr/share/php/xhprof_lib/utils/xhprof_runs.php';

$xhprof_runs = new XHProfRuns_Default();
$run_id = $xhprof_runs->save_run($xhprof_data, 'test');

echo "RUN ID: $run_id";
