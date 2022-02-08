<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Contract;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

interface SerializerInterface
{
    public function serialize(object $object, string $format = JsonEncoder::FORMAT): string;

    /**
     * @param array<int|string,int|string[]> $context
     */
    public function deserialize(
        string $serializable,
        string $type,
        string $format = JsonEncoder::FORMAT,
        array $context = []
    ): mixed;
}
