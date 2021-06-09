<?php
declare( strict_types = 1 );

use Psr\Log\LogLevel;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use function Serendipity\Job\Kernel\serendipity_env;

return [
    'APP_VERSION' => serendipity_env('APP_VERSION'),
    'DEBUG' => serendipity_env('DEBUG', true),
    StdoutLoggerInterface::class => [
        'log_level' => [
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::DEBUG,
            LogLevel::EMERGENCY,
            LogLevel::ERROR,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
        ],
    ]
];
