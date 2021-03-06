<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Dingtalk;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Psr\Http\Message\ResponseInterface;
use SwowCloud\Job\Dingtalk\Http\Client;
use SwowCloud\Job\Dingtalk\Messages\ActionCard;
use SwowCloud\Job\Dingtalk\Messages\FeedCard;
use SwowCloud\Job\Dingtalk\Messages\Message;

class DingTalk
{
    /**
     * @var
     */
    protected mixed $config;

    protected string $robot = 'default';

    protected DingTalkService $dingTalkService;

    protected ?Client $client;

    /**
     * DingTalk constructor.
     *
     * @param $config
     */
    public function __construct($config = null, Client $client = null)
    {
        $this->config = $config ?? ApplicationContext::getContainer()->get(ConfigInterface::class)->get('dingtalk');
        $this->client = $client;
        $this->with();
    }

    /**
     * @return $this
     */
    public function with(string $robot = 'default'): self
    {
        $this->robot = $robot;
        $this->dingTalkService = new DingTalkService($this->config[$robot], $this->client);

        return $this;
    }

    /**
     * @return false|ResponseInterface
     */
    public function text(string $content = ''): bool|ResponseInterface
    {
        return $this->dingTalkService
            ->setTextMessage($content)
            ->send();
    }

    /**
     * @param $title
     * @param $text
     */
    public function action($title, $text): ActionCard|Message
    {
        return $this->dingTalkService
            ->setActionCardMessage($title, $text);
    }

    /**
     * @return $this
     */
    public function at(array $mobiles = [], bool $atAll = false): self
    {
        $this->dingTalkService
            ->setAt($mobiles, $atAll);

        return $this;
    }

    /**
     * @param $title
     * @param $text
     * @param $url
     *
     * @return false|ResponseInterface
     */
    public function link($title, $text, $url, string $picUrl = ''): bool|ResponseInterface
    {
        return $this->dingTalkService
            ->setLinkMessage($title, $text, $url, $picUrl)
            ->send();
    }

    /**
     * @param $title
     * @param $markdown
     *
     * @return false|ResponseInterface
     */
    public function markdown($title, $markdown): bool|ResponseInterface
    {
        return $this->dingTalkService
            ->setMarkdownMessage($title, $markdown)
            ->send();
    }

    /**
     * @param $title
     * @param $markdown
     */
    public function actionCard($title, $markdown, int $hideAvatar = 0, int $btnOrientation = 0): ActionCard|Message
    {
        return $this->dingTalkService
            ->setActionCardMessage($title, $markdown, $hideAvatar, $btnOrientation);
    }

    public function feed(): FeedCard|Message
    {
        return $this->dingTalkService
            ->setFeedCardMessage();
    }
}
