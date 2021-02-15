<?php

/**
 * Hosted Session Ngenius Saved Card Sale Request
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

include_once _PS_MODULE_DIR_.'hsngenius/classes/Logger.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Request/AbstractRequest.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Config/Config.php';

class SavedCardSaleRequest extends AbstractRequest
{
    /**
     * Builds ENV sale request array
     *
     * @param array $order
     * @param float $amount
     * @param string $session
     * @return array
     */
    public function getBuildArray($order, $amount, $session)
    {
        $logger = new Logger();
        $log['path'] = __METHOD__;
        $storeId = isset(Context::getContext()->shop->id) ? (int)Context::getContext()->shop->id : null;
        $config = new Config();
        $data = [
            'data' => [
                'order' => [
                    'action' => 'SALE',
                    'amount' => [
                        'currencyCode' =>  $order['amount']['currencyCode'],
                        'value' => (string) $order['amount']['value'],
                    ],
                    'merchantOrderReference' => $order['merchantOrderReference'],
                    'emailAddress' => $order['emailAddress'],
                ],
                'billingAddress'    => [
                    'firstName' => $order['billingAddress']['firstName'],
                    'lastName'  => $order['billingAddress']['lastName'],
                    'address1'  => $order['billingAddress']['address1'],
                    'city'  => $order['billingAddress']['city'],
                    'countryCode' => $order['billingAddress']['countryCode'],
                ],
                'payment'   => [
                    'maskedPan' => $order['payment']['maskedPan'],
                    'expiry'    => $order['payment']['expiry'],
                    'cardholderName'    => $order['payment']['cardholderName'],
                    'scheme'    => $order['payment']['scheme'],
                    'cardToken' => $order['payment']['cardToken'],
                    'recaptureCsc'  => $order['payment']['recaptureCsc'],
                    'cvv'   => $order['payment']['cvv'],
                ],
            ],
            'method' => "POST",
            'uri' => $config->getSavedCardOrderRequestURL($storeId),
        ];

        $log['sale_request'] = json_encode($data);
        $logger->addLog($log);
        return $data;
    }
}
