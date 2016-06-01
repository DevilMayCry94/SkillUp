<?php

namespace SkillUp;
use Socket\Server;
use Socket\Client;

set_include_path(__DIR__);
spl_autoload_extensions(".class.php");
spl_autoload_register(function ($class) {
    $class = str_replace('\\', '/', $class);
    require_once __DIR__.'/' . $class . '.php';
});

echo "1-Server\n2-Client\n";
$choice = trim(fgets(STDIN));
if ($choice == '1') {
    $socket = new Server('10.10.24.161', 8890);
    $socket->start();
} elseif ($choice == '2') {
    $client = new Client('10.10.24.161', 8890);
    $client->start();
}

