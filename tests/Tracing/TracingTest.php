<?php

declare(strict_types=1);

namespace Tests\Tracing;

use Basis\Telemetry\Tracing\Builder;
use Basis\Telemetry\Tracing\Exporter;
use Basis\Telemetry\Tracing\SpanContext;
use Basis\Telemetry\Tracing\Status;
use Basis\Telemetry\Tracing\Tracer;
use PHPUnit\Framework\TestCase;

class TracingTest extends TestCase
{
    public function testReset()
    {
        $tracer = new Tracer();
        $this->assertCount(1, $tracer->getSpans());
        $active = $tracer->getActiveSpan();

        $tracer->createSpan('a');
        $tracer->createSpan('b');
        $this->assertCount(3, $tracer->getSpans());

        $tracer->reset();
        $this->assertCount(1, $tracer->getSpans());

        $this->assertNotSame(
            $active->getSpanContext()->getSpanId(),
            $tracer->getActiveSpan()->getSpanContext()->getSpanId(),
        );
    }

    public function testContextGenerationAndRestore()
    {
        $spanContext = SpanContext::generate();
        $this->assertSame(strlen($spanContext->getTraceId()), 32);
        $this->assertSame(strlen($spanContext->getSpanId()), 16);

        $spanContext2 = SpanContext::generate();
        $this->assertNotSame($spanContext->getTraceId(), $spanContext2->getTraceId());
        $this->assertNotSame($spanContext->getSpanId(), $spanContext2->getSpanId());

        $spanContext3 = SpanContext::restore($spanContext->getTraceId(), $spanContext->getSpanId());
        $this->assertSame($spanContext3->getTraceId(), $spanContext->getTraceId());
        $this->assertSame($spanContext3->getSpanId(), $spanContext->getSpanId());
    }

    public function testTracerSpanContextRestore()
    {
        $tracer = new Tracer();
        $spanContext = $tracer->getActiveSpan()->getSpanContext();

        $spanContext2 = SpanContext::restore($spanContext->getTraceId(), $spanContext->getSpanId());
        $tracer2 = new Tracer($spanContext2);

        $this->assertSame($tracer->getActiveSpan()->getSpanContext()->getTraceId(), $tracer2->getActiveSpan()->getSpanContext()->getTraceId());
    }

    public function testSpanNameUpdate()
    {
        $database = (new Tracer())->createSpan('database');
        $this->assertSame($database->getName(), 'database');
        $database->setName('tarantool');
        $this->assertSame($database->getName(), 'tarantool');
    }

    public function testNestedSpans()
    {
        $tracer = new Tracer();

        $guard = $tracer->createSpan('guard.validate');
        $connection = $tracer->createSpan('guard.database.connection');
        $procedure = $tracer->createSpan('guard.procedure.registration')->end();
        $connection->end();
        $policy = $tracer->createSpan('policy.describe')->end();

        $guard->end();

        $this->assertSame($connection->getParentSpanContext(), $guard->getSpanContext());
        $this->assertSame($procedure->getParentSpanContext(), $connection->getSpanContext());
        $this->assertSame($policy->getParentSpanContext(), $guard->getSpanContext());

        $this->assertCount(5, $tracer->getSpans());
    }

    public function testCreateSpan()
    {
        $tracer = new Tracer();
        $global = $tracer->getActiveSpan();

        $mysql = $tracer->createSpan('mysql');
        $this->assertSame($tracer->getActiveSpan(), $mysql);
        $this->assertSame($global->getSpanContext()->getTraceId(), $mysql->getSpanContext()->getTraceId());
        $this->assertSame($mysql->getParentSpanContext(), $global->getSpanContext());
        $this->assertNotNull($mysql->getStart());
        $this->assertTrue($mysql->isRecordingEvents());
        $this->assertNull($mysql->getDuration());

        $mysql->end();
        $this->assertFalse($mysql->isRecordingEvents());
        $this->assertNotNull($mysql->getDuration());

        $duration = $mysql->getDuration();
        $this->assertSame($duration, $mysql->getDuration());
        $mysql->end();
        $this->assertGreaterThan($duration, $mysql->getDuration());

        $this->assertTrue($mysql->getStatus()->isOk());

        // active span rolled back
        $this->assertSame($tracer->getActiveSpan(), $global);
        $this->assertNull($global->getStatus());

        // active span should be kept for global span
        $global->end();
        $this->assertSame($tracer->getActiveSpan(), $global);
        $this->assertTrue($global->getStatus()->isOk());
    }

    public function testStatusManipulation()
    {
        $tracer = new Tracer();

        $cancelled = $tracer->createSpan('cancelled');
        $cancelled->end(new Status(Status::CANCELLED));
        $this->assertFalse($cancelled->getStatus()->isOk());
        $this->assertSame($cancelled->getStatus()->getCanonicalCode(), Status::CANCELLED);
        $this->assertSame($cancelled->getStatus()->getDescription(), Status::DESCRIPTION[Status::CANCELLED]);

        $custom = $tracer->createSpan('custom');
        $custom->end(new Status(404, 'Not found'));
        $this->assertFalse($custom->getStatus()->isOk());
        $this->assertSame($custom->getStatus()->getCanonicalCode(), 404);
        $this->assertSame($custom->getStatus()->getDescription(), 'Not found');

        $noDescription = new Status(500);
        $this->assertNull($noDescription->getDescription());

        $custom->setStatus(new Status(Status::OK));
        $this->assertTrue($custom->getStatus()->isOk());

        $this->assertCount(3, $tracer->getSpans());
    }

    public function testSpanAttributesApi()
    {
        $span = (new Tracer())->getActiveSpan();

        // set attributes
        $span->setAttributes([ 'username' => 'nekufa' ]);

        // get attribute
        $this->assertSame($span->getAttribute('username'), 'nekufa');

        // otherwrite
        $span->setAttributes([ 'email' => 'nekufa@gmail.com', ]);

        // null attributes
        $this->assertNull($span->getAttribute('username'));
        $this->assertSame($span->getAttribute('email'), 'nekufa@gmail.com');

        // set attribute
        $span->setAttribute('username', 'nekufa');
        $this->assertSame($span->getAttribute('username'), 'nekufa');
        $this->assertSame($span->getAttributes(), [
            'email' => 'nekufa@gmail.com',
            'username' => 'nekufa',
        ]);

        // keep order
        $span->setAttributes([ 'a' => 1, 'b' => 2]);
        $this->assertSame(array_keys($span->getAttributes()), ['a', 'b']);
        $span->setAttributes([ 'b' => 2, 'a' => 1, ]);
        $this->assertSame(array_keys($span->getAttributes()), ['b', 'a']);

        // attribute update don't change the order
        $span->setAttribute('a', 3);
        $span->setAttribute('b', 4);
        $this->assertSame(array_keys($span->getAttributes()), ['b', 'a']);

        $this->expectExceptionMessage("Span is readonly");
        $span->end();
        $span->setAttribute('b', 5);
    }

    public function testEventRegistration()
    {
        $span = (new Tracer())->createSpan('database');
        $event = $span->addEvent('select', [
            'space' => 'guard.session',
            'id' => 67235
        ]);
        $this->assertSame($event->getName(), 'select');
        $this->assertSame($event->getAttributes(), [
            'space' => 'guard.session',
            'id' => 67235,
        ]);
        $this->assertSame($event->getAttribute('space'), 'guard.session');
        $this->assertNull($event->getAttribute('invalid-attribute'));
        $this->assertCount(1, $span->getEvents());
        $this->assertSame($span->getEvents(), [$event]);

        $span->addEvent('update')
            ->setAttribute('space', 'guard.session')
            ->setAttribute('id', 67235)
            ->setAttribute('active_at', time());

        $this->assertCount(2, $span->getEvents());

        $this->expectExceptionMessage("Span is readonly");
        $span->end();
        $span->addEvent('update');
    }

    public function testBuilder()
    {
        $spanContext = SpanContext::generate();
        $tracer = Builder::create()
            ->setSpanContext($spanContext)
            ->getTracer();

        $this->assertInstanceOf(Tracer::class, $tracer);
        $this->assertSame($tracer->getActiveSpan()->getSpanContext(), $spanContext);
    }

    public function testParentSpanContext()
    {
        $tracer = new Tracer();
        $global = $tracer->getActiveSpan();
        $request = $tracer->createSpan('request');
        $this->assertSame($request->getParentSpanContext()->getSpanId(), $global->getSpanContext()->getSpanId());
        $this->assertNull($global->getParentSpanContext());
        $this->assertNotNull($request->getParentSpanContext());
    }

    public function testSerialization()
    {
        $tracer = new Tracer();
        $span = $tracer->createSpan('serializable');
        $span->setAttribute('attribute', 'value');
        $span->addEvent('greet', [ 'name' => 'nekufa' ]);

        $serialized = serialize($span);
        $unserialized = unserialize($serialized);

        $this->assertSame($span->getName(), $unserialized->getName());
        $this->assertSame($span->getStart(), $unserialized->getStart());
        $this->assertSame($span->getEnd(), $unserialized->getEnd());

        $this->assertSame($unserialized->getAttribute('attribute'), 'value');
        $this->assertCount(1, $unserialized->getEvents());
        [$event] = $unserialized->getEvents();
        $this->assertSame($event->getName(), 'greet');
        $this->assertSame($event->getAttribute('name'), 'nekufa');

        return $tracer;
    }
}
