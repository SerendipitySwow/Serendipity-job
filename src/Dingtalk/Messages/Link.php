<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Dingtalk\Messages;

class Link extends Message
{
    public function __construct($title, $text, $messageUrl, $picUrl = '')
    {
        $this->setMessage($title, $text, $messageUrl, $picUrl);
    }

    public function setMessage($title, $text, $messageUrl, $picUrl = ''): void
    {
        $this->message = [
            'msgtype' => 'link',
            'link' => [
                'text' => $text,
                'title' => $title,
                'picUrl' => $picUrl,
                'messageUrl' => $messageUrl,
            ],
        ];
    }
}
