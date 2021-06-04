<?php
declare(strict_types = 1);

class Server
{
    protected string $host = '127.0.0.1';

    protected int $port = 9502;

    protected int $backlog = 128;

    protected Socket $socket;

    protected function __construct(string $host = '127.0.0.1', int $port = 8001, int $backlog = 128)
    {
        $this->host    = $host;
        $this->port    = $port;
        $this->backlog = $backlog;
    }

    public function socketCreate()
    {
        //创建socket套接字
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {
            //创建失败抛出异常，socket_last_error获取最后一次socket操作错误码，socket_strerror打印出对应错误码所对应的可读性描述
            throw new Exception(socket_strerror(socket_last_error($this->socket)));
        } else {
            echo "create socket successful\n";
        }
    }
}
