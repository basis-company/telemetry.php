<?php declare(strict_types=1);

namespace Tests\Tracing;

use Basis\Telemetry\Tracing\Exporter\ZipkinExporter;
use Basis\Telemetry\Tracing\Tracer;
use Basis\Telemetry\Tracing\Transport\ZipkinTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class TransportTest extends TestCase
{
    public function testZipkin()
    {
        $tracer = new Tracer();
        $span = $tracer->getActiveSpan();
        $event = $span->addEvent('hello world');
        $span->setAttribute('nick', 'nekufa');
        $span->end();

        $client = new MockHttpClient([
            function ($method, $url, $options) use ($span, $event) {
                $this->assertSame($url, 'http://zipkin-hostname:9411/api/v2/spans');
                $this->assertNotNull($options['body']);
                $data = json_decode($options['body']);
                $this->assertCount(1, $data);
                [ $row ] = $data;
                $this->assertSame($row->id, $span->getSpanContext()->getSpanId());
                $this->assertSame($row->traceId, $span->getSpanContext()->getTraceId());
                $this->assertSame($row->parentId, null);
                $this->assertSame($row->name, $span->getName());
                $this->assertSame($row->timestamp, (int) round($span->getStart() * 1000000));

                $duration = round($span->getEnd() * 1000000) - round($span->getStart() * 1000000);
                $this->assertSame($row->duration, (int) $duration);

                $this->assertEquals($row->localEndpoint, (object) [ 'serviceName' => 'tester' ]);
                $this->assertCount(1, get_object_vars($row->tags));
                $this->assertSame($row->tags->nick, 'nekufa');
                $this->assertCount(1, $row->annotations);

                [ $annotation ] = $row->annotations;
                $this->assertSame($annotation->value, 'hello world');
                $this->assertSame($annotation->timestamp, (int) round(1000000 * $event->getTimestamp()));

                return new MockResponse('OK');
            }
        ]);

        $exporter = new ZipkinExporter([ 'serviceName' => 'tester' ]);
        $transport = new ZipkinTransport($client, 'zipkin-hostname');

        $exporter->flush($tracer, $transport);

        $this->assertSame(1, $client->getRequestsCount());
    }
}
