<?php

namespace NGenius\Http;

use Exception;
use NGenius\Command;
use NGenius\Config\Config;
use stdClass;

class TransactionAuth extends AbstractTransaction
{
    /**
     * Processing of API response
     *
     * @param $responseString
     * @return array|bool
     * @throws Exception
     */
    public function postProcess($responseString): ?array
    {
        $command = new Command();
        $response = json_decode($responseString);
        if (isset($response->_links->payment->href)
            && $command->placeNgeniusOrder($this->buildNgeniusData($response))
        ) {
            return ['payment_url' => $response->_links->payment->href];
        }
        return null;
    }

    /**
     * Build Ngenius Data Array
     *
     * @param stdClass $response
     * @param $order
     * @return array
     * @throws Exception
     */
    protected function buildNgeniusData(stdClass $response): array
    {
        $config = new Config();
        $data = [];
        $data['reference']  = $response->reference ?? '';
        $data['action']     = $response->action ?? '';
        $data['state']      = $response->_embedded->payment[0]->state ?? '';
        $data['status']     = $config->getOrderStatus().'_PENDING';
        $data['id_order']   = '';
        $data['id_cart']    = $response->merchantOrderReference ?? '';
        $data['amount']     = $response->amount->value ?? '';
        $data['currency']   = $response->amount->currencyCode ?? '';
        $data['outlet_id']  = $response->outletId ?? '';
        return $data;
    }
}
