<?php

namespace NGenius\Request;

use NGenius\Logger;
use NGenius\Config\Config;
use NGenius\Request\AbstractRequest;

class AuthorizationRequest extends AbstractRequest
{
    /**
     * Builds ENV athorization request array
     *
     * @param  $order
     * @param  $amount
     *
     * @return array
     */
    public function getBuildArray($order, $amount): array
    {
        $config              = new Config();
        $logger              = new Logger();
        $log                 = [];
        $log['path']         = __METHOD__;
        $storeId             = isset(\Context::getContext()->shop->id) ? (int)\Context::getContext()->shop->id : null;
        $data                = [
            'data'   => [
                'action'                 => 'AUTH',
                'amount'                 => [
                    'currencyCode' => $order['amount']['currencyCode'],
                    'value'        => (int)($amount * 100),
                ],
                'merchantAttributes'     => [
                    'redirectUrl'          => $order['merchantAttributes']['redirectUrl'],
                    'skipConfirmationPage' => true,
                ],
                'billingAddress'         => [
                    'firstName'   => $order['billingAddress']['firstName'],
                    'lastName'    => $order['billingAddress']['lastName'],
                    'address1'    => $order['billingAddress']['address1'],
                    'address2'    => $order['billingAddress']['address2'],
                    'city'        => $order['billingAddress']['city'],
                    'stateCode'   => $order['billingAddress']['stateCode'],
                    'postalCode'  => $order['billingAddress']['postalCode'],
                    'countryCode' => $order['billingAddress']['countryCode'],
                ],
                'phoneNumber'            => [
                    'countryCode' => $order['phoneNumber']['countryCode'],
                    'subscriber'  => $order['phoneNumber']['subscriber']
                ],
                'merchantOrderReference' => $order['merchantOrderReference'],
                'emailAddress'           => $order['emailAddress'],
                'merchantDefinedData'    => [
                    'pluginName'    => 'prestashop',
                    'pluginVersion' => $config->getPluginVersion()
                ],
            ],
            'method' => "POST",
            'uri'    => $config->getOrderRequestURL($order['amount']['currencyCode'], $storeId)
        ];
        $log['auth_request'] = json_encode($data);
        $logger->addLog($log);

        return $data;
    }
}
