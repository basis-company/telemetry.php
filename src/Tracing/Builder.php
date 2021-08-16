<?php

declare(strict_types=1);

namespace Basis\Telemetry\Tracing;

class Builder
{
    private SpanContext $spanContext;

    public static function create(): self
    {
        return new self();
    }

    public function setSpanContext(SpanContext $spanContext): self
    {
        $this->spanContext = $spanContext;
        return $this;
    }

    public function getTracer(): Tracer
    {
        return new Tracer($this->spanContext);
    }
}
