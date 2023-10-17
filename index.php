<?php

use app\src\LogAnalyzer;

require_once('vendor/autoload.php');

try {
    $reader = new LogAnalyzer('php://stdin');
    $gaps = $reader->analyze();
    foreach ($gaps as $i => $gapsData) {
        $start = $gapsData['start'];
        $end = $gapsData['end'];
        $rate_val = $gapsData['rate'];

        echo "$start - $end - $rate_val\n";
    }
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}


print "Memory usage " . memory_get_peak_usage() / 1024 / 1024 . " MB \n";
