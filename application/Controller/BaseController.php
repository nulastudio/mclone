<?php

class BaseController
{
    protected $app;
    protected $config;

    public function __construct()
    {
        $this->app    = Application::shareApplication();
        $this->config = $this->app->config;
    }
    protected function get($key)
    {
        return isset($_GET[$key]) ? $_GET[$key] : null;
    }
    protected function post($key)
    {
        return isset($_POST[$key]) ? $_POST[$key] : null;
    }
    protected function request($key)
    {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
    }
}
