<?php

declare(strict_types=1);

use app\src\services\LogAnalyzer;
use app\src\services\Options;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LogAnalyzerTest extends TestCase
{

    public function testFileReading(): void
    {
        $options = new Options(['index.php', '-t', 30, '-u', 99.9, '-s', 5]);
        $analyzer = new LogAnalyzer(__DIR__ . '/test.log');
        $analyzer->setOptions($options);

        $logBlocks = $analyzer->read();
        $this->assertSame(2, iterator_count($logBlocks), 'failed responses blocks count');
    }

    public function testAnalyze(): void
    {
        $options = new Options(['index.php', '-t', 35, '-u', 80.0]);
        $analyzer = new LogAnalyzer(__DIR__ . '/test.log');
        $analyzer->setOptions($options);

        $gaps = $analyzer->analyze();
        $count = 0;
        foreach ($gaps as $i => $gapsData) {
            $start = trim($gapsData['start']);
            $end = trim($gapsData['end']);
            $rateVal = number_format($gapsData['rate'], 2, '.', '');

            $this->assertSame('14/06/2017 17:01:02', $start, 'gap date start');
            $this->assertSame('14/06/2017 17:01:12', $end, 'gap date end');
            $this->assertSame('70.00', $rateVal, 'gap rate value');

            $count++;
        }
        $this->assertSame(1, $count, 'analyze test 80%: found gaps count');

        $options = new Options(['index.php', '-t', 35, '-u', 90.0]);
        $analyzer->setOptions($options);

        $gaps = $analyzer->analyze();
        $this->assertSame(2, iterator_count($gaps), 'analyze test 90%: found gaps count');
    }
}
