<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Console;

use Psr\Container\ContainerInterface;
use SwowCloud\Job\Kernel\MatrixService;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Terminal;

class MatrixCommand extends Command
{
    private MatrixService $matrixService;

    // the name of the command (the part after "bin/console")
    public static $defaultName = 'matrix:start';

    public function __construct(ContainerInterface $container)
    {
        $this->matrixService = new MatrixService();
        parent::__construct();
    }

    protected function bootStrap(): void
    {
    }

    public function handle(): int
    {
        // Initialize Terminal
        $this->matrix();

        return SymfonyCommand::SUCCESS;
    }

    public function matrix(): void
    {
        $terminal = new Terminal();
        $width = $terminal->getWidth();
        $height = $terminal->getHeight();
        $cursor = new Cursor($this->output);
        $cursor->hide();

        // Print Welcome Message
        $cursor->clearScreen();
        $cursor->moveToPosition(0, 0);
        $this->output->writeln('<fg=green;options=bold>Welcome to the Matrix ...</>');
        sleep(3);

        // Initialize Matrix
        $cursor->clearScreen();
        $initLine = $this->matrixService->initMatrix($width);
        $arrayLine = $this->matrixService->makeArrayLine($width, $height, $initLine);

        // Make matrix
        $x = 0;
        while (true) {
            $a = 0;
            while ($a < $width - 1) {
                $a++;
                $cursor->moveToPosition($arrayLine[$a]['col'], $arrayLine[$a]['row']);
                $this->output->write('<fg=green;options=bold>' . $arrayLine[$a]['string'] . '</>');
            }
            $x++;
            usleep(60000);
            $arrayLine = $this->matrixService->makeArrayLine($width, $height, $arrayLine);
        }
    }
}
