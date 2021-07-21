<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Middleware;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Serendipity\Job\Db\DB;
use Serendipity\Job\Kernel\Signature;
use Serendipity\Job\Util\Context;
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
        $this->initRequest($application);

        return $this->signature->verifySignature($timestamp, $nonce, $payload, $appKey, $signature);
    }

    /**
     * todo //redis 存储application
     * @param $appKey
     *
     * @return array|bool
     */
    protected function getApplication($appKey): array | bool
    {
        $application = DB::fetch(sprintf(
            "SELECT * FROM application WHERE app_key = '%s' AND status = '1' AND is_deleted = '0'",
            $appKey
        ));

        return $application ?? false;
    }

    protected function initRequest(mixed $application): void
    {
        /**
         * @var Request $SwowRequest
         */
        $SwowRequest = Context::get(RequestInterface::class);
        $SwowRequest = $SwowRequest->withAddedHeader('application', $application);
        Context::set(RequestInterface::class, $SwowRequest);
    }
}
