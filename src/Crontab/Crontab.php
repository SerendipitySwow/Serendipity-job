<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Crontab;

use Carbon\Carbon;

class Crontab
{
    protected ?string $name;

    protected string $type = 'callback';

    protected ?string $rule;

    protected bool $singleton = false;

    protected string $mutexPool = 'default';

    protected int $mutexExpires = 3600;

    protected bool $onOneServer = false;

    protected mixed $callback;

    protected ?string $memo;

    protected ?Carbon $executeTime;

    protected bool $enable = true;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Crontab
    {
        $this->name = $name;

        return $this;
    }

    public function getRule(): ?string
    {
        return $this->rule;
    }

    public function setRule(?string $rule): Crontab
    {
        $this->rule = $rule;

        return $this;
    }

    public function isSingleton(): bool
    {
        return $this->singleton;
    }

    public function setSingleton(bool $singleton): Crontab
    {
        $this->singleton = $singleton;

        return $this;
    }

    public function getMutexPool(): string
    {
        return $this->mutexPool;
    }

    public function setMutexPool(string $mutexPool): Crontab
    {
        $this->mutexPool = $mutexPool;

        return $this;
    }

    public function getMutexExpires(): int
    {
        return $this->mutexExpires;
    }

    public function setMutexExpires(int $mutexExpires): Crontab
    {
        $this->mutexExpires = $mutexExpires;

        return $this;
    }

    public function isOnOneServer(): bool
    {
        return $this->onOneServer;
    }

    public function setOnOneServer(bool $onOneServer): Crontab
    {
        $this->onOneServer = $onOneServer;

        return $this;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function setCallback($callback): Crontab
    {
        $this->callback = $callback;

        return $this;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function setMemo(?string $memo): Crontab
    {
        $this->memo = $memo;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Crontab
    {
        $this->type = $type;

        return $this;
    }

    public function getExecuteTime(): ?Carbon
    {
        return $this->executeTime;
    }

    public function setExecuteTime(Carbon $executeTime): Crontab
    {
        $this->executeTime = $executeTime;

        return $this;
    }

    public function isEnable(): bool
    {
        return $this->enable;
    }

    public function setEnable(bool $enable): Crontab
    {
        $this->enable = $enable;

        return $this;
    }
}
