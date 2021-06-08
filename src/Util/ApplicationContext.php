<?php

declare(strict_types = 1);

namespace Serendipity\Job\Util;

use Serendipity\Job\Application;

class ApplicationContext
{
    /**
     * @var null|Application
     */
    private static ?Application $application = null;

    /**
     * @return \Serendipity\Job\Application
     */
    public static function getApplication() : Application
    {
        return self::$application;
    }

    public static function hasApplication() : bool
    {
        return isset(self::$application);
    }

    public static function setApplication(Application $application) : Application
    {
        self::$application = $application;
        return $application;
    }

}
