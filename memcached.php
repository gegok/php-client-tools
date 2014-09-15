#!/usr/bin/php
<?php
ini_set('display_errors', true);
ini_set('error_reporting', E_ALL);

array_shift($argv);

$host = 'localhost';
$port = 11211;
$listKeys = false;
$key = '';
foreach ($argv as $arg) {
    if (trim($arg)) {
        // options
        if (substr($arg, 0, 7) == '--host=') {
            $host = trim(substr($arg, 7));
        } elseif (substr($arg, 0, 7) == '--port=') {
            $port = (int) trim(substr($arg, 7));
        } elseif ($arg == '--list') {
            $listKeys = true;
        // key
        } else {
            $key = trim($arg);
        }
    }
}

$mc = new Memcached();
$mc->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
$mc->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
$mc->addServer($host, $port);

if ($listKeys) {
    $allKeys = $mc->getAllKeys();
    foreach ($allKeys as $key) {
        echo $key . PHP_EOL;
    }
} else {
    $keys = [];
    if (strpos($key, '*') !== false) {
        $allKeys = $mc->getAllKeys();
        $keyPattern = '/^' . str_replace('*', '.*', $key) . '$/i';
        foreach ($allKeys as $k) {
            if (preg_match($keyPattern, $k)) {
                $keys[] = $k;
            }
        }
    } elseif ($key) {
        $keys[] = $key;
    }

    foreach ($keys as $key) {
        $value = $mc->get($key);
        if (!$value && $mc->getResultCode() == \Memcached::RES_NOTFOUND) {
            echo 'key "' . $key . '" not found' . PHP_EOL;
        } else {
            echo $key . ' = ' . $value . PHP_EOL;
        }
    }
}
