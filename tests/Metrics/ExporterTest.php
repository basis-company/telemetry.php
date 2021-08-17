<?php

declare(strict_types=1);

namespace Tests\Metrics;

use Basis\Telemetry\Metrics\Exporter;
use Basis\Telemetry\Metrics\Exporter\PrometheusExporter;
use Basis\Telemetry\Metrics\Info;
use Basis\Telemetry\Metrics\Registry;
use Basis\Telemetry\Metrics\Type;
use DomainException;
use PHPUnit\Framework\TestCase;

class ExporterTest extends TestCase
{
    public function testExporter(): void
    {
        $registry = new Registry();
        $registry->set('memory_usage', memory_get_usage(true));
        $registry->set('request_counter', 1, [ 'user' => 1 ]);
        $registry->set('request_counter', 2, [ 'user' => 2 ]);
        $registry->set('uptime', 30);

        $info = new Info();
        $info->set('memory_usage', 'Memory usage');
        $info->set('request_counter', 'Request Counter', Type::COUNTER);
        $info->set('uptime', 'Uptime', Type::COUNTER);

        $exporter = new Exporter($registry, $info);
        $array = $exporter->toArray(['env' => 'test']);
        $this->assertCount(4, $array);

        $map = [];
        foreach ($array as $row) {
            $index = $row['key'];
            if (array_key_exists('user', $row['labels'])) {
                $index .= $row['labels']['user'];
            }
            $map[$index] = $row;
        }

        $this->assertArrayHasKey('memory_usage', $map);
        $this->assertArrayHasKey('request_counter1', $map);
        $this->assertArrayHasKey('request_counter2', $map);
        $this->assertArrayHasKey('uptime', $map);

        $this->assertSame($map['memory_usage']['help'], 'Memory usage');
        $this->assertSame($map['memory_usage']['key'], 'memory_usage');
        $this->assertSame($map['memory_usage']['type'], Type::GAUGE);
        $this->assertSame($map['memory_usage']['labels'], [ 'env' => 'test' ]);

        $this->assertSame($map['request_counter1']['help'], 'Request Counter');
        $this->assertSame($map['request_counter1']['key'], 'request_counter');
        $this->assertSame($map['request_counter1']['type'], Type::COUNTER);
        $this->assertSame($map['request_counter1']['value'], 1);
        $this->assertSame($map['request_counter1']['labels'], [ 'env' => 'test', 'user' => 1 ]);

        $this->assertSame($map['request_counter2']['help'], 'Request Counter');
        $this->assertSame($map['request_counter2']['key'], 'request_counter');
        $this->assertSame($map['request_counter2']['type'], Type::COUNTER);
        $this->assertSame($map['request_counter2']['value'], 2);
        $this->assertSame($map['request_counter2']['labels'], [ 'env' => 'test', 'user' => 2 ]);

        $this->assertSame($map['uptime']['help'], 'Uptime');
        $this->assertSame($map['uptime']['key'], 'uptime');
        $this->assertSame($map['uptime']['type'], Type::COUNTER);
        $this->assertSame($map['uptime']['value'], 30);
        $this->assertSame($map['uptime']['labels'], [ 'env' => 'test' ]);

        $exporter = new PrometheusExporter($registry, $info);
        $result = $exporter->toString('svc_');

        $this->assertStringContainsString('HELP svc_memory_usage Memory usage', $result);
        $this->assertStringContainsString('HELP svc_request_counter Request Counter', $result);
        $this->assertStringContainsString('HELP svc_uptime Uptime', $result);
        $this->assertStringContainsString('TYPE svc_memory_usage gauge', $result);
        $this->assertStringContainsString('TYPE svc_request_counter counter', $result);
        $this->assertStringContainsString('TYPE svc_uptime counter', $result);

        $this->assertCount(2, explode('HELP svc_request_counter', $result));
    }
    public function testExtraLabels()
    {
        $registry = new Registry();
        $registry->set('request_counter', 27);
        $info = new Info();
        $info->set('request_counter', 'Request Counter');

        $exporter = new PrometheusExporter($registry, $info);
        $result = $exporter->toString('svc_', [ 'hostname' => 'tester' ]);

        $this->assertStringContainsString('svc_request_counter{hostname="tester"} 27', $result);
    }

    public function testInfoMissmatch()
    {
        $registry = new Registry();
        $registry->set('counter', 1);
        $info = new Info();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("No Info for counter");

        $exporter = new Exporter($registry, $info);
        $exporter->toArray();
    }
}
