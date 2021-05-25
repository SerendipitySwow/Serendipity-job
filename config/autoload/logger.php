<?php

declare(strict_types = 1);

use Serendipity\Job\Kernel\Logger\AppendRequestIdProcessor;
use Monolog\Formatter\JsonFormatter;

return [
    'default' => [
        'handler'    => [
            'class'       => Monolog\Handler\StreamHandler::class,
            'constructor' => [
                'stream' => SERENDIPITY_JOB_PATH . '/runtime/logs/hyperf.log',
                'level'  => Monolog\Logger::DEBUG,
            ],
        ],
        'formatter'  => [
            'class'       => JsonFormatter::class,
            'constructor' => [
                'batchMode'     => JsonFormatter::BATCH_MODE_JSON,
                'appendNewline' => true,
            ],
        ],
        'processors' => [
            [
                'class' => AppendRequestIdProcessor::class,
            ],
        ],
    ],
];
