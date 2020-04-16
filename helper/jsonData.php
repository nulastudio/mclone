<?php

function jsonData($err_no = 0, $err_msg = '', $data = null)
{
    return [
        'err_no'        => (int) $err_no,
        'err_msg'       => (string) $err_msg,
        'data'          => is_array($data) ? $data : null,
        'response_time' => microtime(true),
    ];
}
