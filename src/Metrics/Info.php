<?php

declare(strict_types=1);

namespace Basis\Telemetry\Metrics;

use InvalidArgumentException;

class Info
{
    private $data = [];

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key): array
    {
        if (!$this->exists($key)) {
            throw new InvalidArgumentException("Invalid key $key");
        }

        return $this->data[$key];
    }

    public function set(string $key, string $help, string $type = Type::GAUGE): self
    {
        if (!Type::isValid($type)) {
            throw new InvalidArgumentException("Invalid type $type");
        }
        $this->data[$key] = compact('key', 'help', 'type');

        return $this;
    }
}
