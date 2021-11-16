<?php

declare(strict_types=1);

namespace Basis\Telemetry\Metrics\Importer;

use Basis\Telemetry\Metrics\Info;
use Basis\Telemetry\Metrics\Registry;

class PrometheusImporter
{
    public function __construct(private Registry $registry, private Info $info)
    {
    }
    public function fromFile(string $path, string $prefix = ''): self
    {
        return $this->fromString(file_get_contents($path), $prefix);
    }

    public function fromString(string $string, string $prefix = ''): self
    {
        foreach (explode(PHP_EOL, $string) as $line) {
            if (substr($line, 0, 1) == '#') {
                $this->parseInfoLine($line, $prefix);
            } else {
                $this->parseDataLine($line, $prefix);
            }
        }

        return $this;
    }

    private function parseInfoLine(string $line, string $prefix)
    {
        $parts = explode(' ', $line, 4);
        if (count($parts) < 4) {
            return null;
        }

        [$_, $type, $nick, $value] = $parts;
        if ($prefix && strpos($nick, $prefix) === 0) {
            $nick = substr($nick, strlen($prefix));
        }

        $type = strtolower($type);

        $this->info->update($nick, $type, $value);
    }

    private function parseDataLine(string $line, string $prefix)
    {
        if (strpos($line, ' ') === false) {
            return;
        }

        [$nick, $value] = explode(' ', $line);

        if ($prefix && strpos($nick, $prefix) === 0) {
            $nick = substr($nick, strlen($prefix));
        }

        $labels = [];
        if (strpos($nick, '{') !== false) {
            [$nick, $postfix] = explode('{', trim($nick, '}'), 2);
            foreach (explode(',', $postfix) as $chunk) {
                [$k, $v] = explode('=', $chunk, 2);
                $labels[$k] = trim($v, '"');
            }
        }

        if ("" . intval($value) == $value) {
            $value = intval($value);
        } else {
            $value = floatval($value);
        }

        $this->registry->set($nick, $value, $labels);
    }
}
