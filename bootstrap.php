<?php

// uneditable
define('ACCESS', 'SUCCESS');

// system name, editable
define('SYSTEM', 'mclone');

define('ROOT_PATH', __DIR__);
define('CORE_PATH', ROOT_PATH . '/system');
define('APP_PATH', ROOT_PATH . '/application');
define('CONTROLLER_PATH', APP_PATH . '/Controller');
define('MODEL_PATH', APP_PATH . '/Model');
define('VIEW_PATH', APP_PATH . '/View');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('DATA_PATH', ROOT_PATH . '/data');
define('LOG_PATH', ROOT_PATH . '/log');
define('CACHE_PATH', ROOT_PATH . '/cache');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('VENDOR_PATH', ROOT_PATH . '/vendor');
define('HELPER_PATH', ROOT_PATH . '/helper');

define('ROOT_URI', dirname($_SERVER['SCRIPT_NAME']));
define('PUBLIC_URI', ROOT_URI . '/public');

// 5 minutes
set_time_limit(60 * 5);

$DEBUG_MODE = getenv('DEBUG');
// $DEBUG_MODE = true;

define('DEBUG', $DEBUG_MODE);

date_default_timezone_set('Asia/Shanghai');

// error_reporting(~E_ALL);
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
// error_reporting(E_ALL);

require VENDOR_PATH . '/autoload.php';
require ROOT_PATH . '/autoload.php';

foreach (glob(HELPER_PATH . '/*.php') as $helper) {
    require $helper;
}

$config = require CONFIG_PATH . '/config.php';

$app = Application::shareApplication($config);

$app->bootstrap();
