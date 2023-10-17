<?php

namespace app\src;

use Garden\Cli\Cli;

class Options
{
    private int $time;
    private float $percent;
    private int $scale;

    public function __construct(array|null $argv = null)
    {
        $cli = new Cli();

        $cli->opt('u:u', 'min availability percentage', true)
            ->opt('time:t', 'max response time (sec) - response time higher than value detects as failed', true, 'integer')
            ->opt('scale:s', 'scale - requests count', true);

        $args = $cli->parse($argv);

        $this->time = intval($args->getOpt('time'));
        $this->percent = floatval($args->getOpt('u'));
        $this->scale = intval($args->getOpt('scale'));
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function getPercent(): float
    {
        return $this->percent;
    }

    public function getScale(): int
    {
        return $this->scale;
    }
}
