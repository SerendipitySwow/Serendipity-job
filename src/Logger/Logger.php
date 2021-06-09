<?php
declare( strict_types = 1 );

namespace Serendipity\Job\Logger;

use Monolog\Logger as MonoLogger;
use Serendipity\Job\Contract\LoggerInterface;

class Logger extends MonoLogger implements LoggerInterface
{
}

