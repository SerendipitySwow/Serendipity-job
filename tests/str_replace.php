<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

$bodytag = str_replace('{date}', date('Y-m-d'), '/10001/{txt}');
echo $bodytag;

echo trim("date('y-m-d')");
