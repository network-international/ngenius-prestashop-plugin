<?php

namespace NGenius\Request;

use NGenius\Logger;
use NGenius\Config\Config;
use NGenius\Request\AbstractRequest;

class SaleRequest extends AbstractRequest
{
    /**
     * Builds ENV sale request array
     *
     * @param $order
     * @param $amount
     * @return array
     */
    public function getBuildArray($order, $amount): array
    {
        $config = new Config();
        $logger = new Logger();
        $log = [];
        $log['path'] = __METHOD__;
        $storeId = isset(\Context::getContext()->shop->id) ? (int)\Context::getContext()->shop->id : null;
        $data = [
            'data' => [
                'action' => 'SALE',
                'amount' => [
                    'currencyCode' =>  $order['amount']['currencyCode'],
                    'value' => strval($order['amount']['value']),
                ],
                'merchantAttributes' => [
                    'redirectUrl' => $order['merchantAttributes']['redirectUrl'],
                    'skipConfirmationPage' => true,
                ],
                'billingAddress'    => [
                    'firstName'     =>  $order['billingAddress']['firstName'],
                    'lastName'      =>  $order['billingAddress']['lastName'],
                    'address1'      =>  $order['billingAddress']['address1'],
                    'city'          =>  $order['billingAddress']['city'],
                    'countryCode'   =>  $order['billingAddress']['countryCode'],
                ],
                'merchantOrderReference' => $order['merchantOrderReference'],
                'emailAddress' => $order['emailAddress'],
                'merchantDefinedData' => [
                    'pluginName' => 'prestashop',
                    'pluginVersion' => '1.0.2'
                ],
            ],
            'method' => "POST",
            'uri' => $config->getOrderRequestURL($order['amount']['currencyCode'], $storeId)
        ];
        $log['sale_request'] = json_encode($data);
        $logger->addLog($log);
        return $data;
    }
}
