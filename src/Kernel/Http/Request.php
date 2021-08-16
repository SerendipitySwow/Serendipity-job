<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Http;

use Psr\Http\Message\RequestInterface;
use Swow\Http\Server\Request as SwowRequest;

class Request
{
    /**
     * @var RequestInterface|SwowRequest
     */
    protected RequestInterface|SwowRequest $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function all(): array
    {
        return array_merge(
            $this->request
                    ->getQueryParams(),
            json_decode(
                    $this->request
                        ->getBodyAsString() !== '' ? $this->request
                        ->getBodyAsString() : '{}',
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                )
        ) ?? [];
    }

    /**
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->request->{$name}(...$arguments);
    }

    public function setRequest(SwowRequest $request): void
    {
        $this->request = $request;
    }
}
