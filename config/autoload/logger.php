<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use Monolog\Formatter\JsonFormatter;
use Serendipity\Job\Kernel\Logger\AppendRequestIdProcessor;

return [
    'default' => [
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,
            'constructor' => [
                'filename' => BASE_PATH . '/runtimes/logs/serendipity_job.log',
                'maxFiles' => 5,
                'level' => Monolog\Logger::DEBUG,
            ],
        ],
        'formatter' => [
            'class' => JsonFormatter::class,
            'constructor' => [
                'batchMode' => JsonFormatter::BATCH_MODE_JSON,
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
