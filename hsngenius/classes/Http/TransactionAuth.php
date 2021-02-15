<?php

/**
 * Hosted Session Ngenius TransactionAuth
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
include_once _PS_MODULE_DIR_.'hsngenius/classes/Command.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Logger.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Http/AbstractTransaction.php';

class TransactionAuth extends AbstractTransaction
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
     * @param array $responseEnc
     * @return array|bool
     */
    protected function postProcess($responseEnc)
    {
        $command = new Command();
        $logger = new Logger();
        $response = json_decode($responseEnc);

        if (isset($response->orderReference)) {
            $order = new Order($response->merchantOrderReference);
            $data['reference']  = $response->orderReference;
            $data['action']     = 'AUTH';
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
                $logger->addLog("Order Id: " . $order['merchantOrderReference'] . " - ERROR! Invalid Ngenius Order Data.");
                return $responseEnc;
            }
        } else {
            $logger->addLog(json_encode($response));
            return $responseEnc;
        }
    }
}
