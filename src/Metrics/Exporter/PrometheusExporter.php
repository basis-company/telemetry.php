<?php

declare(strict_types=1);

namespace Basis\Telemetry\Metrics\Exporter;

use Basis\Telemetry\Metrics\Exporter;
use Basis\Telemetry\Metrics\Info;
use Basis\Telemetry\Metrics\Registry;

class PrometheusExporter extends Exporter
{
    private int $decimals = 3;

    public function setDecimals(int $decimals): self
    {
        $this->decimals = $decimals;
        return $this;
    }

    public function toFile(string $path, string $prefix = '', array $labels = []): void
    {
        file_put_contents($path, $this->toString($prefix, $labels));
    }

    public function toString(string $prefix = '', array $labels = []): string
    {
        $result = [];

        foreach ($this->toArray($labels) as $row) {
            $key = $prefix . $row['key'];

            if (!array_key_exists($key . '!h', $result)) {
                $result[$key . '!h'] = sprintf('# HELP %s %s', $key, $row['help']);
                $result[$key . '!t'] = sprintf('# TYPE %s %s', $key, $row['type']);
            }

            $rowLabels = [];
            foreach ($row['labels'] as $k => $v) {
                $rowLabels[] = sprintf('%s="%s"', $k, $v);
            }
            if (count($rowLabels)) {
                $key .= '{' . implode(',', $rowLabels) . '}';
            }

            $value = $row['value'];
            if (is_float($value)) {
                $value = number_format($value, $this->decimals);
            }

            $result[$key . '_'] = sprintf('%s %s', $key, $value);
        }

        ksort($result);

        return implode(PHP_EOL, $result);
    }
}
