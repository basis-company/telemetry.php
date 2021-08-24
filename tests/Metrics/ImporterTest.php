<?php

declare(strict_types=1);

namespace Tests\Metrics;

use Basis\Telemetry\Metrics\Exporter;
use Basis\Telemetry\Metrics\Exporter\PrometheusExporter;
use Basis\Telemetry\Metrics\Importer\PrometheusImporter;
use Basis\Telemetry\Metrics\Info;
use Basis\Telemetry\Metrics\Registry;
use Basis\Telemetry\Metrics\Type;
use DomainException;
use PHPUnit\Framework\TestCase;

class ImporterTest extends TestCase
{
    public function testPrometheus(): void
    {
        $registry = new Registry();
        $registry->set('memory_usage', memory_get_usage(true));
        $registry->set('request_counter', 1, ['user' => '1']);
        $registry->set('request_counter', 2, ['user' => '2']);
        $registry->set('uptime', 30);

        $info = new Info();
        $info->set('memory_usage', 'Memory usage');
        $info->set('request_counter', 'Request Counter', Type::COUNTER);
        $info->set('uptime', 'Uptime', Type::COUNTER);

        $exporter = new PrometheusExporter($registry, $info);

        $registry2 = new Registry();
        $info2 = new Info();
        $importer = new PrometheusImporter($registry2, $info2);
        $filename = '/tmp/' . bin2hex(random_bytes(32));

        $exporter->toFile($filename, 'tester_');
        $importer->fromFile($filename, 'tester_');

        unlink($filename);

        $keys = [
            ['memory_usage', []],
            ['request_counter', ['user' => '1']],
            ['request_counter', ['user' => '2']],
            ['uptime'],
        ];

        foreach ($keys as $key) {
            $this->assertSame($registry->get(...$key), $registry2->get(...$key));
            $this->assertSame($info->get($key[0]), $info2->get($key[0]));
        }
    }
}
