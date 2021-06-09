<?php

declare( strict_types = 1 );

namespace Serendipity\Job\Kernel\Nsq\Config;

use Composer\InstalledVersions;

/**
 * This class is used for configuring the clients for nsq. Immutable properties must be set when creating the object and
 * are sent to NSQ for feature specification or negotiation. Keep in mind that some features might require some
 * configuration on the server-side and could be not available.
 *
 * @psalm-immutable
 */
final class ClientConfig implements \JsonSerializable
{
    /**
     * @psalm-suppress ImpureFunctionCall
     */
    public function __construct (
        /**
         * The secret used for authorization, if the server requires it. This value will be ignored if the server
         * does not require authorization.
         */
        public ?string $authSecret = null,

        /**
         * The timeout for establishing a connection in seconds.
         */
        public int $connectTimeout = 10,

        /**
         * An identifier used to disambiguate this client (i.e. something specific to the consumer).
         */
        public string $clientId = '',

        /**
         * Enable deflate compression for this connection. A client cannot enable both [snappy] and [deflate].
         */
        public bool $deflate = false,

        /**
         * Configure the deflate compression level for this connection.
         *
         * Valid range: `1 <= deflate_level <= configured_max`
         *
         * Higher values mean better compression but more CPU usage for nsqd.
         */
        public int $deflateLevel = 6,

        /**
         * Milliseconds between heartbeats.
         *
         * Valid range: `1000 <= heartbeat_interval <= configured_max` (`-1` disables heartbeats)
         */
        public int $heartbeatInterval = 30000,

        /**
         * The hostname where the client is deployed.
         */
        public string $hostname = '',

        /**
         * Configure the server-side message timeout in milliseconds for messages delivered to this client.
         */
        public int $msgTimeout = 60000,

        /**
         * The sample rate for incoming data to deliver a percentage of all messages received to this connection.
         * This only applies to subscribing connections. The valid range is between 0 and 99, where 0 means that all
         * data is sent (this is the default). 1 means that 1% of the data is sent.
         */
        public int $sampleRate = 0,

        /**
         * Boolean used to indicate that the client supports feature negotiation. If the server is capable,
         * it will send back a JSON payload of supported features and metadata.
         */
        public bool $featureNegotiation = true,

        /**
         * Enable TLS for this connection.
         */
        public bool $tls = false,

        /**
         * Enable snappy compression for this connection. A client cannot enable both [snappy] and [deflate].
         */
        public bool $snappy = false,

        /**
         * The read timeout for connection sockets and for awaiting responses from nsq.
         */
        public int $readTimeout = 5,

        /**
         * A string identifying the agent for this client in the spirit of HTTP.
         */
        public string $userAgent = '',
    ) {
        $this->featureNegotiation = true; // Always enabled

        if ('' === $this->hostname) {
            $this->hostname = ( static fn (mixed $h): string => \is_string($h) ? $h : '' )(gethostname());
        }

        if ('' === $this->userAgent) {
            $this->userAgent = 'nsqphp/' . InstalledVersions::getPrettyVersion('nsq/nsq');
        }

        if ($this->snappy && $this->deflate) {
            throw new \InvalidArgumentException('Client cannot enable both [snappy] and [deflate]');
        }
    }

    /**
     * @phpstan-ignore-next-line
     */
    public function jsonSerialize (): array
    {
        return [
            'client_id' => $this->clientId,
            'deflate' => $this->deflate,
            'deflate_level' => $this->deflateLevel,
            'feature_negotiation' => $this->featureNegotiation,
            'heartbeat_interval' => $this->heartbeatInterval,
            'hostname' => $this->hostname,
            'msg_timeout' => $this->msgTimeout,
            'sample_rate' => $this->sampleRate,
            'snappy' => $this->snappy,
            'tls_v1' => $this->tls,
            'user_agent' => $this->userAgent,
        ];
    }

    public function toString (): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);
    }
}
