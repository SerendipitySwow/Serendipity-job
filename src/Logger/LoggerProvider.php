<?php
declare(strict_types = 1);

namespace Serendipity\Job\Logger;

use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\LoggerInterface;
use Serendipity\Job\Kernel\Logger\StdoutLogger;
use Serendipity\Job\Kernel\Provider\AbstractProvider;

class  LoggerProvider extends AbstractProvider
{
    public function bootApp() : void
    {
        $logger = new StdoutLogger($this->container()->get(ConfigInterface::class));
        $this->container()->set(LoggerInterface::class, $logger);
    }
}
