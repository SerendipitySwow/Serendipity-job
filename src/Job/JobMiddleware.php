<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Job;

use Serendipity\Job\Contract\JobInterface;
use Serendipity\Job\Contract\JobMiddlewareInterface;

class JobMiddleware implements JobMiddlewareInterface
{
    public function handle(JobInterface $job, \Closure $next): mixed
    {
        if (!$job instanceof JobInterface) {
            throw new ScheduleException('参数无效');
        }

        return $next($job);
    }
}
