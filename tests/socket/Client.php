<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

class TcpClient
{
    private $server_port;

    private $server_addr;

    private $socket_handle;

    public function __construct($port = 8001, $addr = '127.0.0.1')
    {
        $this->server_addr = $addr;
        $this->server_port = $port;
    }

    /**
     * @throws Exception
     */
    private function createSocket()
    {
        //创建socket套接字
        $this->socket_handle = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket_handle) {
            //创建失败抛出异常，socket_last_error获取最后一次socket操作错误码，socket_strerror打印出对应错误码所对应的可读性描述
            throw new Exception(socket_strerror(socket_last_error($this->socket_handle)));
        }
        echo "create socket successful\n";
    }

    public function connectToServer()
    {
        $this->createSocket();
        if (!socket_connect($this->socket_handle, $this->server_addr, $this->server_port)) {
            echo socket_strerror(socket_last_error($this->socket_handle)) . "\n";
            exit(1);
        }
        while (true) {
            $data = fgets(STDIN);
            //如果用户输入quit，那么退出程序
            if (strcmp($data, 'quit') == 0) {
                break;
            }
            socket_write($this->socket_handle, $data);
        }
    }
}

$client = new TcpClient();
$client->connectToServer();
