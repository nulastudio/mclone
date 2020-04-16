<?php

class View
{
    public $view;
    public $data;
    public $isJson;

    public function __construct($view, $isJson = false)
    {
        $this->view   = $view;
        $this->isJson = $isJson;
    }

    public static function make($viewName)
    {
        $viewPath = self::viewPath($viewName);
        if (is_file($viewPath)) {
            return new View($viewPath);
        } else {
            throw new \UnexpectedValueException("View file does not exist!");
        }
    }

    public static function json($json)
    {
        return new View($json, true);
    }

    public static function render($view = null)
    {
        ob_start();
        if (is_string($view)) {
            echo $view;
        } else if ($view instanceof View) {
            if ($view->isJson) {
                echo json_encode($view->view);
            } else {
                if ($view->data) {
                    extract($view->data);
                }
                require $view->view;
            }
        }
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public static function process($view = null)
    {
        echo self::render($view);
    }

    public function with($key, $value = null)
    {
        $this->data[$key] = $value;
        return $this;
    }

    private static function viewPath($viewName)
    {
        $viewName = trim(str_replace('\\', '/', $viewName));
        $filePath = str_replace('..', '/', $viewName);
        return VIEW_PATH . "/{$filePath}.php";
    }
}
