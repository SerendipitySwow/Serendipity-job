<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Contract;

use Closure;

interface JobMiddlewareInterface
{
    /**
     * Handle current middleware.
     */
    public function handle(JobInterface $job, Closure $next): mixed;
}
