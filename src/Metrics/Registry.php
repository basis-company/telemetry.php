<?php

declare(strict_types=1);

namespace Basis\Telemetry\Metrics;

class Registry
{
    private array $data = [];

    public function exists(string $key, array $labels = []): bool
    {
        $index = json_encode([ $key, $labels ]);

        return array_key_exists($index, $this->data);
    }

    public function get(string $key, array $labels = []): mixed
    {
        $index = json_encode([ $key, $labels ]);

        return array_key_exists($index, $this->data) ? $this->data[$index]['value'] : null;
    }

    public function increment(string $key, float | int $amount = 1, array $labels = []): mixed
    {
        $index = json_encode([ $key, $labels ]);

        if ($this->exists($key, $labels)) {
            $this->data[$index]['value'] += $amount;
        } else {
            $this->set($key, $amount, $labels);
        }

        return $this;
    }

    public function set(string $key, float | int $value, array $labels = []): self
    {
        $index = json_encode([ $key, $labels ]);

        $this->data[$index] = compact('key', 'labels', 'value');

        return $this;
    }

    public function toArray(): array
    {
        return array_values($this->data);
    }
}
