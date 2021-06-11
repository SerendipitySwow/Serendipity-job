<?php
declare( strict_types = 1 );

namespace Serendipity\Job\Logger;


use Serendipity\Job\Kernel\Logger\StdoutLogger;
use Serendipity\Job\Kernel\Provider\AbstractProvider;

class LoggerProvider extends AbstractProvider
{
    public function bootApp (): void
    {
        $this->container()
             ->get(StdoutLogger::class);
    }
}
