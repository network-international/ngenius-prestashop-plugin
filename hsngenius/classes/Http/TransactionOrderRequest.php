<?php
/**
 * Hosted Session Ngenius TransactionOrderRequest
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Http/AbstractTransaction.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Config/Config.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Command.php';

class TransactionOrderRequest extends AbstractTransaction
{

    /**
     * Processing of API request body
     *
     * @param array $data
     * @return string
     */
    protected function preProcess(array $data)
    {
        return json_encode($data);
    }

    /**
     * Processing of API response
     *
     * @param array $response
     * @return array|bool
     */
    protected function postProcess($responseEnc)
    {
        if ($responseEnc) {
            return json_decode($responseEnc);
        } else {
            return false;
        }
    }
}
