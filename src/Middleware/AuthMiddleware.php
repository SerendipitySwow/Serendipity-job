<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Middleware;

use Serendipity\Job\Kernel\Signature;
use Swow\Http\Server\Request;

class AuthMiddleware
{
    protected Signature $signature;

    final public function __construct(Signature $signature)
    {
        $this->signature = $signature;
    }

    final public function process(Request $request): bool
    {
        $timestamp = $request->getHeaderLine('timestamps') ?? '';
        $nonce = $request->getHeaderLine('nonce') ?? '';
        $payload = $request->getHeaderLine('payload') ?? '';
        $apiKey = $request->getHeaderLine('api_key') ?? '';
        $signature = $request->getHeaderLine('signature') ?? '';

        return $this->signature->verifySignature($timestamp, $nonce, $payload, $apiKey, $signature);
    }
}
