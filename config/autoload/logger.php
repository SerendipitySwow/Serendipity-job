<?php

declare(strict_types = 1);

return [
    'default' => [
        'handlers'   => [
            [
                'class'       => Monolog\Handler\StreamHandler::class,
                'constructor' => [
                    'stream' => SERENDIPITY_JOB_PATH . '/runtime/logs/hyperf.log',
                    'level'  => Monolog\Logger::DEBUG,
                ],
                'formatter'   => [
                    'class'       => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [],
                ],
            ],
        ],
        'processors' => [
        ],
    ],
];
