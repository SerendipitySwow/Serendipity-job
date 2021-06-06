<?php
declare(strict_types = 1);

class Server
{
    protected string $host = '127.0.0.1';

    protected int $port = 9502;

    protected int $backlog = 128;

    protected Socket $socket;

    public function __construct(string $host = '127.0.0.1', int $port = 8001, int $backlog = 128)
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

    /**
     * @throws Exception
     */
    private function bindAddr()
    {
        if (!socket_bind($this->socket, $this->host, $this->port)) {
            throw new Exception(socket_strerror(socket_last_error($this->socket_handle)));
        } else {
            echo "bind addr successful\n";
        }
    }

    private function listen()
    {
        if (!socket_listen($this->socket, $this->backlog)) {
            throw new Exception(socket_strerror(socket_last_error($this->socket_handle)));
        } else {
            echo "socket  listen successful\n";
        }
    }

    /**
     * @throws Exception
     */
    private function accept()
    {
        $client_socket_handle = socket_accept($this->socket);
        if (!$client_socket_handle) {
            echo "socket_accept call failed\n";
            exit(1);
        }

        while (true) {
            $bytes_num = socket_recv($client_socket_handle, $buffer, 100, 0);
            if (!$bytes_num) {
                echo "socket_recv  failed\n";
                exit(1);
            }

            echo 'content from client:' . $buffer . "\n";
        }
    }

    public function startServer()
    {
        try {
            $this->socketCreate();
            $this->bindAddr();
            $this->listen();
            $this->accept();
        } catch (Exception $exception) {
            echo $exception->getMessage() . "\n";
        }
    }
}

$server = new Server();
$server->startServer();

