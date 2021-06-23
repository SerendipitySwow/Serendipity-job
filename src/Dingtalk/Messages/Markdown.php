<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Dingtalk\Messages;

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
