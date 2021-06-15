<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Contract;

interface ConfigInterface
{
    public function get(string $key, mixed $default = null);

    public function set(string $key, mixed $value = null);
}
