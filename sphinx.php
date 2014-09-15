#!/usr/bin/php
<?php
require_once 'sphinxapi.php';

ini_set('display_errors', true);
ini_set('error_reporting', E_ALL);

$sc = new SphinxClient();
$sc->SetServer('localhost',9312);
$sc->SetMatchMode(SPH_MATCH_ALL);
$sc->SetRankingMode(SPH_RANK_NONE);
$sc->SetArrayResult(true);

if (count($argv) < 2) {
    die("Wrong argument count\n");
}

array_shift($argv);

$index = '';
$query = '';
$skipMatches = false;
$skipInfo = false;
foreach ($argv as $arg) {
    if (trim($arg)) {
        // options
        if (substr($arg, 0, 2) == '--') {
            if ($arg == '--skip-matches') {
                $skipMatches = true;
            } elseif ($arg == '--skip-info') {
                $skipInfo = true;
            } elseif (substr($arg, 0, 9) == '--limits=') {
                $limits = array_map('intval', array_map('trim', explode(':', substr($arg, 9))));
                if (count($limits) >= 2) {
                    $sc->SetLimits($limits[0], $limits[1], isset($limits[2]) ? $limits[2] : 0, isset($limits[3]) ? $limits[3] : 0);
                }
            }
        // filter
        } elseif (strpos($arg, '=') !== false) {
            list($attr, $value) = explode('=', $arg);
            $exclude = false;
            // exclude
            if (substr($attr, -1) == '!') {
                $attr = substr($attr, 0, -1);
                $exclude = true;
            }
            // range
            if (strpos($value, ':') !== false) {
                list($min, $max) = explode(':', $value);
                $sc->SetFilterRange($attr, $min, $max, $exclude);
            // list/single
            } else {
                if (strpos($value, ',') !== false) {
                    $values = array_filter(array_map('trim', explode(',', $value)));
                } else {
                    $values = array(trim($value));
                }
                $sc->SetFilter($attr, $values, $exclude);
            }
        // index
        } elseif (!$index) {
            $index = trim($arg);
        // query
        } else {
            $query = trim($arg);
        }
    }
}

if (!$index) {
    die("Index is not specified\n");
}

$indexes = [];
if (substr($index, -1) == '*') {
    $index = substr($index, 0, -1);
    $indexes[] = $index . '_full';
    $indexes[] = $index . '_delta';
    $indexes[] = $index;
} else {
    $indexes[] = $index;
}

foreach ($indexes as $index) {
    echo "index: $index\n";
    $res = $sc->Query($query, $index);
    if ($skipMatches) {
        unset($res['matches']);
    } elseif ($skipInfo) {
        $res = $res['matches'];
    }
    print_r($res);
}
