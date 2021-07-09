<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel;

use Hyperf\Utils\Str;
use Serendipity\Job\Contract\ConfigInterface;

//TODO api签名验证
class Signature
{
    /**
     * @var array 签名验证信息
     */
    private array $signature;

    /**
     * @var string 加密方式
     */
    private string $signatureType = 'sha256';

    public function __construct(ConfigInterface $config)
    {
        $this->signature = $config->get('signature');
    }

    /**
     * @Notes: 生成对称加密密钥
     *
     * @param string $timestamp 时间戳
     * @param string $nonce 16位随机字符串
     * @param string $payload 请求body
     * @param string $signatureSecret 签名密钥
     */
    public function generateSignature(
        string $timestamp,
        string $nonce,
        string $payload,
        string $signatureSecret
    ): string {
        $data = $timestamp . $nonce . $payload;
        $hmac = hash_hmac($this->signatureType, $data, $signatureSecret);

        return base64_encode($hmac);
    }

    /**
     * @Notes: 生成随机字符串
     */
    public function generateNonce(): string
    {
        return Str::random();
    }

    /**
     * @Notes: 验证密钥是否正确
     *
     * @param string $timestamp //时间戳
     * @param string $nonce //16位随机字符串
     * @param string $payload // 请求荷载
     * @param string $signatureApiKey //服务端api key
     * @param string $clientSignature //客户端生成的 签名
     */
    public function verifySignature(
        string $timestamp,
        string $nonce,
        string $payload,
        string $signatureApiKey,
        string $clientSignature
    ): bool {
        $arguments = func_get_args();
        foreach ($arguments as $v) {
            if (empty($v)) {
                return false;
            }
        }
        if (time() - $timestamp > $this->getSignatureApiTime($signatureApiKey)) {
            return false;
        }
        $apiSecret = $this->getSignatureApiSecret($signatureApiKey);
        if (empty($apiSecret) || !$this->verifiedTimestamp($timestamp, $signatureApiKey)) {
            return false;
        }
        $arg = [
            $timestamp,
            $nonce,
            $payload,
            $apiSecret,
        ];
        $generateSignature = $this->generateSignature(...$arg);

        return !($generateSignature !== $clientSignature);
    }

    /**
     * @Notes: 根据api 获取api secret
     */
    public function getSignatureApiSecret(string $signatureApiKey): string
    {
        $apiInfo = $this->getSignatureApiInfo($signatureApiKey);
        if (empty($apiInfo)) {
            return '';
        }

        return current($apiInfo)['signatureSecret'];
    }

    /**
     * @Notes: 获取验证时间戳
     */
    public function getSignatureApiTime(string $signatureApiKey): string
    {
        $apiInfo = $this->getSignatureApiInfo($signatureApiKey);
        if (empty($apiInfo)) {
            return '';
        }

        return current($apiInfo)['timestampValidity'];
    }

    /**
     * @Notes: 根据apikey 获取api信息
     */
    protected function getSignatureApiInfo(string $signatureApiKey = ''): array
    {
        $apiInfo = array_filter($this->signature, static fn ($item) => $item['signatureApiKey'] === $signatureApiKey);
        if (empty($apiInfo)) {
            return [];
        }

        return array_values($apiInfo);
    }

    /**
     * @Notes: 验证时间戳有效性
     */
    protected function verifiedTimestamp(string $timestamp = '', string $signatureApiKey = ''): bool
    {
        $apiInfo = $this->getSignatureApiInfo($signatureApiKey);
        if (empty($apiInfo)) {
            return false;
        }
        $timestampValidity = (int) $apiInfo[0]['timestampValidity'];

        return !(time() - (int) $timestamp > $timestampValidity);
    }
}
