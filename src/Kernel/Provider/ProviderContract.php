<?php
declare(strict_types = 1);

namespace Serendipity\Job\Kernel\Provider;

interface ProviderContract
{
    public static function bootApp();

    public static function bootRequest();

    public static function shutdown();
}
