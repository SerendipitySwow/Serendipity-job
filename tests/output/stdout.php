<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

fwrite(STDOUT, "通过STDOUT写入；\n");

$demo = fopen('php://stdout', 'w');
fwrite($demo, '通过php://stdout写入；');
fclose($demo);
