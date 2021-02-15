<?php
/**
 * Hosted Session Ngenius Redirect Module Front Controller
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
include_once _PS_MODULE_DIR_.'hsngenius/classes/Logger.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Command.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Request/OrderStatusRequest.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Request/TokenRequest.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Http/TransactionOrderRequest.php';

class HsngeniusRedirectModuleFrontController extends ModuleFrontController
{

    /**
     * Processing of API response
     *
     * @return void
     */
    public function postProcess()
    {
        $logger = new Logger();
        $log['path'] = __METHOD__;
        $this->display_column_left = false;
        $this->display_column_right = false;
        $command = new Command();
        $ref = $_REQUEST['ref'];
    
        $ngeniusOrder = $this->getNgeniusOrder($ref);
        $order = new Order($ngeniusOrder['id_order']);
        if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id) {
            $this->updateNgeniusOrderStatusToProcessing($ngeniusOrder, $order);
            $order->setCurrentState((int)Configuration::get('NGENIUS_PROCESSING'));            
            $response = $this->getOrderStatusRequest($ref);
            $response = json_decode(json_encode($response), true);
            
            $this->processOrder($response, $ngeniusOrder);
            if ((isset($this->getNgeniusOrder($ref)['state']) && $this->getNgeniusOrder($ref)['state'] == 'FAILED') || ($this->getNgeniusOrder($ref)['state'] != 'AUTHORISED' && $this->getNgeniusOrder($ref)['state'] != 'CAPTURED')) {
                // cart restore
                $newOrder = new Order(Order::getOrderByCartId($order->id_cart));
                if ($newOrder) {
                    $oldCart = new Cart($order->id_cart);
                    $duplication = $oldCart->duplicate();
                    if (!$duplication || !Validate::isLoadedObject($duplication['cart'])) {
                        $this->errors[] = Tools::displayError('Sorry. We cannot renew your order.');
                    } elseif (!$duplication['success']) {
                        $this->errors[] = Tools::displayError('Some items are no longer available, and we are unable to renew your order.');
                    } else {
                        $this->context->cookie->id_cart = $duplication['cart']->id;
                        $context = $this->context;
                        $context->cart = $duplication['cart'];
                        CartRule::autoAddToCart($context);
                        $this->context->cookie->write();
                    }
                }
                // cart restore end
                // Reinject Quantity
                $url = _PS_BASE_URL_.__PS_BASE_URI__.'module/hsngenius/failedorder';
                $log['ps_redirection_url'] = $url;
                $logger->addLog($log);                            
                Tools::redirect($url);
            } else {
                $url = $this->module->getOrderConfUrl($order);
                $log['ps_redirection_url'] = $url;
                $logger->addLog($log);
                Tools::redirectLink($url);
            }
        }
    }

    /**
     * Process Order.
     *
     * @param array $response
     * @param array $ngeniusOrder
     * @param int|null $cronJob
     * @return bool
     */
    public function processOrder($response, $ngeniusOrder, $cronJob = false)
    {
        $logger = new Logger();
        $log['path'] = __METHOD__;
        $command = new Command();
        $paymentId = '';
        $captureAmount = 0;
        $status = null;
        $state = null;
        $transactionId = '';
        $cardDetails = $authorizationCode = $resultCode = $resultMessage = null;
        $order = new Order($ngeniusOrder['id_order']);
        if (Validate::isLoadedObject($order)) {
            if (isset($response['_embedded']['payment'][0]['_id'])) {
                $transactionIdRes = explode(":", $response['_embedded']['payment'][0]['_id']);
                $paymentId = end($transactionIdRes);
            }
            
            // saved Card
            if (isset($response['_embedded']['payment'][0]['paymentMethod'])) {
                $cardDetails = json_encode($response['_embedded']['payment'][0]['paymentMethod']);
            }

            // authResponse
            if (isset($response['_embedded']['payment'][0]['authResponse'])) {
                $authResponse = $response['_embedded']['payment'][0]['authResponse'];
                $authorizationCode = $authResponse['authorizationCode'];
                $resultCode = $authResponse['resultCode'];
                $resultMessage = $authResponse['resultMessage'];
            }
            // state
            $state = isset($response['_embedded']['payment'][0]['state']) ? $response['_embedded']['payment'][0]['state'] : null;
            
            if ($state == 'CAPTURED') {
                if (isset($response['_embedded']['payment'][0]['_embedded']['cnp:capture']) && is_array($response['_embedded']['payment'][0]['_embedded']['cnp:capture'])) {
                    $lastTransaction = end($response['_embedded']['payment'][0]['_embedded']['cnp:capture']);
                    foreach ($response['_embedded']['payment'][0]['_embedded']['cnp:capture'] as $key => $capture) {
                        if (isset($capture['state']) && ($capture['state'] == 'SUCCESS') && isset($capture['amount']['value'])) {
                            $captureAmount += $capture['amount']['value'];
                        }
                    }
                }
                
                if (isset($lastTransaction['_links']['self']['href'])) {
                    $transactionArr = explode('/', $lastTransaction['_links']['self']['href']);
                    $transactionId = end($transactionArr);
                } elseif ($lastTransaction['_links']['cnp:refund']['href']) {
                    $transactionArr = explode('/', $lastTransaction['_links']['cnp:refund']['href']);
                    $transactionId = $transactionArr[count($transactionArr)-2];
                }
                $status = $this->getNgeniusOrderStatus($response);
                $state = $response['_embedded']['payment'][0]['state'];
                $command->sendOrderConfirmationMail($order);
            } elseif ($state == 'AUTHORISED') {
                $command->sendOrderConfirmationMail($order);
                $status = $this->getNgeniusOrderStatus($response);
                $state = $response['_embedded']['payment'][0]['state'];
            } elseif ($state == 'FAILED' || $state == 'POST_AUTH_REVIEW') {
                $status = 'NGENIUS_FAILED';
                $state = $response['_embedded']['payment'][0]['state'];
                $command->reinjectQuantity($order->id);
            } elseif ($state == 'STARTED' || $state == 'AWAIT_3DS') {
                $status = ($cronJob == true) ? 'NGENIUS_FAILED' : 'NGENIUS_AWAIT_3DS';
                $data = [
                    'id_payment' => $paymentId,
                    'reference' => $ngeniusOrder['reference'],
                    'card_details' => $cardDetails,
                    'authorization_code' => $authorizationCode,
                    'result_code' => $resultCode,
                    'result_message' => $resultMessage,
                ];
                $command->updateNgeniusNetworkinternational($data);
            }
            
            if (isset($status) && isset($state)) {
                // only run cron job
                if ($cronJob == true) {
                    $this->updateNgeniusOrderStatusToProcessing($ngeniusOrder, $order);
                    $order->setCurrentState((int)Configuration::get('NGENIUS_PROCESSING'));
                }
                
                // saved card
                if (isset($response['_embedded']['payment'][0]['savedCard']) && isset($ngeniusOrder['ch_saved_card']) && $ngeniusOrder['ch_saved_card'] == 1) {
                    $savedCard = $response['_embedded']['payment'][0]['savedCard'];
                    $command->setCustomerSavedCard($savedCard, $order->id_customer, $ngeniusOrder);
                }

                $data = [
                    'id_payment'    => $paymentId,
                    'capture_amt'   => $captureAmount > 0 ? $captureAmount / 100 : 0,
                    'status'        => $status,
                    'state'         => $state,
                    'reference'     => $ngeniusOrder['reference'],
                    'id_capture'    => $transactionId,
                    'card_details'  => $cardDetails,
                    'authorization_code' => $authorizationCode,
                    'result_code'   => $resultCode,
                    'result_message'=> $resultMessage,
                    'api_type'      => ($cronJob == true) ? 'cron' : 'direct',
                ];
                $command->updateNgeniusNetworkinternational($data);
                $command->updatePsOrderPayment($command->getOrderPaymentRequest($response), $order);
                $command->addCustomerMessage($response, $order);
                $order->setCurrentState((int)Configuration::get($status));
                $log['order_status_set_to'] = $status;
                $logger->addLog($log);
                return true;
            }
        }
        return false;
    }

    /**
     * Gets Ngenius Order Status.
     *
     * @param array $response
     * @return string
     */
    public function getNgeniusOrderStatus($response)
    {
        switch ($response['action']) {
            case "SALE":
                $status = 'NGENIUS_COMPLETE';
                break;
            case "AUTH":
                $status = 'NGENIUS_AUTHORISED';
                break;
            default:
                $status = 'NGENIUS_PENDING';
        }
        return $status;
    }

    /**
     * Gets Order Status Request
     *
     * @param string $ref
     * @param int|null $storeId
     * @return array
     */
    public static function getOrderStatusRequest($ref, $storeId = null)
    {
        $tokenRequest = new TokenRequest();
        $transferFactory = new TransferFactory();
        $transactionOrderRequest = new TransactionOrderRequest();
        $orderStatusRequest = new OrderStatusRequest();

        $requestData =  [
            'token'     => $tokenRequest->getAccessToken(),
            'request'   => $orderStatusRequest->getBuildArray($ref, $storeId),
        ];
        $transferObject =  $transferFactory->create($requestData);
        $orderResponse = $transactionOrderRequest->placeRequest($transferObject);
        return $orderResponse;
    }

    /**
     * Gets ngenius order by referance
     *
     * @param string $reference
     * @return array
     */
    public static function getNgeniusOrder($reference)
    {
        $sql = new DbQuery();
        $sql->select('*')->from("ngenius_networkinternational")->where('reference ="'.pSQL($reference).'"');
        return $ngenusOrder = Db::getInstance()->getRow($sql);
    }

    /**
     * Update Ngenius Order Status To Processing
     *
     * @param string $reference
     * @return bool
     */
    public static function updateNgeniusOrderStatusToProcessing($ngenusOrder, $order)
    {
        $command = new Command();
        $ngeniusOrder = [
            'status' => 'NGENIUS_PROCESSING',
            'reference' => $ngenusOrder['reference'],
        ];
        $command->updateNgeniusNetworkinternational($ngeniusOrder);
        $command->addCustomerMessage($response = null, $order);
        return true;
    }
}
