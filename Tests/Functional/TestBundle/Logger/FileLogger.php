<?php

namespace JMS\SecurityExtraBundle\Tests\Functional\TestBundle\Logger;

use Psr\Log\LoggerInterface;

class FileLogger implements LoggerInterface
{
    private $dir;

    public function __construct($logDir)
    {
        $this->dir = $logDir;
    }

    public function emergency($message, array $context = array())
    {
        $this->log('emerg', '[EMERG] '.$message);
    }

    public function alert($message, array $context = array())
    {
        $this->log('alert', '[ALERT] '.$message);
    }

    public function critical($message, array $context = array())
    {
        $this->log('crit', '[CRIT] '.$message);
    }

    public function error($message, array $context = array())
    {
        $this->log('err', '[ERR] '.$message);
    }

    public function warning($message, array $context = array())
    {
        $this->log('warn', '[WARN] '.$message);
    }

    public function notice($message, array $context = array())
    {
        $this->log('notice', '[NOTICE] '.$message);
    }

    public function info($message, array $context = array())
    {
        $this->log('info', '[INFO] '.$message);
    }

    public function debug($message, array $context = array())
    {
        $this->log('debug', '[DEBUG] '.$message);
    }

    public function log($level, $message, array $context = array())
    {
        file_put_contents($this->dir.'/log', $message."\n", FILE_APPEND);
    }
}
