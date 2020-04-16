<?php

use Medoo\Medoo;

class Application
{
    use \Psr\Log\LoggerAwareTrait;
    use \Psr\Log\LoggerTrait;

    private static $shareApplication;

    public $config;
    public $db;

    private function __construct($config)
    {
        $this->config = $config;
        $this->db = new Medoo($this->config['db'] ?? []);
    }

    public static function shareApplication($config = [])
    {
        if (!static::$shareApplication) {
            static::$shareApplication = new static($config);
        }
        return static::$shareApplication;
    }

    public function getConfig($key, $default = null)
    {
        return is_array($this->config) && isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    public function bootstrap()
    {
        $this->registerErrorHandler();
    }

    protected function registerErrorHandler()
    {
        // 注册异常错误处理器
        $booboo = new \League\BooBoo\BooBoo([]);

        // 调试模式下则打印报错
        if (defined('DEBUG') && DEBUG) {
            $booboo->silenceAllErrors(false);
            $formatter = new \League\BooBoo\Formatter\HtmlTableFormatter;
            $booboo->pushFormatter($formatter);
            $booboo->setErrorPageFormatter($formatter);
        } else {
            $booboo->pushFormatter(new \League\BooBoo\Formatter\NullFormatter);
        }

        // 处理器
        $logger  = new \Monolog\Logger(SYSTEM);
        $logFile = 'php://stderr';
        if (isset($this->config['logFile'])) {
            $logFile = $this->config['logFile'];
        }
        $handler   = new \Monolog\Handler\StreamHandler($logFile, \Monolog\Logger::DEBUG);
        $formatter = new \Monolog\Formatter\LineFormatter();
        $formatter->includeStacktraces();
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);
        $booboo->pushHandler(new \League\BooBoo\Handler\LogHandler($logger));

        $booboo->register();
    }

    public function log($level, $message, array $context = array())
    {
        $this->logger->log($level, $message, $context);
    }
}
