<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Util\Contracts;

interface MessageProvider
{
    /**
     * Get the messages for the instance.
     */
    public function getMessageBag(): MessageBag;
}
