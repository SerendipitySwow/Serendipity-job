<?php
declare(strict_types = 1);

namespace Serendipity\Job\Contract;

interface ConfigInterface
{
    public function get(string $key, mixed $default = null);

    public function set(string $key, mixed $value = null);
}
