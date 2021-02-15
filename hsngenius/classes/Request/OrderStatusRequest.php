<?php

/**
 * Hosted Session Ngenius Order Status Request
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
include_once _PS_MODULE_DIR_.'hsngenius/classes/Logger.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Request/AbstractRequest.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Config/Config.php';

class OrderStatusRequest extends AbstractRequest
{

    /**
     * Builds ENV order status request
     *
     * @param string $orderRef
     * @param int|null $storeId
     * @return array
     */
    public function getBuildArray($orderRef, $storeId = null, $session = null)
    {
        $logger = new Logger();
        $log['path'] = __METHOD__;
        $config = new Config();
        $storeId = isset(Context::getContext()->shop->id) ? (int)Context::getContext()->shop->id : null;
        $data = [
            'data' => [],
            'method' => "GET",
            'uri' => $config->getFetchRequestURL($orderRef, $storeId)
        ];

        $log['order_status_request'] = json_encode($data);
        $logger->addLog($log);
        return $data;
    }
}
