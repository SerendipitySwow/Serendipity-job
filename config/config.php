<?php
declare(strict_types = 1);

use Psr\Log\LogLevel;
use Serendipity\Job\Contract\LoggerInterface;
use function Serendipity\Job\Kernel\env;

return [
    'APP_VERSION'          => env('APP_VERSION'),
    LoggerInterface::class => [
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
