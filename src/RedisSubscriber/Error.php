<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\RedisSubscriber;

use JetBrains\PhpStorm\Pure;

class Error
{
    private string $message;

    /**
     * @param string $message Error message returned by Redis
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * {}
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    #[Pure]
 public function getErrorType(): string
 {
     [$errorType] = explode(' ', $this->getMessage(), 2);

     return $errorType;
 }

    /**
     * Converts the object to its string representation.
     *
     * @return string
     */
    #[Pure]
 public function __toString()
 {
     return $this->getMessage();
 }
}
