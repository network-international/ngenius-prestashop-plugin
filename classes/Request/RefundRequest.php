<?php

/**
 * Hosted Session Ngenius Refund Request
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

include_once _PS_MODULE_DIR_.'hsngenius/classes/Config/Config.php';

class RefundRequest
{
    /**
     * Builds ENV refund request
     *
     * @param array $order
      * @param array $ngenusOrder
     * @return array|bool
     */
    public function build($order, $ngenusOrder)
    {
        $tokenRequest = new TokenRequest();
        $config = new Config();
        $storeId = isset(Context::getContext()->shop->id) ? (int)Context::getContext()->shop->id : null;
        $amount = $ngenusOrder['amount'] * 100;
        if ($config->isComplete()) {
            return[
                'token' => $tokenRequest->getAccessToken(),
                'request' => [
                    'data' => [
                        'amount' => [
                            'currencyCode' => $ngenusOrder['currency'],
                            'value' => (string) $amount,
                        ]
                    ],
                    'method' => "POST",
                    'uri' => $config->getOrderRefundURL($ngenusOrder['reference'], $ngenusOrder['id_payment'], $ngenusOrder['id_capture'], $storeId)
                ]
            ];
        } else {
            Logger::addLog("Error! Invalid Configuration Refund Request.");
            return false;
        }
    }
}
