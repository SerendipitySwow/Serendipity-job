<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/SerendipityJob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Logger;

use Monolog\Processor\ProcessorInterface;
use Serendipity\Job\Util\Context;

class AppendRequestIdProcessor implements ProcessorInterface
{
    public const TRACE_ID = 'log.trace.id';

    public function __invoke(array $record): array
    {
        $record['context']['trace_id'] = Context::getOrSet(self::TRACE_ID, uniqid(md5(self::TRACE_ID), false));

        return $record;
    }
}
