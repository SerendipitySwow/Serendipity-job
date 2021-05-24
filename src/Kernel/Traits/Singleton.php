<?php
declare(strict_types = 1);

namespace Serendipity\Job\Kernel\Traits;

trait Singleton
{
    /**
     * @var null|Singleton $instance
     */
    private static ?self $instance = null;

    /**
     * @return static
     */
    public static function create() : static
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        return self::$instance = new self();
    }
}
