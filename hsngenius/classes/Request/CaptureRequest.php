<?php

/**
 * Hosted Session Ngenius Capture Request
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
include_once _PS_MODULE_DIR_.'hsngenius/classes/Config/Config.php';

class CaptureRequest
{
    /**
     * Builds ENV Capture request
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
            return [
                'token' => $tokenRequest->getAccessToken(),
                'request' => [
                    'data' => [
                        'amount' => [
                            'currencyCode' => $ngenusOrder['currency'],
                            'value' => (string) $amount,
                        ]
                    ],
                    'method' => "POST",
                    'uri' => $config->getOrderCaptureURL($ngenusOrder['reference'], $ngenusOrder['id_payment'], $storeId)
                ]
            ];
        } else {
            Logger::addLog("Error! Invalid configuration for capture request.");
            return false;
        }
    }
}
