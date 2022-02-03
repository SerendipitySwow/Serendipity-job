<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

$bodytag = str_replace('{date}', date('Y-m-d'), '/10001/{txt}');
echo $bodytag;

echo trim("date('y-m-d')");
