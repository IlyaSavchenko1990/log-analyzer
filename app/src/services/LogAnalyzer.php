<?php

namespace app\src\services;

use app\src\services\interfaces\ILogAnalyzer;
use Iterator;

class LogAnalyzer implements ILogAnalyzer
{
    private string $path;
    private Options $options;

    private string $pattern;
    private int $dateRgxpGroup;
    private int|null $reqTimeRgxpGroup;
    private int $codeRgxpGroup;
    private int|null $resTimeRgxpGroup;

    public function __construct(string $path)
    {
        if ($path !== 'php://stdin' && !file_exists($path)) {
            throw new \Exception("File not found");
        }

        $this->pattern = getenv('LOG_LINE_REGEXP');
        if (empty($this->pattern)) {
            throw new \Error('Pattern env not set!');
        }

        $this->dateRgxpGroup = intval(getenv('DATE_RGXP_GROUP'));
        if ($this->dateRgxpGroup === 0) {
            throw new \Error('date regexp group number not set!');
        }

        $this->codeRgxpGroup = intval(getenv('RESPONSE_CODE_RGXP_GROUP'));
        if ($this->codeRgxpGroup === 0) {
            throw new \Error('Response code regexp group number not set!');
        }

        $reqTimeGroup = getenv('DATETIME_RGXP_GROUP');
        if (is_numeric($reqTimeGroup)) {
            $this->reqTimeRgxpGroup = intval($reqTimeGroup);
        }
        $resTimeGroup = getenv('RESPONSE_TIME_RGXP_GROUP');
        if (is_numeric($resTimeGroup)) {
            $this->resTimeRgxpGroup = intval($resTimeGroup);
        }

        $this->path = $path;
    }

    /**
     * Reads log file, accumulate failed requests in pack and
     * yield pack per log lines scale maximum
     */
    public function read(): Iterator
    {
        if (empty($this->options)) {
            $this->setOptions(new Options());
        }

        $regexp = $this->pattern;
        $match = "/$regexp/";

        $failsBuffer = null;

        $handler = fopen($this->path, "r");
        $reset = true;
        $scale = $this->options->getScale();
        $responseTimeCheck = $this->options->getTime();

        while ($line = fgets($handler)) {
            $matches = [];
            $found = preg_match_all($match, $line, $matches);
            if (!$found) {
                continue;
            }

            if ($reset) {
                $failsBuffer = ['count' => 0, 'data' => []];
                $reset = false;
            }

            $failsBuffer['count']++;

            $date = $matches[$this->dateRgxpGroup][0];
            $responseCode = intval($matches[$this->codeRgxpGroup][0]);

            $dateTime = $date;
            if (!empty($this->reqTimeRgxpGroup) && isset($matches[$this->reqTimeRgxpGroup])) {
                $dateTime = $dateTime . ' ' . $matches[$this->reqTimeRgxpGroup][0];
            }

            $responseTime = null;
            if (!empty($this->resTimeRgxpGroup) && isset($matches[$this->resTimeRgxpGroup])) {
                $responseTimeValue = $matches[$this->resTimeRgxpGroup][0];
                if (is_numeric($responseTimeValue)) {
                    $responseTime = floatval($responseTimeValue);
                }
            }

            if (
                $responseCode >= 500
                || (!empty($responseTime) && !empty($responseTimeCheck) && $responseTime > $responseTimeCheck)
            ) {
                if (!isset($failsBuffer[$dateTime])) {
                    $failsBuffer['data'][$failsBuffer['count']] = ['date' => $dateTime];
                }
            }

            //yield if fails count more than buffer limit or reached scale maximum lines
            if (
                $this->checkMemoryLimit()
                || (is_numeric($scale) && $failsBuffer['count'] >= $scale)
            ) {
                $reset = true;
                yield $failsBuffer;
            }
        }

        //if lines lower than scale maximum
        if (!$reset && !empty($failsBuffer)) {
            yield $failsBuffer;
        }

        fclose($handler);
    }

    /**
     * Count failed requests pack and yield
     * gap data (start/end dates of gap and failed percentage) for output
     * if percentage lower than value set in options
     */
    public function analyze(): Iterator
    {
        $fails = $this->read();
        foreach ($fails as $i => $failsBuffer) {
            $count = $failsBuffer['count'];
            $failsBuffer = $failsBuffer['data'];

            $rate = 100;
            $successCount = $count;
            $gapsData = null;
            $reset = false;
            for ($i = 0; $i < $count; $i++) {
                if ($reset) {
                    $gapsData = null;
                    $reset = false;
                }

                //count each line of log as success or fail
                $exists = isset($failsBuffer[$i]) ? $failsBuffer[$i] : null;
                if ($exists) {
                    $successCount--;
                } elseif ($successCount < $count) {
                    $successCount++;
                }

                //if success rate lower than options value - set gaps data
                $rate = $successCount / $count * 100;
                if ($rate <= $this->options->getPercent() && $exists) {
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
                } elseif ($rate > $this->options->getPercent() && $gapsData) {
                    $reset = true;
                    //yield if gap data not empty and success rate is over the check value
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

    private function checkMemoryLimit()
    {
        $memoryLimit = $this->returnBytes(ini_get('memory_limit')) / 1024 / 1024;
        $memoryUsage = memory_get_usage() / 1024 / 1024;

        $hitLimit = $memoryUsage >= ($memoryLimit * 0.8);

        if ($hitLimit) {
            echo "Memory limit was hit, flush... \nlimit - $memoryLimit mb, usage - $memoryUsage mb\n";
        }
        return $hitLimit;
    }

    /**
     * Converts shorthand memory notation value to bytes
     * From http://php.net/manual/en/function.ini-get.php
     *
     * @param $val Memory size shorthand notation string
     */
    private function returnBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = substr($val, 0, -1);
        switch ($last) {
                // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $val;
    }
}
