<?php namespace FMLaravel\Database;

use FileMaker;
use Log;


if (!defined('PEAR_LOG_EMERG')){
    define('PEAR_LOG_EMERG',    0);     /* System is unusable */
}
if (!defined('PEAR_LOG_ALERT')){
    define('PEAR_LOG_ALERT',    1);     /* Immediate action required */
}
if (!defined('PEAR_LOG_CRIT')){
    define('PEAR_LOG_CRIT',     2);     /* Critical conditions */
}
if (!defined('PEAR_LOG_ERR')){
    define('PEAR_LOG_ERR',      3);     /* Error conditions */
}
if (!defined('PEAR_LOG_WARNING')){
    define('PEAR_LOG_WARNING',  4);     /* Warning conditions */
}
if (!defined('PEAR_LOG_NOTICE')){
    define('PEAR_LOG_NOTICE',   5);     /* Normal but significant */
}
if (!defined('PEAR_LOG_INFO')){
    define('PEAR_LOG_INFO',     6);     /* Informational */
}
if (!defined('PEAR_LOG_DEBUG')){
    define('PEAR_LOG_DEBUG',    7);     /* Debug-level messages */
}

if (!defined('PEAR_LOG_ALL')) {
    define('PEAR_LOG_ALL', 0xffffffff);    /* All messages */
}
if (!defined('PEAR_LOG_NONE')) {
    define('PEAR_LOG_NONE', 0x00000000);    /* No message */
}

/* Log types for PHP's native error_log() function. */
if (!defined('PEAR_LOG_TYPE_SYSTEM')) {
    define('PEAR_LOG_TYPE_SYSTEM', 0); /* Use PHP's system logger */
}
if (!defined('PEAR_LOG_TYPE_MAIL')) {
    define('PEAR_LOG_TYPE_MAIL', 1); /* Use PHP's mail() function */
}
if (!defined('PEAR_LOG_TYPE_DEBUG')) {
    define('PEAR_LOG_TYPE_DEBUG', 2); /* Use PHP's debugging connection */
}
if (!defined('PEAR_LOG_TYPE_FILE')) {
    define('PEAR_LOG_TYPE_FILE', 3); /* Append to a file */
}
if (!defined('PEAR_LOG_TYPE_SAPI')) {
    define('PEAR_LOG_TYPE_SAPI', 4); /* Use the SAPI logging handler */
}


class LogFacade extends Log
{
    protected $logLevel;

    protected function __construct($filemakerLogLevel){
        $this->logLevel = $filemakerLogLevel;
    }

    public static function with($filemakerLogLevel){
        return new LogFacade($filemakerLogLevel);
    }

    public function attachTo(FileMaker $fileMaker){
        $fileMaker->setLogger($this);
        $fileMaker->setProperty('logLevel',$this->logLevel);
        return $fileMaker;
    }

    public function log($message, $logLevel){

        // the FileMaker API only uses three levels internally

        switch($logLevel){
            case PEAR_LOG_DEBUG:
                \Illuminate\Support\Facades\Log::debug($message);
                break;

            case PEAR_LOG_INFO:
                \Illuminate\Support\Facades\Log::info($message);
                break;

            case PEAR_LOG_ERR:
                \Illuminate\Support\Facades\Log::error($message);
                break;

            default:
                // This should not really happen, but in case it does, we still want it to be logged.
                \Illuminate\Support\Facades\Log::debug('IMPRECISE LOGLEVEL: '.$message);
        }
    }
}