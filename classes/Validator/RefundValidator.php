<?php

/**
 * Hosted Session Ngenius RefundValidator
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
class RefundValidator
{

    /**
     * Performs refund validation for transaction
     *
     * @param array $response
     * @return bool
     */
    public function validate($response)
    {
        if (!isset($response['result']) && !is_array($response['result'])) {
            Logger::addLog('Error! Invalid refund transaction');
            Logger::addLog($response);
            return false;
        } else {
            return true;
        }
    }
}
