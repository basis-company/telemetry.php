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

    public function update(string $key, string $type, string $value): self
    {
        if (!in_array($type, ['help', 'type'])) {
            throw new InvalidArgumentException("Invalid update call");
        }

        if (!array_key_exists($key, $this->data)) {
            if ($type == 'help') {
                // initialize info with default metric type
                return $this->set($key, $value);
            } else {
                // disable initialize with empty help
                throw new InvalidArgumentException("Invalid key $key");
            }
        }

        if ($type == 'type' && !Type::isValid($value)) {
            throw new InvalidArgumentException("Invalid type $value");
        }

        $this->data[$key][$type] = $value;

        return $this;
    }
}
