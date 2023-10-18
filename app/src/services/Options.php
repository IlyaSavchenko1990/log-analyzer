<?php

namespace app\src\services;

use Garden\Cli\Cli;

class Options
{
    private int $time;
    private float $percent;
    private int|null $scale;

    public function __construct(array|null $argv = null)
    {
        $cli = new Cli();

        $cli->opt('u:u', 'min availability percentage', true)
            ->opt('time:t', 'max response time (ms) - response time higher than value detects as failed', true, 'integer')
            ->opt('scale:s', 'scale - requests count', false);

        $args = $cli->parse($argv);

        $this->time = intval($args->getOpt('time'));
        $this->percent = floatval($args->getOpt('u'));

        $scale = $args->getOpt('scale');
        $this->scale = is_numeric($scale) ? intval($scale) : null;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function getPercent(): float
    {
        return $this->percent;
    }

    public function getScale(): int|null
    {
        return $this->scale;
    }
}
