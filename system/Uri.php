<?php

class Uri
{
    public static function isHttps()
    {
        if (isset($_SERVER['HTTPS'])) {
            // Apache 1
            // IIS on
            return $_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == '1';
        } else {
            return $_SERVER['SERVER_PORT'] == 443;
        }
    }

    public static function getScheme()
    {
        return self::isHttps() ? 'https' : 'http';
    }

    public static function getHost()
    {
        return $_SERVER['HTTP_HOST'];
    }

    public static function siteUrl()
    {
        return self::getScheme() . '://' . self::getHost();
    }

    public static function basePath()
    {
        $uri = str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']);
        $uri = dirname($uri);
        $uri = str_replace('\\', '/', $uri);
        return $uri;
    }

    public static function baseUrl($uri = null)
    {
        if (!empty($uri)) {
            $uri = str_replace(['\\', '../', './', '//'], '/', $uri);
            if (strpos($uri, '/') !== 0) {
                $uri = '/' . $uri;
            }
        }
        return self::siteUrl() . self::basePath() . $uri;
    }

    public static function redirect($uri)
    {
        if (empty(parse_url($uri, PHP_URL_HOST))) {
            $uri = self::baseUrl($uri);
        }
        header("Location: {$uri}");
    }
}
