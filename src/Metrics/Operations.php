<?php

declare(strict_types=1);

namespace Basis\Telemetry\Metrics;

class Operations
{
    private array $data = [];
    private array $labels = [];

    public static function create(array $labels = []): self
    {
        return new self($labels);
    }

    public function __construct(array $labels = [])
    {
        $this->labels = $labels;
    }

    public function increment(string $key, float | int $amount = 1, array $labels = []): self
    {
        $this->data[] = [ 'increment', $key, $amount, $labels ];
        return $this;
    }

    public function set(string $key, float | int $value, array $labels = []): self
    {
        $this->data[] = [ 'set', $key, $value, $labels ];
        return $this;
    }

    public function apply(Registry $registry): self
    {
        foreach ($this->data as [ $method, $key, $value, $labels ]) {
            $registry->$method($key, $value, array_merge($this->labels, $labels));
        }
        return $this;
    }
}
