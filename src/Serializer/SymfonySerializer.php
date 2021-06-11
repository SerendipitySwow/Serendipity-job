<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/SerendipityJob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Serializer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

class SymfonySerializer extends Serializer
{
    public function serialize(object $object, string $format = JsonEncoder::FORMAT): string
    {
        return $this->serializer->serialize($object, $format);
    }

    public function deserialize(
        string $serializable,
        string $type,
        string $format = JsonEncoder::FORMAT,
        array $context = []
    ): mixed {
        return $this->serializer->deserialize($serializable, $type, $format, $context);
    }
}
