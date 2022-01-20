<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace App\Job;

use SwowCloud\Job\Contract\JobInterface;
use SwowCloud\Job\Contract\JobMiddlewareInterface;

class JobMiddleware implements JobMiddlewareInterface
{
    public function handle(JobInterface $job, \Closure $next): mixed
    {
        return $next($job);
    }
}
