<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Contract;

use Closure;

interface JobMiddlewareInterface
{
    /**
     * Handle current middleware.
     */
    public function handle(JobInterface $job, Closure $next): mixed;
}
