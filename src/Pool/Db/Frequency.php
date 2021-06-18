<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Pool\Db;

class Frequency extends \Hyperf\Pool\Frequency
{
    /**
     * 被计算频率的时间间隔
     * @var int
     */
    protected $time = 10;

    /**
     * 触发低频的频率
     * @var int
     */
    protected $lowFrequency = 5;

    /**
     * 连续触发低频的最小时间间隔
     * @var int
     */
    protected $lowFrequencyInterval = 60;
}
