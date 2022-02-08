<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Console;

use Hyperf\Utils\Filesystem\Filesystem;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputOption;

final class ConfigPublishCommand extends Command
{
    /**
     * @var string
     */
    public static $defaultName = 'config:publish';

    protected const COMMAND_PROVIDER_NAME = 'Config';

    private ContainerInterface $container;

    protected Filesystem $filesystem;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Publish any publishable configs from vendor packages.')
            ->setDefinition([
                new InputOption(
                    'id',
                    'i',
                    InputOption::VALUE_REQUIRED,
                    'The id of the package you want to publish.',
                    null
                ),
            ]);
    }

    protected function bootStrap(): void
    {
    }

    public function handle(): int
    {
        $this->filesystem = $this->container->get(Filesystem::class);
        $id = $this->input->getOption('id');

        $this->copy($id);

        return SymfonyCommand::SUCCESS;
    }

    protected function copy(string $id): int
    {
        $source = __DIR__ . "/../../config/autoload/{$id}.php";
        $destination = BASE_PATH . "/config/autoload/{$id}.php";

        if ($this->filesystem->exists($destination)) {
            $this->output->writeln(sprintf('<fg=red>[%s] already exists.</>', $destination));

            return 0;
        }

        if (!$this->filesystem->exists($dirname = dirname($destination))) {
            $this->filesystem->makeDirectory($dirname, 0755, true);
        }

        if ($this->filesystem->isDirectory($source)) {
            $this->filesystem->copyDirectory($source, $destination);
        } else {
            $this->filesystem->copy($source, $destination);
        }

        $this->output->writeln(sprintf('<fg=green> Publishes [%s] successfully.</>', $id));

        return 0;
    }
}
