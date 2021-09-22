<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Contract;

use PDO;

interface ConnectorInterface
{
    /**
     * Establish a database connection.
     */
    public function connect(array $config): PDO;
}
