<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\RedisSubscriber;

class Status implements ResponseInterface
{
    private static $OK;

    private static $QUEUED;

    private string $payload;

    /**
     * @param string $payload payload of the status response as returned by Redis
     */
    public function __construct(string $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Converts the response object to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->payload;
    }

    /**
     * Returns the payload of status response.
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

    /**
     * Returns an instance of a status response object.
     *
     * Common status responses such as OK or QUEUED are cached in order to lower
     * the global memory usage especially when using pipelines.
     *
     * @param string $payload status response payload
     *
     * @return \Serendipity\Job\RedisSubscriber\Status|string
     * @noinspection PhpVariableVariableInspection
     */
    public static function get(string $payload): Status|string
    {
        /* @noinspection ProperNullCoalescingOperatorUsageInspection */
        return match ($payload) {
            'OK', 'QUEUED' => self::${$payload} ?? (self::${$payload} = new self($payload)),
            default => new self($payload),
        };
    }
}
