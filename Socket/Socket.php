<?php

namespace Socket;

class Socket
{
    use Inform;

    const LIMIT = 10000;

    protected $host;
    protected $port;
    protected $address;
    protected $socket;

    protected $connects = array();

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
        $line = fgets($connect);
        $header = explode(' ', $line);
        $information = array(
            'method' => $header[0],
            'uri' => $header[1]
        );

        while ($line = rtrim(fgets($connect))) {
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $information[$matches[1]] = $matches[2];
            } else {
                break;
            }
        }

        $address = explode(':', stream_socket_get_name($connect, true));
        $information['ip'] = $address[0];
        $information['port'] = $address[1];

        if (empty($inf['Sec-WebSocket-Key'])) {
            return false;
        }

        $hash = $information['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
        $hash = sha1($hash);
        $SecWebSocketAccept = base64_encode(pack('H*', $hash));
        $response = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept:$SecWebSocketAccept\r\n\r\n";

        fwrite($connect, $response);

        return $information;
    }

    /**
     * @param $connect
     * @param $data
     */
    protected function onMessage($connect, $data)
    {
        $this->inform("$connect: $data");
    }

    /**
     * @param $connect
     * @param $msg
     */
    protected function onOpen($connect, $msg)
    {
        fwrite($connect, $msg, self::LIMIT);
    }

    /**
     * @param $resource
     * @return mixed
     */
    protected function getMessage($resource)
    {
        return fread($resource, self::LIMIT);
    }

}
