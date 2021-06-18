<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Redis;

trait ScanCaller
{
    public function scan(&$cursor, $pattern = null, $count = 0)
    {
        return $this->__call('scan', [&$cursor, $pattern, $count]);
    }

    public function hScan($key, &$cursor, $pattern = null, $count = 0)
    {
        return $this->__call('hScan', [$key, &$cursor, $pattern, $count]);
    }

    public function zScan($key, &$cursor, $pattern = null, $count = 0)
    {
        return $this->__call('zScan', [$key, &$cursor, $pattern, $count]);
    }

    public function sScan($key, &$cursor, $pattern = null, $count = 0)
    {
        return $this->__call('sScan', [$key, &$cursor, $pattern, $count]);
    }
}
