<?php

function ende($a)
{
    $b       = $a;
    $keys    = 'YqtnPUX4RGwIN$yB^AR&H3G$J&l#eXdZFj*LF1#E9oMw1InYF2*uWkO3ucGpchgd';
    $key_len = strlen($keys);
    $len     = strlen($b);
    for ($i = 0; $i < $len; $i++) {
        $b[$i] = chr(ord($b[$i]) ^ ord($keys[$i % $key_len]));
    }
    return (string)$b;
}

function encrypt($msg)
{
    return bin2hex(ende($msg));
}

function decrypt($sec)
{
    return ende(hex2bin($sec));
}
