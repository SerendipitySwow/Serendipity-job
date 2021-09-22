<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Traits;

trait Singleton
{
    private static ?self $instance = null;

    public static function create(string $module = null): static
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        return self::$instance = new self($module);
    }
}
