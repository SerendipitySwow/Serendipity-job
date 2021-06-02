<?php
declare(strict_types = 1);

namespace Serendipity\Job\Contract;

interface JobMiddlewareInterface
{
    /**
     * Handle current middleware.
     *
     * @param \Serendipity\Job\Contract\JobInterface $job
     * @param \Closure                               $next
     *
     * @return mixed
     */
    public function handle(JobInterface $job, \Closure $next) : mixed;
}
