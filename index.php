<?php

use app\src\services\LogAnalyzer;

require_once('vendor/autoload.php');

try {
    $reader = new LogAnalyzer('php://stdin');
    $gaps = $reader->analyze();
    foreach ($gaps as $i => $gapsData) {
        $start = trim($gapsData['start']);
        $end = trim($gapsData['end']);
        $rateVal = number_format($gapsData['rate'], 2, '.', '');

        echo "$start - $end - $rateVal\n";
    }
} catch (Error $e) {
    echo $e->getMessage() . "\n";
}

print "Memory usage " . memory_get_peak_usage() / 1024 / 1024 . " MB \n";
