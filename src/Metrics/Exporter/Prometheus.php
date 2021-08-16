<?php

declare(strict_types=1);

namespace Basis\Telemetry\Metrics\Exporter;

use Basis\Telemetry\Metrics\Exporter;
use Basis\Telemetry\Metrics\Info;
use Basis\Telemetry\Metrics\Registry;

class Prometheus extends Exporter
{
    public function toString(string $prefix = '', array $labels = []): string
    {
        $info = [];
        $result = [];

        foreach ($this->toArray($labels) as $row) {
            $key = $prefix . $row['key'];

            if (!array_key_exists($key, $info)) {
                $result[] = sprintf('# HELP %s %s', $key, $row['help']);
                $result[] = sprintf('# TYPE %s %s', $key, $row['type']);
                $info[$key] = true;
            }

            $rowLabels = [];
            foreach ($row['labels'] as $k => $v) {
                $rowLabels[] = sprintf('%s="%s"', $k, $v);
            }
            if (count($rowLabels)) {
                $key .= '{' . implode(',', $rowLabels) . '}';
            }

            $result[] = sprintf('%s %s', $key, $row['value']);
        }

        return implode(PHP_EOL, $result);
    }
}
