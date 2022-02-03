<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel\Traits;

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
