<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Dingtalk\Messages;

use Psr\Http\Message\ResponseInterface;
use SwowCloud\Job\Dingtalk\DingTalkService;

class FeedCard extends Message
{
    protected DingTalkService $service;

    public function __construct(DingTalkService $service)
    {
        $this->service = $service;
        $this->setMessage();
    }

    public function setMessage(): void
    {
        $this->message = [
            'feedCard' => [
                'links' => [],
            ],
            'msgtype' => 'feedCard',
        ];
    }

    public function addLinks($title, $messageUrl, $picUrl): FeedCard
    {
        $this->message['feedCard']['links'][] = [
            'title' => $title,
            'messageURL' => $messageUrl,
            'picURL' => $picUrl,
        ];

        return $this;
    }

    public function send(): bool|ResponseInterface
    {
        $this->service->setMessage($this);

        return $this->service->send();
    }
}
