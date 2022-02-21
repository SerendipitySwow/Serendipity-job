<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel\Swow;

class Socket extends \Swow\Http\Server
{
    /** 获取当前server连接数 */
    public function connecions(): int
    {
        return count($this->connections);
    }
}
