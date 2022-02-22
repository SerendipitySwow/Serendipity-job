<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel\Swow;

use League\CLImate\CLImate;

class Debugger extends \Swow\Debug\Debugger
{
    protected CLImate $climate;

    public function __construct()
    {
        $this->climate = new CLImate();
        parent::__construct();
    }

    /**
     * 终端输出增加颜色控制
     *TODO
     * @return $this
     */
    public function out(string $string = '', bool $newline = true, string $color = 'green'): static
    {
        $buffer = $this->climate->output->get('buffer');
        /* @noinspection PhpPossiblePolymorphicInvocationInspection */
        $buffer->clean();
        $this->climate->to('buffer')->{$color}($string);
        /* @noinspection PhpPossiblePolymorphicInvocationInspection */
        $this->output->write([rtrim($buffer->get(), "\n"), $newline ? "\n" : null]);

        return $this;
    }

    public function error(string $string = '', bool $newline = true): static
    {
        $this->out($string, $newline, 'error');

        return $this;
    }

    public function exception(string $string = '', bool $newline = true): static
    {
        $this->out($string, $newline, 'error');

        return $this;
    }
}
