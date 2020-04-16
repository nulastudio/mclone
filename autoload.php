<?php

spl_autoload_register(function ($class) {
    if (strpos($class, '../') !== false || preg_match('/[^\w\-\\\.]/', $class)) {
        return;
    }
    $class = str_replace('\\', '/', $class);
    tryLoadClass(APP_PATH . "/Controller/{$class}.php") ||
    tryLoadClass(APP_PATH . "/Model/{$class}.php")
    ;
});

function tryLoadClass($file)
{
    if (file_exists($file)) {
        require $file;
        return true;
    }
    return false;
}
