<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

error_reporting(E_ALL ^ E_NOTICE);
ob_implicit_flush();

// 地址与接口，即创建socket时需要服务器的IP和端口
$sk = new Sock('127.0.0.1', 8000);

// 对创建的socket循环进行监听，处理数据
$sk->run();

// 下面是sock类
class Sock
{
    public $sockets; // socket的连接池，即client连接进来的socket标志

    public $users;   // 所有client连接进来的信息，包括socket、client名字等

    public $master;  // socket的resource，即前期初始化socket时返回的socket资源

    private $sda = [];   // 已接收的数据

    private $slen = [];  // 数据总长度

    private $sjen = [];  // 接收数据的长度

    private $ar = [];    // 加密key

    private $n = [];

    public function __construct($address, $port)
    {
        // 创建socket并把保存socket资源在$this->master
        $this->master = $this->WebSocket($address, $port);

        // 创建socket连接池
        $this->sockets = [$this->master];
    }

    // 对创建的socket循环进行监听，处理数据
    public function run()
    {
        // 死循环，直到socket断开
        while (true) {
            $changes = $this->sockets;
            $write = null;
            $except = null;

            /*
            //这个函数是同时接受多个连接的关键，我的理解它是为了阻塞程序继续往下执行。
            socket_select ($sockets, $write = NULL, $except = NULL, NULL);
            $sockets可以理解为一个数组，这个数组中存放的是文件描述符。当它有变化（就是有新消息到或者有客户端连接/断开）时，socket_select函数才会返回，继续往下执行。
            $write是监听是否有客户端写数据，传入NULL是不关心是否有写变化。
            $except是$sockets里面要被排除的元素，传入NULL是”监听”全部。
            最后一个参数是超时时间
            如果为0：则立即结束
            如果为n>1: 则最多在n秒后结束，如遇某一个连接有新动态，则提前返回
            如果为null：如遇某一个连接有新动态，则返回
            */
            socket_select($changes, $write, $except, null);
            foreach ($changes as $sock) {
                // 如果有新的client连接进来，则
                if ($sock == $this->master) {
                    // 接受一个socket连接
                    $client = socket_accept($this->master);

                    // 给新连接进来的socket一个唯一的ID
                    $key = uniqid();
                    $this->sockets[] = $client;  // 将新连接进来的socket存进连接池
                    $this->users[$key] = [
                        'socket' => $client,  // 记录新连接进来client的socket信息
                        'shou' => false,       // 标志该socket资源没有完成握手
                    ];
                // 否则1.为client断开socket连接，2.client发送信息
                } else {
                    $len = 0;
                    $buffer = '';
                    // 读取该socket的信息，注意：第二个参数是引用传参即接收数据，第三个参数是接收数据的长度
                    do {
                        $l = socket_recv($sock, $buf, 1000, 0);
                        $len += $l;
                        $buffer .= $buf;
                    } while ($l == 1000);

                    // 根据socket在user池里面查找相应的$k,即健ID
                    $k = $this->search($sock);

                    // 如果接收的信息长度小于7，则该client的socket为断开连接
                    if ($len < 7) {
                        // 给该client的socket进行断开操作，并在$this->sockets和$this->users里面进行删除
                        $this->send2($k);
                        continue;
                    }
                    // 判断该socket是否已经握手
                    if (!$this->users[$k]['shou']) {
                        // 如果没有握手，则进行握手处理
                        $this->woshou($k, $buffer);
                    } else {
                        // 走到这里就是该client发送信息了，对接受到的信息进行uncode处理
                        $buffer = $this->uncode($buffer, $k);
                        if ($buffer == false) {
                            continue;
                        }
                        // 如果不为空，则进行消息推送操作
                        $this->send($k, $buffer);
                    }
                }
            }
        }
    }

    // 指定关闭$k对应的socket
    public function close($k)
    {
        // 断开相应socket
        socket_close($this->users[$k]['socket']);
        // 删除相应的user信息
        unset($this->users[$k]);
        // 重新定义sockets连接池
        $this->sockets = [$this->master];
        foreach ($this->users as $v) {
            $this->sockets[] = $v['socket'];
        }
        // 输出日志
        $this->e("key:{$k} close");
    }

    // 根据sock在users里面查找相应的$k
    public function search($sock)
    {
        foreach ($this->users as $k => $v) {
            if ($sock == $v['socket']) {
                return $k;
            }
        }

        return false;
    }

    // 传相应的IP与端口进行创建socket操作
    public function WebSocket($address, $port)
    {
        $server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1); // 1表示接受所有的数据包
        socket_bind($server, $address, $port);
        socket_listen($server);
        $this->e('Server Started : ' . date('Y-m-d H:i:s'));
        $this->e('Listening on   : ' . $address . ' port ' . $port);

        return $server;
    }

    /*
    * 函数说明：对client的请求进行回应，即握手操作
    * @$k clien的socket对应的健，即每个用户有唯一$k并对应socket
    * @$buffer 接收client请求的所有信息
    */
    public function woshou($k, $buffer)
    {
        // 截取Sec-WebSocket-Key的值并加密，其中$key后面的一部分258EAFA5-E914-47DA-95CA-C5AB0DC85B11字符串应该是固定的
        $buf = substr($buffer, strpos($buffer, 'Sec-WebSocket-Key:') + 18);
        $key = trim(substr($buf, 0, strpos($buf, "\r\n")));
        $new_key = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        // 按照协议组合信息进行返回
        $new_message = "HTTP/1.1 101 Switching Protocols\r\n";
        $new_message .= "Upgrade: websocket\r\n";
        $new_message .= "Sec-WebSocket-Version: 13\r\n";
        $new_message .= "Connection: Upgrade\r\n";
        $new_message .= 'Sec-WebSocket-Accept: ' . $new_key . "\r\n\r\n";
        socket_write($this->users[$k]['socket'], $new_message, strlen($new_message));

        // 对已经握手的client做标志
        $this->users[$k]['shou'] = true;

        return true;
    }

    // 解码函数
    public function uncode($str, $key)
    {
        $mask = [];
        $data = '';
        $msg = unpack('H*', $str);
        $head = substr($msg[1], 0, 2);
        if ($head == '81' && !isset($this->slen[$key])) {
            $len = substr($msg[1], 2, 2);
            $len = hexdec($len); // 把十六进制的转换为十进制
            if (substr($msg[1], 2, 2) == 'fe') {
                $len = substr($msg[1], 4, 4);
                $len = hexdec($len);
                $msg[1] = substr($msg[1], 4);
            } elseif (substr($msg[1], 2, 2) == 'ff') {
                $len = substr($msg[1], 4, 16);
                $len = hexdec($len);
                $msg[1] = substr($msg[1], 16);
            }
            $mask[] = hexdec(substr($msg[1], 4, 2));
            $mask[] = hexdec(substr($msg[1], 6, 2));
            $mask[] = hexdec(substr($msg[1], 8, 2));
            $mask[] = hexdec(substr($msg[1], 10, 2));
            $s = 12;
            $n = 0;
        } elseif ($this->slen[$key] > 0) {
            $len = $this->slen[$key];
            $mask = $this->ar[$key];
            $n = $this->n[$key];
            $s = 0;
        }

        $e = strlen($msg[1]) - 2;
        for ($i = $s; $i <= $e; $i += 2) {
            $data .= chr($mask[$n % 4] ^ hexdec(substr($msg[1], $i, 2)));
            $n++;
        }
        $dlen = strlen($data);

        if ($len > 255 && $len > $dlen + intval($this->sjen[$key])) {
            $this->ar[$key] = $mask;
            $this->slen[$key] = $len;
            $this->sjen[$key] = $dlen + intval($this->sjen[$key]);
            $this->sda[$key] = $this->sda[$key] . $data;
            $this->n[$key] = $n;

            return false;
        }
        unset($this->ar[$key],$this->slen[$key],$this->sjen[$key],$this->n[$key]);
        $data = $this->sda[$key] . $data;
        unset($this->sda[$key]);

        return $data;
    }

    // 与uncode相对
    public function code($msg)
    {
        $frame = [];
        $frame[0] = '81';
        $len = strlen($msg);
        if ($len < 126) {
            $frame[1] = $len < 16 ? '0' . dechex($len) : dechex($len);
        } elseif ($len < 65025) {
            $s = dechex($len);
            $frame[1] = '7e' . str_repeat('0', 4 - strlen($s)) . $s;
        } else {
            $s = dechex($len);
            $frame[1] = '7f' . str_repeat('0', 16 - strlen($s)) . $s;
        }
        $frame[2] = $this->ord_hex($msg);
        $data = implode('', $frame);

        return pack('H*', $data);
    }

    public function ord_hex($data)
    {
        $msg = '';
        $l = strlen($data);
        for ($i = 0; $i < $l; $i++) {
            $msg .= dechex(ord($data[$i]));
        }

        return $msg;
    }

    // 用户加入或client发送信息
    public function send($k, $msg)
    {
        // 将查询字符串解析到第二个参数变量中，以数组的形式保存如：parse_str("name=Bill&age=60",$arr)
        parse_str($msg, $g);
        $ar = [];

        if ($g['type'] == 'add') {
            // 第一次进入添加聊天名字，把姓名保存在相应的users里面
            $this->users[$k]['name'] = $g['ming'];
            $ar['type'] = 'add';
            $ar['name'] = $g['ming'];
            $key = 'all';
        } else {
            // 发送信息行为，其中$g['key']表示面对大家还是个人，是前段传过来的信息
            $ar['nrong'] = $g['nr'];
            $key = $g['key'];
        }
        // 推送信息
        $this->send1($k, $ar, $key);
    }

    // 对新加入的client推送已经在线的client
    public function getusers()
    {
        $ar = [];
        foreach ($this->users as $k => $v) {
            $ar[] = ['code' => $k, 'name' => $v['name']];
        }

        return $ar;
    }

    // $k 发信息人的socketID $key接受人的 socketID ，根据这个socketID可以查找相应的client进行消息推送，即指定client进行发送
    public function send1($k, $ar, $key = 'all')
    {
        $ar['code1'] = $key;
        $ar['code'] = $k;
        $ar['time'] = date('m-d H:i:s');
        // 对发送信息进行编码处理
        $str = $this->code(json_encode($ar));
        // 面对大家即所有在线者发送信息
        if ($key == 'all') {
            $users = $this->users;
            // 如果是add表示新加的client
            if ($ar['type'] == 'add') {
                $ar['type'] = 'madd';
                $ar['users'] = $this->getusers();        // 取出所有在线者，用于显示在在线用户列表中
                $str1 = $this->code(json_encode($ar)); // 单独对新client进行编码处理，数据不一样
                // 对新client自己单独发送，因为有些数据是不一样的
                socket_write($users[$k]['socket'], $str1, strlen($str1));
                // 上面已经对client自己单独发送的，后面就无需再次发送，故unset
                unset($users[$k]);
            }
            // 除了新client外，对其他client进行发送信息。数据量大时，就要考虑延时等问题了
            foreach ($users as $v) {
                socket_write($v['socket'], $str, strlen($str));
            }
        } else {
            // 单独对个人发送信息，即双方聊天
            socket_write($this->users[$k]['socket'], $str, strlen($str));
            socket_write($this->users[$key]['socket'], $str, strlen($str));
        }
    }

    // 用户退出向所用client推送信息
    public function send2($k)
    {
        $this->close($k);
        $ar['type'] = 'rmove';
        $ar['nrong'] = $k;
        $this->send1(false, $ar, 'all');
    }

    // 记录日志
    public function e($str)
    {
        // $path=dirname(__FILE__).'/log.txt';
        $str = $str . "\n";
        // error_log($str,3,$path);
        // 编码处理
        echo iconv('utf-8', 'gbk//IGNORE', $str);
    }
}
