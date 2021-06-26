<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Job;

use Serendipity\Job\Contract\JobInterface;

class SimpleJob implements JobInterface
{
    public function __construct()
    {
    }

    public function handle(): void
    {
        throw new \Exception('测试钉钉,陈宇凡我儿子');
    }

    public function canRetry(int $attempt, $error): bool
    {
        return $attempt < 5;
    }

    public function retryAfter(int $attempt): int
    {
        return 0;
    }

    public function failed(array $payload): void
    {
        echo "job#{} was failed.\n";
    }

    public function middleware(): array
    {
        return [JobMiddleware::class];
    }

    public function getIdentity(): int | string
    {
        // TODO: Implement getIdentity() method.
    }

    public function getTimeout(): int
    {
        // TODO: Implement getTimeout() method.
    }

    public function IncreaseCounter(int $attempt = 1): mixed
    {
        // TODO: Implement IncreaseCounter() method.
    }

    public function getStep(): int
    {
        // TODO: Implement getStep() method.
    }

    public function getCounter(): int
    {
        // TODO: Implement getCounter() method.
    }
}
