<?php


class  WebsocketServer
{
    private $port = 8080;
    private $addr = "127.0.0.1";
    private $socket_handle;
    private $back_log = 10;
    private $websocket_key;
    private $current_message_length;

    private $is_shakehanded = false;
    private $mask_key;


    public function __construct($port = 8080, $addr = "127.0.0.1", $back_log = 10)
    {
        $this->port = $port;
        $this->addr = $addr;
        $this->back_log = $back_log;
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
        } else {
            echo "create socket successful\n";
        }
    }


    /**
     * @throws Exception
     */
    private function bindAddr()
    {
        if (!socket_bind($this->socket_handle, $this->addr, $this->port)) {
            throw new Exception(socket_strerror(socket_last_error($this->socket_handle)));
        } else {
            echo "bind addr successful\n";
        }
    }

    private function listen()
    {
        if (!socket_listen($this->socket_handle, $this->back_log)) {
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
        while (true) {
            $client_socket_handle = socket_accept($this->socket_handle);
            if (!$client_socket_handle) {
                throw new Exception(socket_strerror(socket_last_error($this->socket_handle)));
            } else {
                //与客户端握手
                if (!$this->is_shakehanded) {
                    $this->shakehand($client_socket_handle);
                    $this->is_shakehanded = true;
                }
                //等待客户端新传输的数据
                if (!socket_recv($client_socket_handle, $buffer, 1000, 0)) {
                    throw new Exception(socket_strerror(socket_last_error($client_socket_handle)));
                }
                //解析消息的长度
                $payload_length = ord($buffer[1]) & 0x7f;//第二个字符的低7位
                if ($payload_length >= 0 && $payload_length < 125) {
                    $this->current_message_length = $payload_length;
                    $payload_type = 1;
                    echo $payload_length . "\n";
                } else if ($payload_length == 126) {
                    $payload_type = 2;
                    $this->current_message_length = ((ord($buffer[2]) & 0xff) << 8) | (ord($buffer[3]) & 0xff);
                    echo $this->current_message_length;
                } else {
                    $payload_type = 3;
                    $this->current_message_length =
                        (ord($buffer[2]) << 56)
                        | (ord($buffer[3]) << 48)
                        | (ord($buffer[4]) << 40)
                        | (ord($buffer[5]) << 32)
                        | (ord($buffer[6]) << 24)
                        | (ord($buffer[7]) << 16)
                        | (ord($buffer[8]) << 8)
                        | (ord($buffer[9]) << 0);
                }
                //解析掩码，这个必须有的，掩码总共4个字节
                $mask_key_offset = ($payload_type == 1 ? 0 : ($payload_type == 2 ? 2 : 8)) + 2;
                $this->mask_key = substr($buffer, $mask_key_offset, 4);
                //获取加密的内容
                $real_message = substr($buffer, $mask_key_offset + 4);
                $i = 0;
                $parsed_ret = '';
                //解析加密的数据
                while ($i < strlen($real_message)) {
                    $parsed_ret .= chr((ord($real_message[$i]) ^ ord(($this->mask_key[$i % 4]))));
                    $i++;
                }
                echo $parsed_ret . "\n";
                //把解析出来的数据直接返回给客户端
                $this->echoContentToClient($client_socket_handle, $parsed_ret);
            }
        }
    }

    /**
     * @param $client_socket
     * @param $content
     * @throws Exception
     */
    private function echoContentToClient($client_socket, $content)
    {
        $len = strlen($content);
        //第一个字节
        $char_seq = chr(0x80 | 1);

        $b_2 = 0;
        //fill length
        if ($len > 0 && $len <= 125) {
            $char_seq .= chr(($b_2 | $len));
        } else if ($len <= 65535) {
            $char_seq .= chr(($b_2 | 126));
            $char_seq .= (chr($len >> 8) . chr($len & 0xff));
        } else {
            $char_seq .= chr(($b_2 | 127));
            $char_seq .=
                (chr($len >> 56)
                    . chr($len >> 48)
                    . chr($len >> 40)
                    . chr($len >> 32)
                    . chr($len >> 24)
                    . chr($len >> 16)
                    . chr($len >> 8)
                    . chr($len >> 0));
        }
        $char_seq .= $content;
        $this->writeToSocket($client_socket, $char_seq);
    }

    private function writeToSocket($client_socket, $content)
    {
        $ret = socket_write($client_socket, $content, strlen($content));
        if (!$ret) {
            throw new Exception(socket_last_error($client_socket));
        }
    }

    /**
     * @param $client_socket_handle
     * @throws Exception
     */
    private function shakehand($client_socket_handle)
    {
        if (socket_recv($client_socket_handle, $buffer, 1000, 0) < 0) {
            throw new Exception(socket_strerror(socket_last_error($this->socket_handle)));
        }
        while (1) {
            if (preg_match("/([^\r]+)\r\n/", $buffer, $match) > 0) {
                $content = $match[1];
                if (strncmp($content, "Sec-WebSocket-Key", strlen("Sec-WebSocket-Key")) == 0) {
                    $this->websocket_key = trim(substr($content, strlen("Sec-WebSocket-Key:")), " \r\n");
                }
                $buffer = substr($buffer, strlen($content) + 2);
            } else {
                break;
            }
        }
        //响应客户端
        $this->writeToSocket($client_socket_handle, "HTTP/1.1 101 Switching Protocol\r\n");
        $this->writeToSocket($client_socket_handle, "Upgrade: websocket\r\n");
        $this->writeToSocket($client_socket_handle, "Connection: upgrade\r\n");
        $this->writeToSocket($client_socket_handle, "Sec-WebSocket-Accept:" . $this->calculateResponseKey() . "\r\n");
        $this->writeToSocket($client_socket_handle, "Sec-WebSocket-Version: 13\r\n\r\n");
    }

    private function calculateResponseKey()
    {
        $GUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

        $result = base64_encode(sha1($this->websocket_key . $GUID, true));
        return $result;
    }

    public function startServer()
    {
        try {
            $this->createSocket();
            $this->bindAddr();
            $this->listen();
            $this->accept();
        } catch (Exception $exception) {
            echo $exception->getMessage() . "\n";
        }
    }
}

setlocale(LC_ALL, "US");
$server = new WebsocketServer();
$server->startServer();
