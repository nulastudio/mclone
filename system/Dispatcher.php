<?php

class Dispatcher
{
    public static $ALLOW_DOMAINS = ['localhost', 'liesauer.net', 'nulastudio.org'];

    public static function dispatch($message)
    {
        if ($message instanceof View) {
            $view = View::render($message);
            if ($message->isJson) {
                static::dispatchJSON($view);
            } else {
                static::dispatchHTML($view);
            }
        } else if (is_array($message)) {
            static::dispatchJSON($message);
        } else {
            static::dispatchHTML($message);
        }
    }
    private static function dispatchHTML($html)
    {
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }
    private static function dispatchJSON($json)
    {
        header('Content-Type: application/json; charset=utf-8');
        $HTTP_ORIGIN = '*';
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $allow = false;
            foreach (static::$ALLOW_DOMAINS as $domain) {
                if (strpos($_SERVER['HTTP_ORIGIN'], $domain) !== false) {
                    $allow = true;
                }
            }

            $HTTP_ORIGIN = $allow ? $_SERVER['HTTP_ORIGIN'] : '';
        }
        if ($HTTP_ORIGIN) {
            header("Access-Control-Allow-Origin: {$HTTP_ORIGIN}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, PATCH, DELETE');
            header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin, Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Authorization , Access-Control-Request-Headers');
        }
        echo is_array($json) ? json_encode($json) : $json;
    }
}
