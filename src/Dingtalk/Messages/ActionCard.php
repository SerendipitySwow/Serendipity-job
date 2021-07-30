<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Dingtalk\Messages;

use Serendipity\Job\Dingtalk\DingTalkService;

class ActionCard extends Message
{
    protected DingTalkService $service;

    public function __construct(DingTalkService $service, $title, $markdown, $hideAvatar = 0, $btnOrientation = 0)
    {
        $this->service = $service;
        $this->setMessage($title, $markdown, $hideAvatar, $btnOrientation);
    }

    public function setMessage($title, $markdown, $hideAvatar = 0, $btnOrientation = 0): void
    {
        $this->message = [
            'msgtype' => 'actionCard',
            'actionCard' => [
                'title' => $title,
                'text' => $markdown,
                'hideAvatar' => $hideAvatar,
                'btnOrientation' => $btnOrientation,
            ],
        ];
    }

    public function single($title, $url): ActionCard
    {
        $this->message['actionCard']['singleTitle'] = $title;
        $this->message['actionCard']['singleURL'] = $url;
        $this->service->setMessage($this);

        return $this;
    }

    public function addButtons($title, $url): ActionCard
    {
        $this->message['actionCard']['btns'][] = [
            'title' => $title,
            'actionURL' => $url,
        ];

        return $this;
    }

    public function send()
    {
        $this->service->setMessage($this);

        return $this->service->send();
    }
}
