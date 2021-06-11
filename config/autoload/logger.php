<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/SerendipityJob/main/LICENSE
 */

declare(strict_types=1);

use Monolog\Formatter\JsonFormatter;
use Serendipity\Job\Kernel\Logger\AppendRequestIdProcessor;

return [
    'default' => [
        'handler' => [
            'class' => Monolog\Handler\StreamHandler::class,
            'constructor' => [
                'stream' => BASE_PATH . '/runtime/logs/hyperf.log',
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
