<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Db\Pool;

use Hyperf\Contract\ConnectionInterface;
use Serendipity\Job\Db\PDOConnection;

class PDOPool extends Pool
{
    protected function createConnection(): ConnectionInterface
    {
        return new PDOConnection($this->container, $this, $this->config);
    }
}
