<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel;

use Hyperf\Utils\Str;
use Serendipity\Job\Contract\ConfigInterface;

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

    public function __construct(ConfigInterface $config, array $options = [])
    {
        $this->signature = array_merge($config->get('signature'), $options);
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
     * @param string $signatureAppKey //服务端app key
     * @param string $clientSignature //客户端生成的 签名
     */
    public function verifySignature(
        string $timestamp,
        string $nonce,
        string $payload,
        string $signatureAppKey,
        string $clientSignature
    ): bool {
        $arguments = func_get_args();
        foreach ($arguments as $v) {
            if (empty($v)) {
                return false;
            }
        }
        if (time() - $timestamp > $this->getSignatureApiTime($signatureAppKey)) {
            return false;
        }
        $apiSecret = $this->getSignatureApiSecret($signatureAppKey);
        if (empty($apiSecret) || !$this->verifiedTimestamp($timestamp, $signatureAppKey)) {
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
    public function getSignatureApiSecret(string $signatureAppKey): string
    {
        if (!$this->checkSignatureApiInfo($signatureAppKey)) {
            return '';
        }

        return $this->signature['signatureSecret'];
    }

    /**
     * @Notes: 获取验证时间戳
     */
    public function getSignatureApiTime(string $signatureAppKey): int | string
    {
        if (!$this->checkSignatureApiInfo($signatureAppKey)) {
            return '';
        }

        return $this->signature['timestampValidity'];
    }

    protected function checkSignatureApiInfo(string $signatureAppKey = ''): bool
    {
        return $this->signature['signatureAppKey'] === $signatureAppKey;
    }

    /**
     * @Notes: 验证时间戳有效性
     */
    protected function verifiedTimestamp(string $timestamp = '', string $signatureAppKey = ''): bool
    {
        if (!$this->checkSignatureApiInfo($signatureAppKey)) {
            return false;
        }
        $timestampValidity = (int) $this->signature['timestampValidity'];

        return !(time() - (int) $timestamp > $timestampValidity);
    }
}
