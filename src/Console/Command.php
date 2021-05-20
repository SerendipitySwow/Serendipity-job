<?php
declare(strict_types = 1);

namespace Serendipity\Job\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class Command extends SymfonyCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name;

    /**
     * @var InputInterface
     */
    protected InputInterface $input;

    /**
     * @var SymfonyStyle
     */
    protected SymfonyStyle $output;

    public function __construct(string $name = null)
    {
        if (!$name && $this->name) {
            $name = $this->name;
        }
        parent::__construct($name);
    }
}
