<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Serializer;

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
