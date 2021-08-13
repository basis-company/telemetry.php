<?php declare(strict_types=1);

namespace Basis\Telemetry\Metrics\Exporter;

use Basis\Telemetry\Metrics\Info;
use Basis\Telemetry\Metrics\Registry;

class Prometheus extends Exporter
{
    public function toString(string $prefix = ''): string
    {
        $info = [];
        $result = [];

        foreach ($this->toArray() as $row) {
            $key = $prefix . $row['key'];

            if (!array_key_exists($key, $info)) {
                $result[] = sprintf('# HELP %s %s', $key, $row['help']);
                $result[] = sprintf('# TYPE %s %s', $key, $row['type']);
                $info[$key] = true;
            }

            $labels = [];
            foreach ($row['labels'] as $k => $v) {
                $labels[] = sprintf('%s="%s"', $k, $v);
            }
            if (count($labels)) {
                $key .= '{' . implode(',', $labels) . '}';
            }

            $result[] = sprintf('%s %s', $key, $row['value']);
        }

        return implode(PHP_EOL, $result);
    }
}
