<?php

/**
 * Hosted Session Ngenius TransactionSale
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

include_once _PS_MODULE_DIR_.'hsngenius/classes/Http/AbstractTransaction.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Command.php';

class TransactionSale extends AbstractTransaction
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
        $command = new Command();
        $response = json_decode($responseEnc);
        
        if (isset($response->orderReference)) {
            $order = new Order($response->merchantOrderReference);
            $data['reference']  = $response->orderReference;
            $data['action']     = 'SALE';
            $data['state']      = isset($response->state) ? $response->state : '';
            $data['status']     = Config::getInitialStatus();
            $data['id_order']   = isset($response->merchantOrderReference) ? $response->merchantOrderReference : '';
            $data['id_cart']    = isset($order->id_cart) ? $order->id_cart : '';
            $data['amount']     = isset($response->amount->value) ? $response->amount->value / 100 : '';
            $data['currency']   = isset($response->amount->currencyCode) ? $response->amount->currencyCode : '';

            if ($command->updateNngeniusTable($data)) {
                $command->addCustomerMessage(json_decode(json_encode($response), true), $order);
                return $responseEnc;
            } else {
                Logger::addLog("Invalid Ngenius Order Data!");
                return $responseEnc;
            }
        } else {
            Logger::addLog(json_encode($response));
            return $responseEnc;
        }
    }
}
