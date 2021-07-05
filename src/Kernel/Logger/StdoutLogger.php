<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Logger;

use Psr\Log\LogLevel;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;
use function str_replace;

/**
 * Default logger for logging server start and requests.
 * PSR-3 logger implementation that logs to STDOUT, using a newline after each
 * message. Priority is ignored.
 */
class StdoutLogger implements StdoutLoggerInterface
{
    private ConfigInterface $config;

    /**
     * @var OutputInterface
     */
    private mixed $output;

    private array $tags = [
        'component',
    ];

    public function __construct(ConfigInterface $config, $output = null)
    {
        $this->config = $config;
        $this->output = $output ?: new ConsoleOutput();
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []): void
    {
        $config = $this->config->get(StdoutLoggerInterface::class, ['log_level' => []]);
        if (!in_array($level, $config['log_level'], true)) {
            return;
        }
        $keys = array_keys($context);
        $tags = [];
        foreach ($keys as $k => $key) {
            if (in_array($key, $this->tags, true)) {
                $tags[$key] = $context[$key];
                unset($keys[$k]);
            }
        }
        $search = array_map(static function ($key) {
            return sprintf('{%s}', $key);
        }, $keys);
        $message = str_replace($search, $context, $this->getMessage((string) $message, $level, $tags));
        $this->output->writeln($message);
    }

    protected function getMessage(string $message, string $level = LogLevel::INFO, array $tags = []): string
    {
        $tag = match ($level) {
            LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL => 'error',
            LogLevel::ERROR => 'fg=red',
            LogLevel::WARNING, LogLevel::NOTICE => 'comment',
            default => 'info',
        };

        $template = sprintf('<%s>[%s]</>', $tag, strtoupper($level));
        $implodedTags = '';
        foreach ($tags as $value) {
            $implodedTags .= (' [' . $value . ']');
        }

        return sprintf($template . $implodedTags . ' %s', $message);
    }
}
