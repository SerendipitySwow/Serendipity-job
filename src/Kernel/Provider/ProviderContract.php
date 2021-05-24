<?php
declare(strict_types = 1);

namespace Serendipity\Job\Kernel\Provider;

interface ProviderContract
{
    public function bootApp();

    public function bootRequest();

    public function shutdown();
}
