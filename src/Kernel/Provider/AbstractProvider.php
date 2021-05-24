<?php
declare(strict_types = 1);

namespace Serendipity\Job\Kernel\Provider;

abstract class AbstractProvider implements ProviderContract
{

    public function bootApp() : void
    {
        echo __METHOD__;
    }

    public function bootRequest() : void
    {
        echo __METHOD__;
    }

    public function shutdown() : void
    {
        echo __METHOD__;
    }
}
