<?php
declare(strict_types = 1);

set_time_limit(0);

class HttpServer
{
    private $ip = '127.0.0.1';
    private $port = 9996;

    private $_socket = null;

    public function __construct()
    {
        $this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->_socket === false) {
            die(socket_strerror(socket_last_error($this->_socket)));
        }
    }

    public function run()
    {
        socket_bind($this->_socket, $this->ip, $this->port);
        socket_listen($this->_socket, 5);
        while (true) {
            $socketAccept = socket_accept($this->_socket);
            $request      = socket_read($socketAccept, 1024);
            echo $request;
            socket_write($socketAccept, 'HTTP/1.1 200 OK' . PHP_EOL);
            socket_write($socketAccept, 'Date:' . date('Y-m-d H:i:s') . PHP_EOL);

            $fileName = $this->getUri($request);
            $fileExt  = preg_replace('/^.*\.(\w+)$/', '$1', $fileName);
            $fileName = __DIR__ . '/' . $fileName;
            switch ($fileExt) {
                case "html":
                    //set content type
                    socket_write($socketAccept, 'Content-Type: text/html' . PHP_EOL);
                    socket_write($socketAccept, '' . PHP_EOL);
                    $fileContent = file_get_contents($fileName);
                    socket_write($socketAccept, $fileContent, strlen($fileContent));
                    break;
                case "jpg":
                    socket_write($socketAccept, 'Content-Type: image/jpeg' . PHP_EOL);
                    socket_write($socketAccept, '' . PHP_EOL);
                    $fileContent = file_get_contents($fileName);
                    socket_write($socketAccept, $fileContent, strlen($fileContent));
                    break;
            }
            socket_write($socketAccept, 'web serving', strlen('web serving'));
            socket_close($socketAccept);
        }
    }

    protected function getUri($request = '')
    {
        $arrayRequest = explode(PHP_EOL, $request);
        $line         = $arrayRequest[0];
        $file         = trim(preg_replace('/(\w+)\s\/(.*)\sHTTP\/1.1/i', '$2', $line));
        return $file;
    }

    public function close()
    {
        socket_close($this->_socket);
    }

}
$httpServer = new HttpServer();
$httpServer->run();

