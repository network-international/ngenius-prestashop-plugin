<?php
/**
 * Hosted Session Ngenius Void Validator
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
class VoidValidator
{
    /**
     * Performs reversed the authorization
     *
     * @param array $response
     * @return bool
     */
    public function validate($response)
    {
        if (isset($response['result'])) {
            return true;
        } else {
            Logger::addLog('Error! Invalid void transaction');
            Logger::addLog($response);
            return false;
        }
    }
}
