<?php

namespace Socket;

class Server extends Socket implements SocketInterface
{
    protected $connects = array();

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
        $this->inform('WELCOME TO SERVER!');
        $this->socket = stream_socket_server($this->address, $errno, $errstr);
    }

    /**
     * socket in wait state
     * @return mixed
     */
    public function onListen()
    {
        if (empty($this->socket)) {
            $this->connect();
        }
        while (true) {
            $streams = $this->connects;
            $write = $except = null;
            $streams[] = $this->socket;

            if (!stream_select($streams, $write, $except, null)) {
                break;
            }

            if (in_array($this->socket, $streams)) {
                if ($connect = stream_socket_accept($this->socket, -1)) {
                    $this->connects[] = $connect;
                    unset($streams[array_search($this->socket, $streams)]);
                    $this->onOpen($connect, "Welcome to my socket\n");
                    $this->inform("New connect!");
                }
            }

            $this->onChange($streams);
        }
    }

    /**
     * Listen resource
     * @param $resources
     * @return mixed
     */
    public function onChange($resources)
    {
        foreach ($resources as $resource) {
            $data = $this->getMessage($resource);
            
            if ($data == false) {
                $connectKey = array_search($resource, $this->connects);
                unset($this->connects[$connectKey]);
                $this->inform("$resource disconnected");
                break;
            }
            
            $this->onMessage($resource, $data);
            $this->sendMessageToAllConnect($resource, $data);
        }
    }

    /**
     * Send message to all client except current client
     * @param $currentConnect
     * @param $message
     */
    public function sendMessageToAllConnect($currentConnect, $message)
    {
        foreach ($this->connects as $connect) {
            if ($connect != $currentConnect) {
                $this->sendMessage($connect, "$currentConnect say: $message");
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
        fwrite($resource, $message, self::LIMIT);
    }    
    
    public function start()
    {
        $this->onListen();
    }
}
