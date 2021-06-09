<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq\Stream;

use Amp\Promise;
use Serendipity\Job\Kernel\Nsq\Buffer;
use Serendipity\Job\Kernel\Nsq\Exception\SnappyException;
use Serendipity\Job\Kernel\Nsq\Stream;
use function Amp\call;

class SnappyStream implements Stream
{
    private const IDENTIFIER = [0xff, 0x06, 0x00, 0x00, 0x73, 0x4e, 0x61, 0x50, 0x70, 0x59];
    private const SIZE_HEADER = 4;
    private const SIZE_CHECKSUM = 4;
    private const SIZE_CHUNK = 65536;
    private const TYPE_IDENTIFIER = 0xff;
    private const TYPE_COMPRESSED = 0x00;
    private const TYPE_UNCOMPRESSED = 0x01;
    private const TYPE_PADDING = 0xfe;

    private Buffer $buffer;

    public function __construct(private Stream $stream, string $bytes = '')
    {
        if (!\function_exists('snappy_uncompress')) {
            throw SnappyException::notInstalled();
        }

        $this->buffer = new Buffer($bytes);
    }

    /**
     * {@inheritdoc}
     */
    public function read(): Promise
    {
        return call(function (): \Generator {
            if ($this->buffer->size() < self::SIZE_HEADER && null !== ($chunk = yield $this->stream->read())) {
                $this->buffer->append($chunk);
            }

            $type = $this->buffer->readUInt32LE();

            $size = $type >> 8;
            $type &= 0xff;

            while ($this->buffer->size() < $size && null !== ($chunk = yield $this->stream->read())) {
                $this->buffer->append($chunk);
            }

            switch ($type) {
                case self::TYPE_IDENTIFIER:
                    $this->buffer->discard($size);

                    return $this->read();
                case self::TYPE_COMPRESSED:
                    $this->buffer->discard(self::SIZE_CHECKSUM);

                    return snappy_uncompress($this->buffer->consume($size - self::SIZE_HEADER));
                case self::TYPE_UNCOMPRESSED:
                    $this->buffer->discard(self::SIZE_CHECKSUM);

                    return $this->buffer->consume($size - self::SIZE_HEADER);
                case self::TYPE_PADDING:
                    return $this->read();
                default:
                    throw SnappyException::invalidHeader();
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $data): Promise
    {
        return call(function () use ($data): Promise {
            $result = pack('CCCCCCCCCC', ...self::IDENTIFIER);

            foreach (str_split($data, self::SIZE_CHUNK) as $chunk) {
                $result .= $this->compress($chunk);
            }

            return $this->stream->write($result);
        });
    }

    public function close(): void
    {
        $this->stream->close();
    }

    /**
     * @psalm-suppress PossiblyFalseArgument
     */
    private function compress(string $uncompressed): string
    {
        $compressed = snappy_compress($uncompressed);

        [$type, $data] = \strlen($compressed) <= 0.875 * \strlen($uncompressed)
            ? [self::TYPE_COMPRESSED, $compressed]
            : [self::TYPE_UNCOMPRESSED, $uncompressed];

        /** @phpstan-ignore-next-line */
        $checksum = unpack('N', hash('crc32c', $uncompressed, true))[1];
        $checksum = (($checksum >> 15) | ($checksum << 17)) + 0xa282ead8 & 0xffffffff;

        $size = (\strlen($data) + 4) << 8;

        return pack('VV', $type + $size, $checksum).$data;
    }
}
