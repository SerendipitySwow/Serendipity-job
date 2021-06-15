<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

echo '请输入内容:';
$jimmy = fgets(STDIN);
echo sprintf("输入的内容为: %s\n", $jimmy);

$demo = fopen('php://stdin', 'r');
echo '请输入: ';
$test = fread($demo, 12); //最多读取12个字符
echo sprintf("输入为: %s\n", $test);
fclose($demo);
