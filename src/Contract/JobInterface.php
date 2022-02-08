<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Contract;

interface JobInterface
{
    /**
     * Execute current job.
     */
    public function handle(): void;

    /**
     * Determine whether current job can retry if failed.
     */
    public function canRetry(int $counter, mixed $error): bool;

    /**
     * Get current job's next execution unix time after failed.
     */
    public function retryAfter(int $counter): int;

    /**
     * After failed, this function will be called.
     * @param array<string,int|string> $payload
     */
    public function failed(array $payload): void;

    /**
     * Get the middleware the job should pass through.
     *
     * @return JobMiddlewareInterface[]
     */
    public function middleware(): array;

    /**
     * Get job Token
     */
    public function getIdentity(): int|string;

    /**
     * Get job action time
     */
    public function getTimeout(): int;

    public function IncreaseCounter(int $attempt = 1): mixed;

    public function getStep(): int;

    /**
     * Get the number of retries
     */
    public function getCounter(): int;
}
