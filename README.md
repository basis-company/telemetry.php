# Service telemetry for php
[![License](https://poser.pugx.org/basis-company/telemetry/license.png)](https://packagist.org/packages/basis-company/telemetry)
[![Testing](https://github.com/basis-company/telemetry.php/actions/workflows/tests.yml/badge.svg)](https://github.com/basis-company/telemetry.php/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/github/release/basis-company/telemetry.php.svg?style=flat-square)](https://github.com/basis-company/telemetry.php/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/basis-company/telemetry.svg?style=flat-square)](https://packagist.org/packages/basis-company/telemetry)

- [Installation](#installation)
- [Metrics](#metrics)
- [Tracing](#tracing)

## Installation
The recommended way to install the library is through [Composer](http://getcomposer.org):
```bash
$ composer require basis-company/telemetry
```

## Metrics
```php
<?php

use Basis\Telemetry\Metrics\Exporter\PrometheusExporter;
use Basis\Telemetry\Metrics\Importer\PrometheusImporter;
use Basis\Telemetry\Metrics\Info;
use Basis\Telemetry\Metrics\Registry;

// create registry and manipulate metric values
$registry = new Registry();
$registry->increment('request_counter', 1, [ 'user' => 1 ]);
$registry->increment('request_counter', 2, [ 'user' => 2 ]);
$registry->set('memory_usage', memory_get_usage(true));
$registry->set('uptime', 30);

// describe additional info for metrics
$info = new Info();
$info->set('memory_usage', 'Memory usage');
$info->set('request_counter', 'Request Counter', Type::COUNTER);
$info->set('uptime', 'Uptime in seconds', Type::COUNTER);

$exporter = new PrometheusExporter($registry, $info);
// render prometheus metrics with service prefix and optional extra labels
echo $exporter->toString('mailer_', ['env' => 'test']);

// or write result to a file
$exporter->toFile('public/metrics', 'mailer_');

// # HELP mailer_request_counter Request Counter
// # TYPE mailer_request_counter counter
// mailer_request_counter{env="test",user="1"} 1
// mailer_request_counter{env="test",user="2"} 2
// # HELP mailer_memory_usage Memory usage
// # TYPE mailer_memory_usage gauge
// mailer_memory_usage{env="test"} 4194304
// # HELP mailer_uptime Uptime
// # TYPE mailer_uptime counter
// mailer_uptime{env="test"} 30

$remoteRegistry = new Registry();
$remoteInfo = new Info();
$importer = new PrometheusImporter($remoteRegistry, $remoteInfo);

// cut prefix and import registry data and info configuration
$importer->fromFile('public/metrics', 'mailer_');

// prometheus metrics were parsed and prefix removed
echo $remoteRegistry->get('uptime'); // 30
echo $remoteInfo->get('uptime')['help']; // Uptime in seconds
```

## Tracing
```php
<?php

use Basis\Telemetry\Tracing\Builder;
use Basis\Telemetry\Tracing\Exporter\ZipkinExporter;
use Basis\Telemetry\Tracing\SpanContext;
use Basis\Telemetry\Tracing\Transport\ZipkinTransport;
use Symfony\Component\HttpClient\CurlHttpClient;

$spanContext = SpanContext::generate(); // or extract from headers

$tracer = Builder::create()->setSpanContext($spanContext)->getTracer();

// start a span, register some events
$span = $tracer->createSpan('session.generate');

// set attributes as array
$span->setAttributes([ 'remote_ip' => '5.23.99.245' ]);
// set attribute one by one
$span->setAttribute('country', 'Russia');

$span->addEvent('found_login', [
  'id' => 67235,
  'username' => 'nekufa',
]);
$span->addEvent('generated_session', [
  'id' => md5(microtime(true))
]);

$span->end(); // pass status as an optional argument

// add additional endpoint information
$exporter = new ZipkinExporter([ 'serviceName' => 'tester' ]);

// configure transport using symfony http client instance
$client = new CurlHttpClient();
$transport = new ZipkinTransport($client, 'zipkin-hostname'); // set zipkin hostname, override port or url

$exporter->flush($tracer, $transport);

```

