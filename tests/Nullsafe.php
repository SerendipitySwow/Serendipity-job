<?php
declare( strict_types = 1 );


class Socket1
{
    public $socket = null;

    /**
     * @return null
     */
    public function check ()
    {
        return (bool)$this->socket?->ischeck();
    }
}


