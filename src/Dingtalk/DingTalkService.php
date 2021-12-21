<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Dingtalk;

use Hyperf\Utils\ApplicationContext;
use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\ResponseInterface;
use SwowCloud\Contract\StdoutLoggerInterface;
use SwowCloud\Job\Dingtalk\Http\Client;
use SwowCloud\Job\Dingtalk\Messages\ActionCard;
use SwowCloud\Job\Dingtalk\Messages\FeedCard;
use SwowCloud\Job\Dingtalk\Messages\Link;
use SwowCloud\Job\Dingtalk\Messages\Markdown;
use SwowCloud\Job\Dingtalk\Messages\Message;
use SwowCloud\Job\Dingtalk\Messages\Text;
use Throwable;
use function SwowCloud\Job\Kernel\serendipity_format_throwable;

class DingTalkService
{
    protected mixed $config;

    protected Message $message;

    protected array $mobiles = [];

    protected bool $atAll = false;

    protected Client $client;

    public function __construct($config, Client $client = null)
    {
        $this->config = $config;
        $this->setTextMessage('null');

        if ($client !== null) {
            $this->client = $client;

            return;
        }
        $this->client = $this->createClient($config);
    }

    public function setMessage(Message $message): void
    {
        $this->message = $message;
    }

    #[Pure]
    public function getMessage(): array
    {
        return $this->message->getMessage();
    }

    public function setAt(array $mobiles = [], bool $atAll = false): void
    {
        $this->mobiles = $mobiles;
        $this->atAll = $atAll;
        $this->message?->sendAt($mobiles, $atAll);
    }

    /**
     * @param $content
     *
     * @return $this
     */
    public function setTextMessage($content): self
    {
        $this->message = new Text($content);
        $this->message->sendAt($this->mobiles, $this->atAll);

        return $this;
    }

    /**
     * @param $title
     * @param $text
     * @param $messageUrl
     *
     * @return $this
     */
    public function setLinkMessage($title, $text, $messageUrl, string $picUrl = ''): self
    {
        $this->message = new Link($title, $text, $messageUrl, $picUrl);
        $this->message->sendAt($this->mobiles, $this->atAll);

        return $this;
    }

    /**
     * @param $title
     * @param $markdown
     *
     * @return $this
     */
    public function setMarkdownMessage($title, $markdown): self
    {
        $this->message = new Markdown($title, $markdown);
        $this->message->sendAt($this->mobiles, $this->atAll);

        return $this;
    }

    /**
     * @param $title
     * @param $markdown
     */
    public function setActionCardMessage($title, $markdown, int $hideAvatar = 0, int $btnOrientation = 0): ActionCard|Message
    {
        $this->message = new ActionCard($this, $title, $markdown, $hideAvatar, $btnOrientation);
        $this->message->sendAt($this->mobiles, $this->atAll);

        return $this->message;
    }

    public function setFeedCardMessage(): FeedCard|Message
    {
        $this->message = new FeedCard($this);
        $this->message->sendAt($this->mobiles, $this->atAll);

        return $this->message;
    }

    /**
     * @return false|ResponseInterface
     */
    public function send(): bool|ResponseInterface
    {
        if (!$this->config['enabled']) {
            return false;
        }
        try {
            return $this->client->send($this->message->getBody());
        } catch (Throwable $e) {
            ApplicationContext::getContainer()->get(StdoutLoggerInterface::class)->error(serendipity_format_throwable($e));

            return false;
        }
    }

    /**
     * @param $config
     */
    protected function createClient($config): Client
    {
        return new Client($config);
    }
}
