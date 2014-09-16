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

$keys = [];
if ($key && strpos($key, '*') === false) {
    $keys[] = $key;
} else {
    $allKeys = $mc->getAllKeys();
    if (strpos($key, '*') !== false) {
        $keyPattern = '/^' . str_replace('*', '.*', $key) . '$/i';
        foreach ($allKeys as $k) {
            if (preg_match($keyPattern, $k)) {
                $keys[] = $k;
            }
        }
    } elseif ($listKeys) {
        $keys = $allKeys;
    }
}

foreach ($keys as $key) {
    if ($listKeys) {
        echo $key . PHP_EOL;
    } else {
        $value = $mc->get($key);
        if (!$value && $mc->getResultCode() == \Memcached::RES_NOTFOUND) {
            echo 'key "' . $key . '" not found' . PHP_EOL;
        } else {
            echo $key . ' = ' . $value . PHP_EOL;
        }
    }
}
