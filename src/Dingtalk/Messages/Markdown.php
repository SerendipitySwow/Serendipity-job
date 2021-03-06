<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Dingtalk\Messages;

class Markdown extends Message
{
    public function __construct($title, $markdown)
    {
        $this->setMessage($title, $markdown);
    }

    public function setMessage($title, $markdown): void
    {
        $this->message = [
            'msgtype' => 'markdown',
            'markdown' => [
                'title' => $title,
                'text' => $markdown,
            ],
        ];
    }
}
