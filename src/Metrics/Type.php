<?php

declare(strict_types=1);

namespace Basis\Telemetry\Metrics;

class Type
{
    public const COUNTER = 'counter';
    public const GAUGE = 'gauge';

    public static function isValid(string $nick): bool
    {
        $valid = [ self::COUNTER, self::GAUGE ];

        return in_array($nick, $valid);
    }
}
