<?php

declare( strict_types = 1 );

namespace Serendipity\Job\Kernel\Nsq\Config;

/**
 * The configuration object that holds the config status for a single Connection.
 *
 * @psalm-immutable
 */
final class ConnectionConfig
{
    public function __construct (
        /**
         * Whether or not authorization is required by nsqd.
         */
        public bool $authRequired,

        /**
         * Whether deflate compression is enabled for this connection or not.
         */
        public bool $deflate,

        /**
         * The deflate level. This value can be ignored if [deflate] is `false`.
         */
        public int $deflateLevel,

        /**
         * The maximum deflate level supported by the server.
         */
        public int $maxDeflateLevel,

        /**
         * The maximum value for message timeout.
         */
        public int $maxMsgTimeout,

        /**
         * Each nsqd is configurable with a max-rdy-count. If the consumer sends a RDY count that is outside
         * of the acceptable range its connection will be forcefully closed.
         */
        public int $maxRdyCount,

        /**
         * The effective message timeout.
         */
        public int $msgTimeout,

        /**
         * The size in bytes of the buffer nsqd will use when writing to this client.
         */
        public int $outputBufferSize,

        /**
         * The timeout after which any data that nsqd has buffered will be flushed to this client.
         */
        public int $outputBufferTimeout,

        /**
         * The sample rate for incoming data to deliver a percentage of all messages received to this connection.
         * This only applies to subscribing connections. The valid range is between 0 and 99, where 0 means that all
         * data is sent (this is the default). 1 means that 1% of the data is sent.
         */
        public int $sampleRate,

        /**
         * Whether snappy compression is enabled for this connection or not.
         */
        public bool $snappy,

        /**
         * Whether TLS is enabled for this connection or not.
         */
        public bool $tls,

        /**
         * The nsqd version.
         */
        public string $version,
    ) {
    }

    /**
     * @phpstan-ignore-next-line
     */
    public static function fromArray (array $array): self
    {
        return new self(
            authRequired: $array['auth_required'],
            deflate: $array['deflate'],
            deflateLevel: $array['deflate_level'],
            maxDeflateLevel: $array['max_deflate_level'],
            maxMsgTimeout: $array['max_msg_timeout'],
            maxRdyCount: $array['max_rdy_count'],
            msgTimeout: $array['msg_timeout'],
            outputBufferSize: $array['output_buffer_size'],
            outputBufferTimeout: $array['output_buffer_timeout'],
            sampleRate: $array['sample_rate'],
            snappy: $array['snappy'],
            tls: $array['tls_v1'],
            version: $array['version'],
        );
    }
}
