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
        $info = [];
        $values = [];

        foreach (explode(PHP_EOL, $string) as $line) {
            if (substr($line, 0, 1) == '#') {
                [$_, $type, $nick, $value] = explode(' ', $line, 4);
                if ($prefix && strpos($nick, $prefix) === 0) {
                    $nick = substr($nick, strlen($prefix));
                }
                if (!array_key_exists($nick, $info)) {
                    $info[$nick] = compact('nick');
                }
                $info[$nick][strtolower($type)] = $value;
            } else {
                $labels = [];
                [$nick, $value] = explode(' ', $line);
                if ($prefix && strpos($nick, $prefix) === 0) {
                    $nick = substr($nick, strlen($prefix));
                }
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
                $values[] = compact('nick', 'labels', 'value');
            }
        }

        foreach ($info as $row) {
            $this->info->set($row['nick'], $row['help'], $row['type']);
        }

        foreach ($values as $row) {
            $this->registry->set($row['nick'], $row['value'], $row['labels']);
        }

        return $this;
    }
}
