<?php declare(strict_types=1);

namespace Tests\Metrics;

use Basis\Telemetry\Metrics\Registry;
use PHPUnit\Framework\TestCase;

class RegistryTest extends TestCase
{
    public function testBasic(): void
    {
        $registry = new Registry();
        $registry->set('m1', 5);
        $this->assertSame($registry->get('m1'), 5);

        $registry->increment('m1');
        $this->assertSame($registry->get('m1'), 6);

        $registry->increment('m1', 2);
        $this->assertSame($registry->get('m1'), 8);

        $this->assertNull($registry->get('m2'));

        $registry->increment('m2', 2);
        $this->assertSame($registry->get('m2'), 2);

        $this->assertSame($registry->toArray(), [
            [
                'key' => 'm1',
                'labels' => [],
                'value' => 8,
            ],
            [
                'key' => 'm2',
                'labels' => [],
                'value' => 2,
            ],
        ]);
    }

    public function testLabels(): void
    {
        $registry = new Registry();
        $registry->set('m1', 1, [ 'hostname' => 1 ]);
        $registry->set('m1', 2, [ 'hostname' => 2 ]);
        $registry->set('m2', 3);

        $this->assertSame(1, $registry->get('m1', [ 'hostname' => 1 ]));
        $this->assertSame(2, $registry->get('m1', [ 'hostname' => 2 ]));
        $this->assertNull($registry->get('m1', [ 'hostname' => 3 ]));

        $this->assertSame($registry->toArray(), [
            [
                'key' => 'm1',
                'labels' => [ 'hostname' => 1 ],
                'value' => 1,
            ],
            [
                'key' => 'm1',
                'labels' => [ 'hostname' => 2 ],
                'value' => 2,
            ],
            [
                'key' => 'm2',
                'labels' => [],
                'value' => 3,
            ]
        ]);

        $registry->set('m1', 2, [ 'hostname' => 1 ]);

        $this->assertSame($registry->toArray(), [
            [
                'key' => 'm1',
                'labels' => [ 'hostname' => 1 ],
                'value' => 2,
            ],
            [
                'key' => 'm1',
                'labels' => [ 'hostname' => 2 ],
                'value' => 2,
            ],
            [
                'key' => 'm2',
                'labels' => [],
                'value' => 3,
            ]
        ]);
    }
}
