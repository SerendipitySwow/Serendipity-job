<?php
/**
 * This file is part of Serendipity Job
 *
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare( strict_types = 1 );

namespace Serendipity\Job\Contract;

interface JobInterface
{
    /**
     * Execute current job.
     *
     * @return mixed
     */
    public function handle (): void;

    /**
     * Determine whether current job can retry if fail.
     *
     * @param  int  $attempt
     * @param $error
     *
     * @return bool
     */
    public function canRetry (int $attempt, $error): bool;

    /**
     * Get current job's next execution unix time after failed.
     */
    public function retryAfter (int $attempt): int;

    /**
     * After failed, this function will be called.
     */
    public function failed (int $id, array $payload): void;

    /**
     * Get the middleware the job should pass through.
     *
     * @return JobMiddlewareInterface[]
     */
    public function middleware (): array;

    /**
     * Get job Token
     *
     * @return int|string
     */
    public function getIdentity (): int|string;

    /**
     * Get job action time
     */
    public function getTimeout (): int;

    /**
     * @param  int  $attempt
     *
     * @return mixed
     */
    public function IncreaseCounter (int $attempt = 1): mixed;

    /**
     * Get the number of retries
     *
     * @return int
     */
    public function getCounter (): int;
}
