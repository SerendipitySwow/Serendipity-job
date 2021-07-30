<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Dingtalk\Messages;

use Serendipity\Job\Dingtalk\DingTalkService;

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

    public function send()
    {
        $this->service->setMessage($this);

        return $this->service->send();
    }
}
