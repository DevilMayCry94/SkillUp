<?php

namespace Socket;

abstract class Socket
{

    const CHAR_LIMIT = 10000;
    const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    /**
     * @var string $host
     */
    protected $host;

    /**
     * @var string $port
     */
    protected $port;

    /**
     * @var string $address
     */
    protected $address;

    /**
     *      * @var resource $socket
     */
    protected $socket;

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

    /**
     * contain all connect
     * @var array $connects
     */
    protected $resources = array();

    public function __construct($host, $port)
    {
        $this->host= $host;
        $this->port= $port;
        $this->address = "$host:$port";
    }

    /**
     * @param $connect
     * @return array|bool
     */
    protected function handshake($connect)
    {
        $firstRequestStr = fgets($connect);
        $requestHeader = explode(' ', $firstRequestStr);
        $requestInfo = array(
            'method' => $requestHeader[0],
            'uri' => $requestHeader[1]
        );

        while ($requestString = rtrim(fgets($connect))) {
            //
            if (preg_match('/\A(\S+): (.*)\z/', $requestString, $matches)) {
                $requestInfo[$matches[1]] = $matches[2];
            } else {
                break;
            }
        }

        $address = explode(':', stream_socket_get_name($connect, true));
        $requestInfo['host'] = $address[0];
        $requestInfo['port'] = $address[1];

        if (empty($requestInfo['Sec-WebSocket-Key'])) {
            return false;
        }

        $hash = $requestInfo['Sec-WebSocket-Key'] . self::GUID;
        $hash = sha1($hash);
        $SecWebSocketAccept = base64_encode(pack('H*', $hash));
        
        $response = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept:$SecWebSocketAccept\r\n\r\n";

        fwrite($connect, $response);

        return $requestInfo;
    }

    /**
     * @param $resource
     * @param $data
     */
    protected function onMessage($resource, $data)
    {
        $this->notify("$resource: $data");
    }

    /**
     * @param $resource
     * @return mixed
     */
    protected function getMessage($resource)
    {
        return fread($resource, self::CHAR_LIMIT);
    }


    /**
     * Send message to resource
     * @param $resource
     * @param $message
     * @return mixed
     */
    public function sendMessage($resource, $message)
    {
        fwrite($resource, $message, self::CHAR_LIMIT);
    }

    /**
     * notify
     * @param $message
     */
    public function notify($message)
    {
        echo "$message\n";
    }

    /**
     * Connect to host
     * @return mixed
     */
    abstract function connect();

    /**
     * Listen resource
     * @param $changedResources
     * @return mixed
     */
    abstract function onChange($changedResources);


    /**
     * socket in wait state
     * @return mixed
     */
    abstract function onListen();

}

