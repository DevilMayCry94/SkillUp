<?php

namespace Socket;

interface SocketInterface
{

    /**
     * Connect to host
     * @return mixed
     */
    public function connect();

    /**
     * Listen resource
     * @param $resources
     * @return mixed
     */
    public function onChange($resources);

    /**
     * Send message to resource
     * @param $resource
     * @param $message
     * @return mixed
     */
    public function sendMessage($resource, $message);

    /**
     * socket in wait state
     * @return mixed
     */
    public function onListen();
}