<?php

/**
 * Hosted Session Ngenius Void Request
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
include_once _PS_MODULE_DIR_.'hsngenius/classes/Config/Config.php';
class VoidRequest
{
    /**
     * Builds ENV void request
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
        if ($config->isComplete()) {
            return[
                'token' => $tokenRequest->getAccessToken(),
                'request' => [
                    'data' => [],
                    'method' => "PUT",
                    'uri' => $config->getOrderVoidURL($ngenusOrder['reference'], $ngenusOrder['id_payment'], $storeId)
                ]
            ];
        } else {
            Logger::addLog("Error! Invalid configuration void transaction.");
            return false;
        }
    }
}
