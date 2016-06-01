<?php

namespace Socket;

class Socket
{
    use Inform;

    const LIMIT = 10000;

    protected $host;
    protected $port;
    protected $address;

    protected $connects = array();

    public function __construct($host, $port)
    {
        $this->host= $host;
        $this->port= $port;
        $this->address = "$host:$port";
    }

    public function connect()
    {
        $this->inform('WELCOME TO SERVER!');
        $socket = stream_socket_server($this->address, $errno, $errstr);

        while (true) {
            $streams = $this->connects;
            $write = $except = null;
            $streams[] = $socket;

            if (!stream_select($streams, $write, $except, null)) {
                break;
            }

            if (in_array($socket, $streams)) {
                if ($connect = stream_socket_accept($socket, -1)) {
                    $this->connects[] = $connect;
                    unset($streams[array_search($socket, $streams)]);
                    $this->onOpen($connect, "Welcome to my socket\n");
                    $this->inform("New connect!");
                }
            }

            foreach ($streams as $resource) {
                $data = fread($resource, self::LIMIT);

                if ($data == false) {
                    $connectKey = array_search($resource, $this->connects);
                    unset($this->connects[$connectKey]);
                    $this->inform("$resource disconnected");
                    break;
                }

                $this->onMessage($resource, $data);
                $this->sendMessage($resource, $data);
            }
        }
    }

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

    protected function onMessage($connect, $data)
    {
        $this->inform("$connect: $data");
    }

    protected function onOpen($connect, $msg)
    {
        fwrite($connect, $msg, self::LIMIT);
    }

    protected function sendMessage($currentConnect, $msg)
    {
        foreach ($this->connects as $connect) {
            if ($connect != $currentConnect) {
                fwrite($connect, "$currentConnect say: $msg", self::LIMIT);
            }
        }
    }

}
