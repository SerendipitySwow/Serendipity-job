<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

fwrite(STDERR, "STDERR写入的错误输出；\n");

fwrite(STDOUT, "STDOUT写入的正常输出；\n");

$stdout = fopen('php://stdout', 'w');
fwrite($stdout, "php://stdout写入的正常输出；\n");
fclose($stdout);

$stderr = fopen('php://stderr', 'w');
fwrite($stderr, "php://stderr写入的错误输出；\n");
fclose($stderr);
