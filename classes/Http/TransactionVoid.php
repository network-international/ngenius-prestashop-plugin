<?php

/**
 * Hosted Session Ngenius TransactionVoid
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

include_once _PS_MODULE_DIR_.'hsngenius/classes/Http/AbstractTransaction.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Command.php';

class TransactionVoid extends Abstracttransaction
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
        $response = json_decode($responseEnc, true);
        if (isset($response['errors']) && is_array($response['errors'])) {
            Logger::addLog(json_encode($response));
            return false;
        } else {
            $transactionId = '';
            if (isset($response['_links']['self']['href'])) {
                $transactionArr = explode('/', $response['_links']['self']['href']);
                $transactionId = end($transactionArr);
            }
            $state = isset($response['state']) ? $response['state'] : '';
            $orderReference = isset($response['orderReference']) ? $response['orderReference'] : '';
            $orderStatus = 'NGENIUS_AUTH_REVERSED';

            $ngeniusOrder = [
                'status' => $orderStatus,
                'state' => $state,
                'reference' => $orderReference,
            ];

            $command->updateNgeniusNetworkinternational($ngeniusOrder);
            $order = new Order($response['merchantOrderReference']);
            $command->addCustomerMessage(json_decode(json_encode($response), true), $order);
            $command->setFlagStatus('hsngenius_auth_reversed', true);
            $order->setCurrentState((int)Configuration::get($orderStatus));

            return [
                'result' => [
                    'state' => $state,
                    'order_status' => $orderStatus
                ]
            ];
        }
    }
}
