<?php

namespace Socket;

class Client
{
    use Inform;

    const LIMIT = 10000;
    
    protected $host;
    protected $port;
    protected $address;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->address = "$host:$port";
    }

    public function start()
    {
        $resource = stream_socket_client($this->address, $errno, $errstr);
        $stdin = fopen('php://stdin', 'r');
        if (!$resource) {
            $this->inform("$errstr ($errno)");
        } else {
            $this->inform("Welcome to $this->address");
            while (!feof($resource)) {
                $streams = array($resource, $stdin);
                $write = $except = null;

                if (!stream_select($streams, $write, $except, null)) {
                    break;
                }

                foreach ($streams as $stream) {
                    if ($stream == $stdin) {
                        $msg = trim(fgets($stdin));
                        $this->send($resource, $msg);
                    } else {
                        $msg = fread($stream, self::LIMIT);
                        $this->onMessage($msg);
                    }
                }
            }
            fclose($resource);
        }
    }

    protected function send($resource, $msg)
    {
        fwrite($resource, $msg);
    }

    protected function onMessage($msg)
    {
        $this->inform("$msg");
    }
}

$client = new Client('10.10.24.161', 8890);
$client->start();