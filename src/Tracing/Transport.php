<?php

declare(strict_types=1);

namespace Basis\Telemetry\Tracing;

interface Transport
{
    public function write(array $data): bool;
}
