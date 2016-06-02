<?php

namespace Socket;

class Client extends Socket implements SocketInterface
{
    /**
     * Input stream
     * @var resource $stdIn
     */
    protected $stdIn;

    /**
     * Error message if the connection fails
     * @var string $errStr
     */
    protected $errStr;

    /**
     * Error number if connection fails
     * @var integer $errNo
     */
    protected $errNo;

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
        $this->socket = stream_socket_client($this->address, $this->errNo, $this->errStr);
        $this->stdIn = fopen('php://stdin', 'r');
        if (!$this->socket) {
            $this->notify("$this->errStr ($this->errNo)");
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
            if ($stream == $this->stdIn) {
                $message = trim(fgets($this->stdIn));
                $this->sendMessage($this->socket, $message);
            } else {
                $message = $this->getMessage($stream);
                $this->notify($message);
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
        $this->notify("Welcome to $this->address");
        while (!feof($this->socket)) {
            $streams = array($this->socket, $this->stdIn);
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
