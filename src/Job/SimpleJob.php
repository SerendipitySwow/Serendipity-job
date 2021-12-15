<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Job;

use Serendipity\Job\Contract\JobInterface;

class SimpleJob implements JobInterface
{
    public int|string $identity;

    public int $timeout;

    public int $retryTimes;

    public string $name;

    public int $step;

    public function __construct(int|string $identity, int $timeout, int $retryTimes, string $name, int $step)
    {
        $this->identity = $identity;
        $this->timeout = $timeout;
        $this->retryTimes = $retryTimes;
        $this->name = $name;
        $this->step = $step;
    }

    public function handle(): void
    {
        echo '输出一个字符串.' . PHP_EOL;
//        sleep(20);
//        throw new \Exception('抛出异常');
    }

    public function canRetry(int $counter, $error): bool
    {
        return $counter < 5;
    }

    public function retryAfter(int $counter): int
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

    public function getIdentity(): int|string
    {
        return $this->identity;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function IncreaseCounter(int $attempt = 1): int
    {
        return ++$this->retryTimes;
    }

    public function getStep(): int
    {
        return $this->step;
    }

    public function getCounter(): int
    {
        return $this->retryTimes;
    }
}
