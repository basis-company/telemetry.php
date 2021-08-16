<?php

declare(strict_types=1);

namespace Basis\Telemetry\Tracing;

class Event
{
    private string $name;
    private float $timestamp;
    private array $attributes = [];

    public function __construct(string $name, array $attributes = [], float $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = microtime(true);
        }
        $this->name = $name;
        $this->timestamp = $timestamp;
        $this->setAttributes($attributes);
    }

    public function getAttribute(string $key)
    {
        if (!array_key_exists($key, $this->attributes)) {
            return null;
        }
        return $this->attributes[$key];
    }

    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): self
    {
        $this->attributes = [];
        foreach ($attributes as $k => $v) {
            $this->setAttribute($k, $v);
        }
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTimestamp(): float
    {
        return $this->timestamp;
    }
}
