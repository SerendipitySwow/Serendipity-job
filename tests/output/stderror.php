<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/SerendipityJob/main/LICENSE
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
