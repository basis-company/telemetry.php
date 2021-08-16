<?php

declare(strict_types=1);

namespace Basis\Telemetry\Tracing\Exporter;

use Basis\Telemetry\Tracing\Exporter;
use Basis\Telemetry\Tracing\Span;
use Basis\Telemetry\Tracing\Tracer;

class ZipkinExporter extends Exporter
{
    public function __construct(private array $endpoint = [])
    {
    }

    public function convertSpan(Span $span): array
    {
        return [
            'id' => $span->getSpanContext()->getSpanId(),
            'traceId' => $span->getSpanContext()->getTraceId(),
            'parentId' => $span->getParentSpanContext() ? $span->getParentSpanContext()->getSpanId() : null,
            'localEndpoint' => $this->endpoint,
            'name' => $span->getName(),
            'timestamp' => (int) round($span->getStart() * 1000000),
            'duration' => (int) round($span->getEnd() * 1000000) - round($span->getStart() * 1000000),
            'annotations' => $this->convertEvents($span->getEvents()),
            'tags' => $this->convertAttributes($span->getAttributes()),
        ];
    }

    private function convertEvents(array $events): array
    {
        $annotations = [];

        foreach ($events as $event) {
            $annotations[] = [
                'timestamp' => round($event->getTimestamp() * 1000000),
                'value' => $event->getName(),
            ];
        }

        return $annotations;
    }

    private function convertAttributes(array $attributes): array
    {
        $tags = [];

        foreach ($attributes as $k => $v) {
            if (is_bool($v)) {
                $v = (string) $v;
            }
            $tags[$k] = $v;
        }

        return $tags;
    }
}
