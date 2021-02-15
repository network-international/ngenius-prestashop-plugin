<?php

/**
 * Hosted Session Ngenius Sale Request
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
include_once _PS_MODULE_DIR_.'hsngenius/classes/Logger.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Request/AbstractRequest.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Config/Config.php';
class SaleRequest extends AbstractRequest
{
    /**
     * Builds ENV sale request array
     *
     * @param array $order
     * @param float $amount
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
                'action' => 'SALE',
                'amount' => [
                    'currencyCode' =>  $order['amount']['currencyCode'],
                    'value' => (string) $order['amount']['value'],
                ],
                'billingAddress' => [
                    'firstName' => $order['billingAddress']['firstName'],
                    'lastName'  => $order['billingAddress']['lastName'],
                    'address1'  => $order['billingAddress']['address1'],
                    'city'      => $order['billingAddress']['city'],
                    'countryCode' => $order['billingAddress']['countryCode'],
                ],
                'merchantOrderReference' => $order['merchantOrderReference'],
                'emailAddress' => $order['emailAddress'],
            ],
            'method' => "POST",
            'uri' => $config->getOrderRequestURL($session, $storeId)
        ];

        $log['sale_request'] = json_encode($data);
        $logger->addLog($log);
        return $data;
    }
}
