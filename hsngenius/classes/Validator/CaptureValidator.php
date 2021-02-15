<?php

/**
 * Hosted Session Ngenius Capture Validator
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

class CaptureValidator
{
    /**
     * Performs validation for capture transaction
     *
     * @param array $response
     * @return bool
     */
    public function validate($response)
    {
        
        if (!isset($response['result']) && !is_array($response['result'])) {
            Logger::addLog('Error! Invalid capture transaction');
            Logger::addLog($response);
            return false;
        } else {
            return true;
        }
    }
}
