<?php

namespace app\src;

use Iterator;

class LogAnalyzer
{
    private string $path;
    private Options $options;

    const DATE_PATTERN = "(\d{2}\/\D{3}\/\d{4})";
    const TIME_PATTERN = "(\d{2}:\d{2}:\d{2}\s)";
    const PROTOCOL_PATTERN = "(HTTP\/\d\.?\d?)";
    const RESPONSE_CODE_PATTERN = "(\s[1-5][0-9][0-9]\s)";
    const REQUEST_TIME_PATTERN = "(\\d+\\.\\d+)?";
    const MAX_FAILS_COUNT = 1000000;

    public function __construct(string $path)
    {
        if ($path !== 'php://stdin' && !file_exists($path)) {
            throw new \Exception("File not found");
        }

        $this->path = $path;
    }

    public function read(): Iterator
    {
        if (empty($this->options)) {
            $this->setOptions(new Options());
        }

        $datePattern = self::DATE_PATTERN;
        $timePattern = self::TIME_PATTERN;
        $protocolPattern = self::PROTOCOL_PATTERN;
        $responseCodePattern = self::RESPONSE_CODE_PATTERN;

        $match = "/$datePattern:$timePattern.+$protocolPattern\"$responseCodePattern/";

        $failsAccumulator = null;
        $failsCount = 0;

        $handler = fopen($this->path, "r");
        $reset = true;
        while ($line = fgets($handler)) {
            $matches = [];
            $found = preg_match_all($match, $line, $matches);
            if (!$found) {
                continue;
            }

            if ($reset) {
                $failsAccumulator = ['count' => 0, 'data' => []];
                $failsCount = 0;
                $reset = false;
            }

            $failsAccumulator['count']++;

            $date = $matches[1][0];
            $time = $matches[2][0];
            $responseCode = intval($matches[4][0]);

            if ($responseCode != 200) {
                if (!isset($failsAccumulator[$date . $time])) {
                    $failsAccumulator['data'][$failsAccumulator['count']] = ['date' => $date . ' ' . $time];
                    $failsCount++;
                }
            }

            //Подсчет и сброс каждый промежуток запросов
            if ($failsCount >= self::MAX_FAILS_COUNT || $failsAccumulator['count'] >= $this->options->getScale()) {
                $reset = true;
                yield $failsAccumulator;
            }
        }

        //Если прочитали весь файл, но промежуток меньше заданного, считаем остаток
        if (!empty($failsAccumulator)) {
            yield $failsAccumulator;
        }

        fclose($handler);
    }

    public function analyze(): Iterator
    {
        $fails = $this->read();
        foreach ($fails as $i => $failsAccumulator) {
            $count = $failsAccumulator['count'];
            $failsAccumulator = $failsAccumulator['data'];
            
            $rate = 100;
            $successCount = $count;
            $gapsData = null;
            $reset = false;
            for ($i = 0; $i < $count; $i++) {
                if ($reset) {
                    $gapsData = null;
                    $reset = false;
                }

                $exists = isset($failsAccumulator[$i]) ? $failsAccumulator[$i] : null;
                if ($exists) {
                    $successCount--;
                } elseif ($successCount < $count) {
                    $successCount++;
                }

                $rate = $successCount / $count * 100;
                if ($rate < $this->options->getPercent() && $exists) {
                    if (empty($gapsData)) {
                        $gapsData = [
                            'start' => $exists['date'],
                            'rate' => 100
                        ];
                    }

                    if ($gapsData['rate'] > $rate) {
                        $gapsData['rate'] = $rate;
                    }
                    $gapsData['end'] = $exists['date'];
                } elseif ($gapsData) {
                    $reset = true;
                    yield $gapsData;
                }
            }

            if (!empty($gapsData)) {
                yield $gapsData;
            }
        }
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function setOptions(Options $options): void
    {
        $this->options = $options;
    }
}
