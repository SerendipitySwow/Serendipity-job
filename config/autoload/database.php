<?php
declare(strict_types = 1);

use function Serendipity\Job\Kernel\env;

return [
    'default' => [
        'host'          => env('DB_HOST', 'localhost'),
        'port'          => env('DB_PORT', 3306),
        'database'      => env('DB_DATABASE', 'hyperf'),
        'username'      => env('DB_USERNAME', 'root'),
        'password'      => env('DB_PASSWORD', ''),
        'charset'       => env('DB_CHARSET', 'utf8'),
        'collation'     => env('DB_COLLATION', 'utf8_unicode_ci'),
        'prefix'        => env('DB_PREFIX', ''),
        'maxSpareConns' => 5,
        'maxConns'      => 10,
        'options'       => [
            // 框架默认配置
            PDO::ATTR_CASE              => PDO::CASE_NATURAL,
            PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            // 如果使用的为非原生 MySQL 或云厂商提供的 DB 如从库/分析型实例等不支持 MySQL prepare 协议的, 将此项设置为 true
            PDO::ATTR_EMULATE_PREPARES  => false,
        ],
    ],
];
