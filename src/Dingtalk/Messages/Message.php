<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Dingtalk\Messages;

use JetBrains\PhpStorm\ArrayShape;

abstract class Message
{
    protected array $message = [];

    protected mixed $at;

    public function getMessage(): array
    {
        return $this->message;
    }

    public function sendAt($mobiles = [], $atAll = false): Message
    {
        $this->at = $this->makeAt($mobiles, $atAll);

        return $this;
    }

    public function getBody(): array
    {
        if (empty($this->at)) {
            $this->sendAt();
        }

        return $this->message + $this->at;
    }

    #[ArrayShape(['at' => 'array'])]
    protected function makeAt($mobiles = [], $atAll = false): array
    {
        return [
            'at' => [
                'atMobiles' => $mobiles,
                'isAtAll' => $atAll,
            ],
        ];
    }
}
