<?php

declare(strict_types=1);

namespace Basis\Telemetry\Tracing\Transport;

use Basis\Telemetry\Tracing\Span;
use Basis\Telemetry\Tracing\Tracer;
use Basis\Telemetry\Tracing\Transport;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ZipkinTransport implements Transport
{
    public function __construct(
        private HttpClientInterface $client,
        private string $hostname,
        private int $port = 9411,
        private string $path = '/api/v2/spans',
        private string $schema = 'http'
    ) {
    }

    public function getUrl(): string
    {
        return sprintf('%s://%s:%s%s', $this->schema, $this->hostname, $this->port, $this->path);
    }

    public function write(array $data): bool
    {
        $response = $this->client->request('POST', $this->getUrl(), [
            'body' => json_encode($data),
        ]);

        return $response->getStatusCode() == 200;
    }
}
