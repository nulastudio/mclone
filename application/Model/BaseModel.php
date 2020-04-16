<?php

class BaseModel
{
    protected $app;
    protected $db;

    public function __construct()
    {
        $this->app = Application::shareApplication();
        $this->db  = $this->app->db;
    }

    protected function hasError()
    {
        $err = $this->db->error();
        return $err != null && $err[0] !== "00000";
    }

    protected function raw($value)
    {
        return Medoo\Medoo::raw($value);
    }
}
