<?php

namespace JMS\SecurityExtraBundle\Tests\Functional\TestBundle\Logger;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

class FileLogger implements LoggerInterface
{
    private $dir;

    public function __construct($logDir)
    {
        $this->dir = $logDir;
    }

    public function emerg($message, array $context = array())
    {
        $this->log('[EMERG] '.$message);
    }

    public function alert($message, array $context = array())
    {
        $this->log('[ALERT] '.$message);
    }

    public function crit($message, array $context = array())
    {
        $this->log('[CRIT] '.$message);
    }

    public function err($message, array $context = array())
    {
        $this->log('[ERR] '.$message);
    }

    public function warn($message, array $context = array())
    {
        $this->log('[WARN] '.$message);
    }

    public function notice($message, array $context = array())
    {
        $this->log('[NOTICE] '.$message);
    }

    public function info($message, array $context = array())
    {
        $this->log('[INFO] '.$message);
    }

    public function debug($message, array $context = array())
    {
        $this->log('[DEBUG] '.$message);
    }

    private function log($message)
    {
        file_put_contents($this->dir.'/log', $message."\n", FILE_APPEND);
    }
}