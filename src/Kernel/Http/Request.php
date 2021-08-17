<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Http;

use Hyperf\Utils\Codec\Json;
use JetBrains\PhpStorm\Pure;
use Serendipity\Job\Util\Arr;
use Swow\Http\Server\Request as SwowRequest;

class Request extends SwowRequest
{
    public function all(): array
    {
        return array_merge(
            $this->getQueryParams(),
            Json::decode(
                $this->getBodyAsString() !== '' ? $this->getBodyAsString() : '{}',
            )
        ) ?? [];
    }

    public function post(string $key = null, mixed $default = null): mixed
    {
        $body = Json::decode(
            $this->getBody()
                ->getContents(),
        );

        return $key === null ? $body : $body[$key] ?? $default;
    }

    public function get(string $key = null, mixed $default = null): mixed
    {
        $params = $this->getQueryParams();

        return $key === null ? $params : $params[$key] ?? $default;
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    #[Pure]
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * @param null $default
     */
    public function file(string $key, $default = null): mixed
    {
        return Arr::get($this->getUploadedFiles(), $key, $default);
    }

    /**
     * Determine if the uploaded data contains a file.
     */
    public function hasFile(string $key): bool
    {
        if ($file = $this->file($key)) {
            return $this->isValidFile($file);
        }

        return false;
    }

    /**
     * Check that the given file is a valid SplFileInfo instance.
     */
    protected function isValidFile(mixed $file): bool
    {
        return $file instanceof SplFileInfo && $file->getPath() !== '';
    }
}
