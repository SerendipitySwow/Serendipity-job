<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/SerendipityJob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Contract;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

interface SerializerInterface
{
    public function serialize(object $object, string $format = JsonEncoder::FORMAT): string;

    public function deserialize(
        string $serializable,
        string $type,
        string $format = JsonEncoder::FORMAT,
        array $context = []
    ): mixed;
}
