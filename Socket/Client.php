<?php

namespace Socket;

class Client extends Socket implements SocketInterface
{
    /**
     * Input stream
     * @var resource $stdin
     */
    protected $stdin;

    /**
     * Error message if the connection fails
     * @var string $errstr
     */
    protected $errstr;

    /**
     * Error number if connection fails
     * @var integer $errno
     */
    protected $errno;

    public function __construct($host, $port)
    {
        parent::__construct($host, $port);
    }

    /**
     * Connect to host
     * @return mixed
     */
    public function connect()
    {
        $this->socket = stream_socket_client($this->address, $this->errno, $this->errstr);
        $this->stdin = fopen('php://stdin', 'r');
        if (!$this->socket) {
            $this->inform("$this->errstr ($this->errno)");
        }
    }

    /**
     * Listen resource
     * @param $resources
     * @return mixed
     */
    public function onChange($resources)
    {
        foreach ($resources as $stream) {
            if ($stream == $this->stdin) {
                $msg = trim(fgets($this->stdin));
                $this->sendMessage($this->socket, $msg);
            } else {
                $msg = $this->getMessage($stream);
                $this->inform($msg);
            }
        }
    }

    /**
     * Send message to resource
     * @param $resource
     * @param $message
     * @return mixed
     */
    public function sendMessage($resource, $message)
    {
        fwrite($resource, $message);
    }

    /**
     * socket in wait state
     * @return mixed
     */
    public function onListen()
    {
        if (!$this->socket) {
            $this->connect();
        }
        $this->inform("Welcome to $this->address");
        while (!feof($this->socket)) {
            $streams = array($this->socket, $this->stdin);
            $write = $except = null;

            if (!stream_select($streams, $write, $except, null)) {
                break;
            }

            $this->onChange($streams);
        }
        fclose($this->socket);

    }

    /**
     * start listen socket
     */
    public function start()
    {
        $this->onListen();
    }
}
