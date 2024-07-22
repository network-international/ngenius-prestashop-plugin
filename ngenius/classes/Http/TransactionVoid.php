<?php

namespace NGenius\Http;

use Exception;
use NGenius\Command;
use NGenius\Config\Config;
use NGenius\Http\AbstractTransaction;

class TransactionVoid extends Abstracttransaction
{
    /**
     * Processing of API response
     *
     * @param $responseString
     *
     * @return array|bool
     * @throws Exception
     */
    public function postProcess($responseString): ?array
    {
        $config   = new Config();
        $command  = new Command();
        $response = json_decode($responseString, true);
        if (isset($response['errors']) && is_array($response['errors'])) {
            return null;
        } else {
            $transactionId = '';
            if (isset($response['_links']['self']['href'])) {
                $transactionArr = explode('/', $response['_links']['self']['href']);
                $transactionId  = end($transactionArr);
            }
            $state          = isset($response['state']) ? $response['state'] : '';
            $orderReference = isset($response['orderReference']) ? $response['orderReference'] : '';
            $orderStatus    = $config->getOrderStatus() . '_AUTH_REVERSED';
            $ngeniusOrder   = [
                'status'    => $orderStatus,
                'state'     => $state,
                'reference' => $orderReference,

            ];
            $command->updateNgeniusNetworkinternational($ngeniusOrder);
            $_SESSION['ngenius_auth_reversed'] = 'true';

            return [
                'result' => [
                    'state'        => $state,
                    'order_status' => $orderStatus,
                    'id_capture'   => $transactionId,
                ]
            ];
        }
    }
}
