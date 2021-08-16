<?php

declare(strict_types=1);

namespace Basis\Telemetry\Tracing\Exporter;

use Basis\Telemetry\Tracing\Exporter;
use Basis\Telemetry\Tracing\Span;
use Basis\Telemetry\Tracing\Tracer;

class Zipkin extends Exporter
{
    public function __construct(private array $endpoint = [])
    {
    }

    public function convertSpan(Span $span): array
    {
        $row = [
            'id' => $span->getSpanContext()->getSpanId(),
            'traceId' => $span->getSpanContext()->getTraceId(),
            'parentId' => $span->getParentSpanContext() ? $span->getParentSpanContext()->getSpanId() : null,
            'localEndpoint' => $this->endpoint,
            'name' => $span->getName(),
            'timestamp' => (int) round($span->getStart() * 1000000),
            'duration' => (int) round($span->getEnd() * 1000000) - round($span->getStart() * 1000000),
        ];

        foreach ($span->getAttributes() as $k => $v) {
            if (!array_key_exists('tags', $row)) {
                $row['tags'] = [];
            }
            if (is_bool($v)) {
                $v = (string) $v;
            }
            $row['tags'][$k] = $v;
        }

        foreach ($span->getEvents() as $event) {
            if (!array_key_exists('annotations', $row)) {
                $row['annotations'] = [];
            }
            $row['annotations'][] = [
                'timestamp' => round($event->getTimestamp() * 1000000),
                'value' => $event->getName(),
            ];
        }

        return $row;
    }
}
