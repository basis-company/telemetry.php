<?php

declare(strict_types=1);

namespace Tests\Metrics;

use Basis\Telemetry\Metrics\Info;
use Basis\Telemetry\Metrics\Type;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InfoTest extends TestCase
{
    public function test(): void
    {
        $info = new Info();

        $info->set('uptime', 'Uptime in seconds', Type::COUNTER);

        $this->assertTrue($info->exists('uptime'));

        $description = $info->get('uptime');
        $this->assertNotNull($description);
        $this->assertSame($description['key'], 'uptime');
        $this->assertSame($description['help'], 'Uptime in seconds');
        $this->assertSame($description['type'], Type::COUNTER);
    }

    public function testInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key tester');

        $info = new Info();
        $this->assertFalse($info->exists('tester'));

        $info->get('tester');
    }

    public function testDefaultType()
    {
        $info = new Info();
        $info->set('lag', 'Request lag');

        $description = $info->get('lag');

        $this->assertNotNull($description);
        $this->assertSame($description['key'], 'lag');
        $this->assertSame($description['type'], Type::GAUGE);
    }

    public function testTypeValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type scale');

        $info = new Info();
        $info->set('weight', 'Weight', 'scale');
    }
}
