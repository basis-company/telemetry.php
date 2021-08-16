<?php

declare(strict_types=1);

namespace Basis\Telemetry\Tracing;

abstract class Exporter
{
    abstract public function convertSpan(Span $span): array;

    public function flush(Tracer $tracer, Transport $transport): int
    {
        $data = [];

        foreach ($tracer->getSpans() as $span) {
            $data[] = $this->convertSpan($span);
        }

        if (count($data)) {
            $transport->write($data);
        }

        return count($data);
    }
}
