<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Logger;

use Monolog\Processor\MemoryProcessor;
use Serendipity\Job\Util\Context;

class AppendRequestIdProcessor extends MemoryProcessor
{
    public const TRACE_ID = 'log.trace.id';

    public function __invoke(array $record): array
    {
        $usage = memory_get_usage($this->realUsage);

        if ($this->useFormatting) {
            $usage = $this->formatBytes($usage);
        }

        $record['extra']['memory_usage'] = $usage;
        $record['context']['trace_id'] = Context::getOrSet(self::TRACE_ID, uniqid(md5(self::TRACE_ID), false));

        return $record;
    }
}
