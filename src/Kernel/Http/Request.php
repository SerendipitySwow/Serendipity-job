<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Http;

use Swow\Http\Server\Request as SwowRequest;

class Request extends SwowRequest
{
    public function all(): array
    {
        return array_merge(
            $this->getQueryParams(),
            json_decode(
                    $this->getBodyAsString() !== '' ? $this->getBodyAsString() : '{}',
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                )
        ) ?? [];
    }

    public function post(string $key = null, mixed $default = null): mixed
    {
        $body = json_decode(
            $this->getBody()
                ->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return $key === null ? $body : $body[$key] ?? $default;
    }

    public function get(string $key = null, mixed $default = null): mixed
    {
        $params = $this->getQueryParams();

        return $key === null ? $params : $params[$key] ?? $default;
    }
}
