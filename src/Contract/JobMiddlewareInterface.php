<?php
declare( strict_types = 1 );

namespace Serendipity\Job\Contract;

use Closure;

interface JobMiddlewareInterface
{
    /**
     * Handle current middleware.
     *
     * @param  JobInterface  $job
     * @param  Closure  $next
     *
     * @return mixed
     */
    public function handle (JobInterface $job, Closure $next): mixed;
}
