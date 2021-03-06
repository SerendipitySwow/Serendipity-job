<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Dingtalk;

use Psr\Http\Message\ResponseInterface;
use Throwable;

interface ClientInterface
{
    /**
     * Create and send an HTTP request.
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param array $params Request parameters
     *
     * @throws Throwable
     */
    public function send(array $params = []): ResponseInterface;
}
