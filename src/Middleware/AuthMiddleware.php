<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Middleware;

use InvalidArgumentException;
use Serendipity\Job\Db\DB;
use Serendipity\Job\Kernel\Signature;
use Swow\Http\Server\Request;

class AuthMiddleware
{
    protected Signature $signature;

    final public function process(Request $request): bool
    {
        $timestamp = $request->getHeaderLine('timestamps') ?? '';
        $nonce = $request->getHeaderLine('nonce') ?? '';
        $payload = $request->getHeaderLine('payload') ?? '';
        $appKey = $request->getHeaderLine('app_key') ?? '';
        $signature = $request->getHeaderLine('signature') ?? '';
        $application = $this->getApplication($appKey);
        $application ? $this->signature = make(Signature::class, [
            'options' => [
                'signatureSecret' => $application['secret_key'] ?? '',
                'signatureAppKey' => $appKey,
            ],
        ]) : throw new InvalidArgumentException('Unknown AppKey#');

        return $this->signature->verifySignature($timestamp, $nonce, $payload, $appKey, $signature);
    }

    protected function getApplication($appKey): array | bool
    {
        $application = DB::fetch(sprintf("SELECT * FROM application WHERE app_key = '%s'", $appKey));

        return $application ?? false;
    }
}
