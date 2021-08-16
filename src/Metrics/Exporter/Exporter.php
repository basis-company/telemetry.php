<?php declare(strict_types=1);

namespace Basis\Telemetry\Metrics\Exporter;

use Basis\Telemetry\Metrics\Info;
use Basis\Telemetry\Metrics\Registry;
use DomainException;

class Exporter
{
    public function __construct(protected Registry $registry, protected Info $info)
    {
    }

    public function toArray(array $labels = []): array
    {
        $result = [];

        foreach ($this->registry->toArray() as $row) {
            if (!$this->info->exists($row['key'])) {
                throw new DomainException("No Info for " . $row['key']);
            }
            $info = $this->info->get($row['key']);
            $row['labels'] = array_merge($labels, $row['labels']);
            $result[] = array_merge($row, $info);
        }

        return $result;
    }
}
