<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

$arr = [
    'https://www.baidu.com/1.jpeg',
    'https://www.baid2.com/2.jpeg',
    'https://www.baidu1.com/3.jpeg',
];
$str = json_encode([1]);
$arr = json_decode($str, true);
$arr = array_map(function ($value) {
    return [
        'url' => $value, 'filename' => pathinfo(parse_url($value, PHP_URL_PATH), PATHINFO_BASENAME),
    ];
}, $arr);
var_dump(json_encode($arr));
