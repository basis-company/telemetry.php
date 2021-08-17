<?php declare(strict_types=1);

namespace Tests\Metrics;

use Basis\Telemetry\Metrics\Operations;
use Basis\Telemetry\Metrics\Registry;
use PHPUnit\Framework\TestCase;

class OperationsTest extends TestCase
{
    public function testBasics(): void
    {
        $registry = new Registry();

        $operations = Operations::create([ 'hostname' => 1 ])
            ->increment('request_counter')
            ->increment('request_memory', memory_get_peak_usage(true))
            ->increment('request_timing', 42)
            ->increment('processing_counter', 1, [ 'queue' => 'tester', ])
            ->increment('todo_counter', -1, [ 'queue' => 'tester', ])
            ->set('activity_timestamp', time());

        $this->assertSame(6, $operations->count());

        $operations->apply($registry);

        $this->assertSame(1, $registry->get('request_counter', [ 'hostname' => 1 ]));
        $this->assertSame(-1, $registry->get('todo_counter', [ 'hostname' => 1, 'queue' => 'tester' ]));

        // operations should be serializable
        unserialize(serialize($operations))->apply($registry);

        $this->assertSame(2, $registry->get('request_counter', [ 'hostname' => 1 ]));
        $this->assertSame(42 * 2, $registry->get('request_timing', [ 'hostname' => 1 ]));

        $operations->reset();
        $this->assertSame(0, $operations->count());
    }
}
