<?php

namespace Socket;

class Server extends Socket
{

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
        $this->notify('WELCOME TO SERVER!');
        $this->socket = stream_socket_server($this->address, $errNo, $errStr);
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
            $streams = $this->resources;
            $write = $except = null;
            $socket = $this->socket;
            $streams[] = $socket;


            if (!stream_select($streams, $write, $except, null)) {
                break;
            }

            if (in_array($socket, $streams)) {
                if ($newResource = stream_socket_accept($socket, -1)) {
                    $this->resources[] = $newResource;

                    unset($streams[array_search($socket, $streams)]);
                    $this->sendMessage($newResource, "Welcome to my socket\n");
                    $this->notify("New connect!");
                }
            }

            $this->onChange($streams);
        }
    }

    /**
     * Listen resource
     * @param $changedResources
     * @return mixed
     */
    public function onChange($changedResources)
    {
        foreach ($changedResources as $resource) {
            $data = $this->getMessage($resource);
            
            if ($data == false) {
                $resourceKey = array_search($resource, $this->resources);
                unset($this->resources[$resourceKey]);
                $this->notify("$resource disconnected");
                break;
            }
            
            $this->onMessage($resource, $data);
            $this->sendMessageToAllConnect($resource, $data);
        }
    }

    /**
     * Send message to all client except current client
     * @param $sender
     * @param $message
     */
    public function sendMessageToAllConnect($sender, $message)
    {
        foreach ($this->resources as $resource) {
            if ($resource != $sender) {
                $this->sendMessage($resource, "$sender say: $message");
            }
        }
    }
    
    public function start()
    {
        $this->onListen();
    }
}
