<?php

/**
 * Hosted Session Ngenius logger
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Config/Config.php';
class Logger
{
    /**
     * add ngenius log
     *
     * @param string $$message
     * @return void
     */
    public function addLog($message)
    {
        if (Config::isDebugMode()) {
            $logger = new FileLogger(0);   //0 == debug level, logDebug() won’t work without this.
            $logger->setFilename(_PS_ROOT_DIR_ . "/log/hsngenius.log");
            $logger->logDebug($message);
        }
    }
}
