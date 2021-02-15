<?php
/**
 * Hosted Session Ngenius command core model
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Logger.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Config/Config.php';
// request
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Request/VoidRequest.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Request/SaleRequest.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Request/TokenRequest.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Request/RefundRequest.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Request/CaptureRequest.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Request/OrderStatusRequest.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Request/AuthorizationRequest.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Request/SavedCardSaleRequest.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Request/SavedCardAuthorizationRequest.php';
// Transaction
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Http/TransferFactory.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Http/TransactionAuth.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Http/TransactionSale.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Http/TransactionVoid.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Http/TransactionRefund.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Http/TransactionCapture.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Http/TransactionOrderRequest.php';
// validator
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Validator/VoidValidator.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Validator/RefundValidator.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Validator/CaptureValidator.php';
include_once _PS_MODULE_DIR_ . 'hsngenius/classes/Validator/ResponseValidator.php';



class Command
{
    /**
     * Order Authorize.
     *
     * @param array $order
     * @param float $amount
     * @return bool
     */
    public function authorize($order, $amount, $session)
    {
        $authorizationRequest = new AuthorizationRequest();
        $transferFactory = new TransferFactory();
        $transactionAuth = new TransactionAuth();
        $responseValidator = new ResponseValidator();

        $requestData = $authorizationRequest->build($order, $amount, $session);
        $transferObject = $transferFactory->create($requestData);
        $response = $transactionAuth->placeRequest($transferObject);
        $payment = $responseValidator->validate($response);
        return $payment;
    }

    /**
     * Order sale.
     *
     * @param array $order
     * @param float $amount
     * @return bool
     */
    public function order($order, $amount, $session)
    {
        $saleRequest = new SaleRequest();
        $transferFactory = new TransferFactory();
        $transactionSale = new TransactionSale();
        $responseValidator = new ResponseValidator();

        $requestData = $saleRequest->build($order, $amount, $session);
        $transferObject = $transferFactory->create($requestData);
        $response = $transactionSale->placeRequest($transferObject);
        $payment = $responseValidator->validate($response);
        return $payment;
    }

    /**
     * Saved Card Order Authorize.
     *
     * @param array $order
     * @param float $amount
     * @param string $cvv
     * @return bool
     */
    public function savedCardAuthorize($order, $amount, $session = null)
    {
        $savedCardAuthorizationRequest = new SavedCardAuthorizationRequest();
        $transferFactory = new TransferFactory();
        $transactionAuth = new TransactionAuth();
        $responseValidator = new ResponseValidator();

        $requestData = $savedCardAuthorizationRequest->build($order, $amount, $session);
        $transferObject = $transferFactory->create($requestData);
        $response = $transactionAuth->placeRequest($transferObject);
        $payment = $responseValidator->validate($response);
        return $payment;
    }

    /**
     * Saved Card Order sale.
     *
     * @param array $order
     * @param float $amount
     * @param string $cvv
     * @return bool
     */
    public function savedCardSale($order, $amount, $session = null)
    {
        $savedCardSaleRequest = new SavedCardSaleRequest();
        $transferFactory = new TransferFactory();
        $transactionSale = new TransactionSale();
        $responseValidator = new ResponseValidator();

        $requestData = $savedCardSaleRequest->build($order, $amount, $session);
        $transferObject = $transferFactory->create($requestData);
        $response = $transactionSale->placeRequest($transferObject);
        $payment = $responseValidator->validate($response);
        return $payment;
    }

    /**
     * Order capture.
     *
     * @param array $order
     * @param array $ngenusOrder
     * @return bool
     */
    public function capture($order, $ngenusOrder)
    {
        $captureRequest = new CaptureRequest();
        $transferFactory = new TransferFactory();
        $transactionCapture = new TransactionCapture();
        $captureValidator = new CaptureValidator();

        $requestData = $captureRequest->build($order, $ngenusOrder);
        $transferObject = $transferFactory->create($requestData);
        $response = $transactionCapture->placeRequest($transferObject);
        $result = $captureValidator->validate($response);
        return $result;
    }

    /**
     * Order void.
     *
     * @param array $order
     * @param array $ngenusOrder
     * @return bool
     */
    public function void($order, $ngenusOrder)
    {
        $voidRequest = new VoidRequest();
        $transferFactory = new TransferFactory();
        $transactionVoid = new TransactionVoid();
        $voidValidator = new VoidValidator();

        $requestData = $voidRequest->build($order, $ngenusOrder);
        $transferObject = $transferFactory->create($requestData);
        $response = $transactionVoid->placeRequest($transferObject);
        $result = $voidValidator->validate($response);
        return $result;
    }

    /**
     * Order refund.
     *
     * @param array $order
     * @param array $ngenusOrder
     * @return bool
     */
    public function refund($order, $ngenusOrder)
    {
        $refundRequest = new RefundRequest();
        $transferFactory = new TransferFactory();
        $transactionRefund = new TransactionRefund();
        $refundValidator = new RefundValidator();

        $requestData = $refundRequest->build($order, $ngenusOrder);
        $transferObject = $transferFactory->create($requestData);
        $response = $transactionRefund->placeRequest($transferObject);
        $result = $refundValidator->validate($response);
        return $result;
    }

    /**
     * Update Place Ngenius Order
     *
     * @param array $data
     * @return bool
     */
    public function placeNgeniusOrder($data)
    {
        $insertData = array(
            'id_cart' => (int) $data['id_cart'],
            'id_order' => (int) $data['id_order'],
            'amount' => (float) ($data['amount'] / 100),
            'currency' => pSQL($data['currency']),
            'reference' => pSQL($data['id_order']),
            'action' => pSQL($data['action']),
            'status' => pSQL($data['status']),
            'ch_saved_card' => (int) $data['ch_saved_card'],
            'state' => '',
            'id_payment' => null,
            'capture_amt' => null,
        );
        if (Db::getInstance()->insert("ngenius_networkinternational", $insertData)) {
            return true;
        } else {
            Logger::addLog('Ngenius order not saved!');
            return false;
        }
    }

    /**
     * Gets Ngenius Order
     *
     * @param int $orderId
     * @return bool
     */
    public static function getNgeniusOrder($orderId)
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from("ngenius_networkinternational")
            ->where('id_order ="'.pSQL($orderId).'"');
        return Db::getInstance()->getRow($sql);
    }

    /**
     * Gets Authorization Transaction
     *
     * @param array $ngeniusOrder
     * @return array|bool
     */
    public static function getAuthorizationTransaction($ngeniusOrder)
    {
        if (!empty($ngeniusOrder['id_payment']) && !empty($ngeniusOrder['reference']) && $ngeniusOrder['state'] == 'AUTHORISED') {
            return $ngeniusOrder;
        } else {
            Logger::addLog('Unauthorized N-Genius Online order transaction!');
            return false;
        }
    }

    /**
     * Gets Refunded Transaction
     *
     * @param array $ngeniusOrder
     * @return array|bool
     */
    public static function getRefundedTransaction($ngeniusOrder)
    {
        if (isset($ngeniusOrder['id_capture']) &&  !empty($ngeniusOrder['id_capture']) && $ngeniusOrder['capture_amt'] > 0 && $ngeniusOrder['state'] == 'CAPTURED') {
            return $ngeniusOrder;
        } else {
            //Logger::addLog('Invalid refund N-Genius Online order transaction!');
            return false;
        }
    }

    /**
     * Gets Delivery Transaction
     *
     * @param array $ngeniusOrder
     * @return array|bool
     */
    public static function getDeliveryTransaction($ngeniusOrder)
    {
        if (isset($ngeniusOrder['id_payment']) &&  !empty($ngeniusOrder['id_capture']) && $ngeniusOrder['capture_amt'] > 0) {
            return $ngeniusOrder;
        } else {
            Logger::addLog('Invalid delivery N-Genius Online order transaction!');
            return false;
        }
    }
    
    /**
     * Update Ngenius Networkinternational order table by reference
     *
     * @param array $data
     * @return bool
     */
    public static function updateNgeniusNetworkinternational($data)
    {
        $reference = $data['reference'];
        unset($data['reference']);
        return Db::getInstance()->update(
            'ngenius_networkinternational',
            $data,
            'reference = "'.pSQL($reference).'"'
        );
    }

    /**
     * Update Prestashop Order Payment table
     *
     * @param array $data
     * @return bool
     */
    public static function updatePsOrderPayment($data, $order)
    {
        $orderPayment = new OrderPayment();
        $orderPayment->order_reference = pSQL(Command::getOrderReference($data['id_order']));
        $orderPayment->id_currency = (int) $order->id_currency;
        $orderPayment->amount = (float) ($data['amount'] / 100);
        $orderPayment->payment_method = pSQL($order->payment);
        $orderPayment->conversion_rate = (float) ($order->conversion_rate);
        $orderPayment->transaction_id = pSQL($data['transaction_id']);
        $orderPayment->card_number = pSQL($data['card_number']);
        $orderPayment->card_brand = pSQL($data['card_brand']);
        $orderPayment->card_expiration = pSQL($data['card_expiration']);
        $orderPayment->card_holder = pSQL($data['card_holder']);

        if ($orderPayment->add()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets Order Reference
     *
     * @param int $orderId
     * @return array|bool
     */
    public static function getOrderReference($orderId)
    {
        $order = new Order($orderId);
        if (Validate::isLoadedObject($order)) {
            return $order->reference;
        } else {
            return null;
        }
    }
    
    /**
     * Add Customer Message
     *
     * @param array $response
     * @param array $order
     * @return bool
     */
    public static function addCustomerMessage($response, $order, $queryApi = false)
    {
        $addThread = Command::addCustomerThread($order);
        $thread = Command::getCustomerThread($order);
        $message = Command::buildCustomerMessage($response, $order, $queryApi);
        $customer_message = new CustomerMessage();
        $customer_message->id_customer_thread = (int) $thread['id_customer_thread'];
        $customer_message->private = (int) 1;
        $customer_message->message = pSQL($message);
        if ($customer_message->add()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add Customer Thread
     *
     * @param array $order
     * @return bool
     */
    public static function addCustomerThread($order)
    {
        if (!Command::getCustomerThread($order)) {
            $customer_thread = new CustomerThread();
            $customer_thread->id_contact = (int) 0;
            $customer_thread->id_customer = (int) $order->id_customer;
            $customer_thread->id_shop = (int) $order->id_shop;
            $customer_thread->id_order = (int) $order->id;
            $customer_thread->id_lang = (int) $order->id_lang;
            $customer = new Customer($order->id_customer);
            $customer_thread->email = $customer->email;
            $customer_thread->status = 'open';
            $customer_thread->token = Tools::passwdGen(12);

            if ($customer_thread->add()) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Gets Customer Thread
     *
     * @param array $order
     * @return array|bool
     */
    public static function getCustomerThread($order)
    {
        $sql = new DbQuery();
        $sql->select('*')->from("customer_thread")->where('id_order ="'.(int) $order->id.'"');
        if ($thread = Db::getInstance()->getRow($sql)) {
            return $thread;
        } else {
            return false;
        }
    }

    /**
     * Reinject Quantity to StockAvailable
     *
     * @param int $orderId
     * @return void
     */
    public static function reinjectQuantity($orderId)
    {
        $command = new Command();
        $orderItems = OrderDetail::getList((int)$orderId);
        foreach ($orderItems as $key => $orderItem) {
            $order_detail = new OrderDetail((int)$orderItem['id_order_detail']);
            $command->reinjectQuantityCore($order_detail, $order_detail->product_quantity);
        }
        return true;
    }

    /**
     * @param OrderDetail $order_detail
     * @param int $qty_cancel_product
     * @param bool $delete
     */
    protected function reinjectQuantityCore($order_detail, $qty_cancel_product, $delete = false)
    { 
        // Reinject product
        $reinjectable_quantity = (int)$order_detail->product_quantity - (int)$order_detail->product_quantity_reinjected;
        $quantity_to_reinject = $qty_cancel_product > $reinjectable_quantity ? $reinjectable_quantity : $qty_cancel_product;
        // @since 1.5.0 : Advanced Stock Management
        $product_to_inject = new Product($order_detail->product_id, false, (int)$this->context->language->id, (int)$order_detail->id_shop);

        $product = new Product($order_detail->product_id, false, (int)$this->context->language->id, (int)$order_detail->id_shop);

        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $product->advanced_stock_management && $order_detail->id_warehouse != 0) {
            $manager = StockManagerFactory::getManager();
            $movements = StockMvt::getNegativeStockMvts(
                                $order_detail->id_order,
                                $order_detail->product_id,
                                $order_detail->product_attribute_id,
                                $quantity_to_reinject
                            );
            $left_to_reinject = $quantity_to_reinject;
            foreach ($movements as $movement) {
                if ($left_to_reinject > $movement['physical_quantity']) {
                    $quantity_to_reinject = $movement['physical_quantity'];
                }

                $left_to_reinject -= $quantity_to_reinject;
                if (Pack::isPack((int)$product->id)) {
                    // Gets items
                        if ($product->pack_stock_type == 1 || $product->pack_stock_type == 2 || ($product->pack_stock_type == 3 && Configuration::get('PS_PACK_STOCK_TYPE') > 0)) {
                            $products_pack = Pack::getItems((int)$product->id, (int)Configuration::get('PS_LANG_DEFAULT'));
                            // Foreach item
                            foreach ($products_pack as $product_pack) {
                                if ($product_pack->advanced_stock_management == 1) {
                                    $manager->addProduct(
                                        $product_pack->id,
                                        $product_pack->id_pack_product_attribute,
                                        new Warehouse($movement['id_warehouse']),
                                        $product_pack->pack_quantity * $quantity_to_reinject,
                                        null,
                                        $movement['price_te'],
                                        true
                                    );
                                }
                            }
                        }
                    if ($product->pack_stock_type == 0 || $product->pack_stock_type == 2 ||
                            ($product->pack_stock_type == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 0 || Configuration::get('PS_PACK_STOCK_TYPE') == 2))) {
                        $manager->addProduct(
                                $order_detail->product_id,
                                $order_detail->product_attribute_id,
                                new Warehouse($movement['id_warehouse']),
                                $quantity_to_reinject,
                                null,
                                $movement['price_te'],
                                true
                            );
                    }
                } else {
                    $manager->addProduct(
                            $order_detail->product_id,
                            $order_detail->product_attribute_id,
                            new Warehouse($movement['id_warehouse']),
                            $quantity_to_reinject,
                            null,
                            $movement['price_te'],
                            true
                        );
                }
            }

            $id_product = $order_detail->product_id;
            if ($delete) {
                $order_detail->delete();
            }
            StockAvailable::synchronize($id_product);
        } elseif ($order_detail->id_warehouse == 0) {
            StockAvailable::updateQuantity(
                    $order_detail->product_id,
                    $order_detail->product_attribute_id,
                    $quantity_to_reinject,
                    $order_detail->id_shop
                );

            if ($delete) {
                $order_detail->delete();
            }
        } else {
            $this->errors[] = Tools::displayError('This product cannot be re-stocked.');
        }
    }

    /**
     * biuld customer message for order
     *
     * @param array $response
     * @param array $order
     * @return string
     */
    public static function buildCustomerMessage($response, $order, $queryApi)
    {
        $ngeniusOrder = Command::getNgeniusOrder($order->id);
        $message = '';
        if ($ngeniusOrder) {
            $status = 'Status : '.$ngeniusOrder['status'].' | ';
            $state = ' State : '.$ngeniusOrder['state'].' | ';
            $paymentId = null;
            $amount = null;
            if (isset($response['_embedded']['payment'][0])) {
                $paymentIdArr = explode(':', $response['_embedded']['payment'][0]['_id']);
                $paymentId = 'Transaction ID : '.end($paymentIdArr).' | ';
                if (isset($response['_embedded']['payment'][0]['amount'])) {
                    $value = $response['_embedded']['payment'][0]['amount']['value'] / 100;
                    $currencyCode =  $response['_embedded']['payment'][0]['amount']['currencyCode'];
                    $amount = 'Amount : '.$currencyCode.$value.' | ';
                }
            }
            // capture
            if (isset($response['_embedded']['cnp:capture']) && is_array($response['_embedded']['cnp:capture'])) {
                $lastTransaction = end($response['_embedded']['cnp:capture']);
                if (isset($lastTransaction['_links']['self']['href'])) {
                    $transactionArr = explode('/', $lastTransaction['_links']['self']['href']);
                    $paymentId = 'Capture ID : '.end($transactionArr).' | ';
                }
                if (isset($lastTransaction['state']) && ($lastTransaction['state'] == 'SUCCESS') && isset($lastTransaction['amount']['value'])) {
                    $value = $lastTransaction['amount']['value'] / 100;
                    $currencyCode =  $lastTransaction['amount']['currencyCode'];
                    $amount = 'Amount : '.$currencyCode.$value.' | ';
                }
            }
            // refund
            if (isset($response['_embedded']['cnp:refund']) && is_array($response['_embedded']['cnp:refund'])) {
                $lastTransaction = end($response['_embedded']['cnp:refund']);
                if (isset($lastTransaction['_links']['self']['href'])) {
                    $transactionArr = explode('/', $lastTransaction['_links']['self']['href']);
                    $paymentId = 'Refunded ID : '.end($transactionArr).' | ';
                }
                foreach ($response['_embedded']['cnp:refund'] as $refund) {
                    if (isset($refund['state']) && ($refund['state'] == 'SUCCESS') && isset($refund['amount']['value'])) {
                        $value = $refund['amount']['value'] / 100;
                        $currencyCode =  $lastTransaction['amount']['currencyCode'];
                        $amount = 'Amount : '.$currencyCode.$value.' | ';
                    }
                }
            }

            // query API
            if ($queryApi == true) {
                $message = 'API Type: Query API | ';
                if (isset($response['_embedded']['payment'][0])) {
                    $paymentIdArr = explode(':', $response['_embedded']['payment'][0]['_id']);
                    $paymentId = 'Transaction ID : '.end($paymentIdArr).' | ';
                }
                
                if (isset($response['_embedded']['payment'][0]['_embedded']['cnp:capture']) && is_array($response['_embedded']['payment'][0]['_embedded']['cnp:capture'])) {
                    foreach ($response['_embedded']['payment'][0]['_embedded']['cnp:capture'] as $captureTransaction) {
                        if (isset($captureTransaction['_links']['self']['href'])) {
                            $transactionArr = explode('/', $captureTransaction['_links']['self']['href']);
                            $paymentId = $paymentId.'Capture ID : '.end($transactionArr).' - ';
                        }
                        if (isset($captureTransaction['_links']['cnp:refund']['href'])) {
                            $transactionArr = explode('/', $captureTransaction['_links']['cnp:refund']['href']);
                            $paymentId = $paymentId.'Capture ID : '.$transactionArr[count($transactionArr)-2].' - ';
                        }
                        
                        if (isset($captureTransaction['state']) && ($captureTransaction['state'] == 'SUCCESS') && isset($captureTransaction['amount']['value'])) {
                            $value = $captureTransaction['amount']['value'] / 100;
                            $currencyCode =  $captureTransaction['amount']['currencyCode'];
                            $paymentId = $paymentId.'Amount : '.$currencyCode.$value.' | ';
                        }
                    }
                }

                if (isset($response['_embedded']['payment'][0]['_embedded']['cnp:refund']) && is_array($response['_embedded']['payment'][0]['_embedded']['cnp:refund'])) {
                    $refundedAmount = 0;
                    foreach ($response['_embedded']['payment'][0]['_embedded']['cnp:refund'] as $refundTransaction) {
                        if (isset($refundTransaction['state']) && ($refundTransaction['state'] == 'SUCCESS') && isset($refundTransaction['amount']['value'])) {
                            $value = $refundTransaction['amount']['value'] / 100;
                            $currencyCode =  $refundTransaction['amount']['currencyCode'];
                            $refundedAmount = $refundedAmount+$value;
                        }
                    }
                    $paymentId = $paymentId.'Total Refunded : '.$currencyCode.$refundedAmount.' | ';
                }
                $amount = '';
            }

            $created = date('Y-m-d H:i:s');
            return $message.$status.$state.$paymentId.$amount.$created;
        } else {
            return $message;
        }
    }

    /**
     * Update Status Await 3ds
     *
     * @param array $data
     * @return bool
     */
    public static function updateStatusAwait3ds($response)
    {
        $command = new Command();
        if (isset($response->merchantOrderReference)) {
            $order = new Order($response->merchantOrderReference);
            if (Validate::isLoadedObject($order)) {
                $order->setCurrentState((int)Configuration::get('NGENIUS_AWAIT_3DS'));
                $ngeniusOrder = [
                    'status' => 'NGENIUS_AWAIT_3DS',
                    'reference' => $response->orderReference,
                ];
                $command->updateNgeniusNetworkinternational($ngeniusOrder);
                $command->addCustomerMessage($response = null, $order);
            }
        }
    }

    /**
     * Update Nngenius order table by id_order
     *
     * @param array $data
     * @return bool
     */
    public static function updateNngeniusTable($data)
    {
        if (Db::getInstance()->update(
            'ngenius_networkinternational',
            $data,
            'id_order = "'.pSQL($data['id_order']).'"'
        )) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set flag status
     *
     * @param string $key
     * @param string $message
     * @return bool
     */
    public static function setFlagStatus($key, $message)
    {
        $cookie = Context::getContext()->cookie;
        $cookie->__set($key, $message);
        $cookie->write();
        return true;
    }

    /**
     * Hosted Session Ngenius Error Message.
     *
     * @return true
     */
    public function addNgeniusErrorMessage($message)
    {
        $cookie = Context::getContext()->cookie;
        $cookie->__set('hsngenius_errors', $message);
        $cookie->write();
        return true;
    }

    /**
     * Gets Order Item Query Api
     *
     * @param array $ngeniusOrder
     * @return bool
     */
    public function orderItemQueryApi($ngeniusOrder)
    {
        $response = $this->getOrderStatusRequest($ngeniusOrder['reference']);
        $response = json_decode(json_encode($response), true);
        return $this->processOrderItem($response, $ngeniusOrder);
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
     * Process Order item.
     *
     * @param array $response
     * @param array $ngeniusOrder
     * @return bool
     */
    public function processOrderItem($response, $ngeniusOrder)
    {
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

            $state = isset($response['_embedded']['payment'][0]['state']) ? $response['_embedded']['payment'][0]['state'] : null;
            
            if ($state == 'CAPTURED') {
                // refund array
                if (isset($response['_embedded']['payment'][0]['_embedded']['cnp:refund']) && is_array($response['_embedded']['payment'][0]['_embedded']['cnp:refund'])) {
                    $refundAmount = 0;
                    foreach ($response['_embedded']['payment'][0]['_embedded']['cnp:refund'] as $key => $refund) {
                        if (isset($refund['state']) && ($refund['state'] == 'SUCCESS') && isset($refund['amount']['value'])) {
                            $refundAmount += $refund['amount']['value'];
                        }
                    }
                    $refundAmount = $refundAmount/100;
                }
                // capture array
                if (isset($response['_embedded']['payment'][0]['_embedded']['cnp:capture']) && is_array($response['_embedded']['payment'][0]['_embedded']['cnp:capture'])) {
                    $lastTransaction = end($response['_embedded']['payment'][0]['_embedded']['cnp:capture']);
                    $captureAmount = 0;
                    foreach ($response['_embedded']['payment'][0]['_embedded']['cnp:capture'] as $key => $capture) {
                        if (isset($capture['state']) && ($capture['state'] == 'SUCCESS') && isset($capture['amount']['value'])) {
                            $captureAmount += $capture['amount']['value'];
                        }
                    }
                    
                    if (isset($lastTransaction['_links']['self']['href'])) {
                        $transactionArr = explode('/', $lastTransaction['_links']['self']['href']);
                        $transactionId = end($transactionArr);
                    } elseif ($lastTransaction['_links']['cnp:refund']['href']) {
                        $transactionArr = explode('/', $lastTransaction['_links']['cnp:refund']['href']);
                        $transactionId = $transactionArr[count($transactionArr)-2];
                    }
                }
                // status
                if ($ngeniusOrder['amount'] == $refundAmount) {
                    $status = 'NGENIUS_FULLY_REFUNDED';
                } elseif ($refundAmount > 0) {
                    $status = 'NGENIUS_PARTIALLY_REFUNDED';
                } else {
                    $status = ($response['action'] == 'SALE') ? 'NGENIUS_COMPLETE' : 'NGENIUS_FULLY_CAPTURED';
                }
            } elseif ($state == 'AUTHORISED') {
                $status = ($response['action'] == 'SALE') ? 'NGENIUS_COMPLETE' : 'NGENIUS_AUTHORISED';
            } elseif ($state == 'REVERSED') {
                $status = 'NGENIUS_AUTH_REVERSED';
            } elseif ($state == 'FAILED' || $state == 'POST_AUTH_REVIEW') {
                $status = 'NGENIUS_FAILED';
            } elseif ($state == 'AWAIT_3DS') {
                $status = 'NGENIUS_AWAIT_3DS';
            } elseif ($state == 'STARTED') {
                $status = 'NGENIUS_PENDING';
            }
            
            // order allready updated
            if ($status == $ngeniusOrder['status']) {
                return false;
            }
            // order confirmation email
            if ($state == 'CAPTURED' || $state == 'AUTHORISED') {
                $command->sendOrderConfirmationMail($order);
            }
            
            if (isset($status) && isset($state)) {
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
                    'result_message' => $resultMessage,
                    'api_type'      => 'queryapi',
                ];
                    
                // saved card
                if (isset($response['_embedded']['payment'][0]['savedCard']) && isset($ngeniusOrder['ch_saved_card']) && $ngeniusOrder['ch_saved_card'] == 1) {
                    $savedCard = $response['_embedded']['payment'][0]['savedCard'];
                    $command->setCustomerSavedCard($savedCard, $order->id_customer);
                }

                $command->updateNgeniusNetworkinternational($data);
                $command->updatePsOrderPayment($command->getOrderPaymentRequest($response), $order);
                $command->addCustomerMessage($response, $order, $queryApi = true);
                $command->setQueryApiCookie();
                $order->setCurrentState((int)Configuration::get($status));
                if ($status == "NGENIUS_FAILED") {
                    $command->reinjectQuantity($order->id);
                }
                return true;
            }
        }
    }

    /**
     * Customer Saved Card.
     *
     * @param array $savedCard
     * @param int $customerId
     * @return bool
     */
    public function setCustomerSavedCard($savedCard, $customerId)
    {
        $data = [
            'id_customer' => $customerId,
            'saved_card' => json_encode($savedCard),
        ];
        
        $savedCardsArr = $this->getCustomerSavedCards($customerId);
        if ($savedCardsArr == false || $this->checkCardSaved($savedCardsArr, $savedCard['maskedPan'], $savedCard['cardToken']) == false) {
            $this->addCustomerSavedCard($data);
        }
        return true;
    }

    /**
     *  Check customer saved or not
     *
     * @param array $savedCards
     * @return bool
     */
    public function checkCardSaved($savedCardsArr, $maskedPan, $cardToken)
    {
        $flag = false;
        foreach ($savedCardsArr as $value) {
            $savedCard = json_decode($value['saved_card'], true);
            if ($savedCard['maskedPan'] == $maskedPan && $savedCard['cardToken'] == $cardToken) {
                $flag = true;
            }
        }
        return $flag;
    }

    /**
     * Gets Customer Saved Card items
     *
     * @param int $customerId
     * @return bool
     */
    public function getCustomerSavedCards($customerId)
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from("customer_savedcard")
            ->where('id_customer ="'.pSQL($customerId).'"');
        //return Db::getInstance()->getRow($sql);
        return Db::getInstance()->ExecuteS($sql);
    }

    /**
     * Gets Customer Saved Card item
     *
     * @param int $customerId
     * @param int $savedCardId
     * @return bool
     */
    public function getCustomerSavedCard($customerId, $savedCardId)
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from("customer_savedcard")
            ->where('id_customer ="'.pSQL($customerId).'" and id ="'.pSQL($savedCardId).'"');
        return Db::getInstance()->getRow($sql);
    }

    /**
     * set Customer Saved Card
     *
     * @param array $data
     * @return bool
     */
    public function addCustomerSavedCard($data)
    {
        $insertData = array(
            'id_customer' => (int) $data['id_customer'],
            'saved_card' => pSQL($data['saved_card']),
        );
        if (Db::getInstance()->insert("customer_savedcard", $insertData)) {
            return true;
        } else {
            Logger::addLog('not saved customer saved card!');
            return false;
        }
    }

    /**
     * Delete Customer Saved Card
     *
     * @param int $id
     * @return bool
     */
    public function deleteCustomerSavedCard($id)
    {
        if (Db::getInstance()->delete('customer_savedcard', 'id = "'.pSQL($id).'"')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set flag status
     *
     * @return bool
     */
    public static function setQueryApiCookie()
    {
        $cookie = Context::getContext()->cookie;
        $cookie->__set('queryApi', true);
        $cookie->write();
        return true;
    }

    /**
     * Gets Order Payment Request
     *
     * @param array $response
     * @return array
     */
    public static function getOrderPaymentRequest($response)
    {
        $paymentMethod = $response['_embedded']['payment'][0]['paymentMethod'];
        if (isset($response['_embedded']['payment'][0]['state'])) {
            $transactionIdRes = explode(":", $response['_embedded']['payment'][0]['_id']);
            $transactionId = end($transactionIdRes);
        }
        $orderPayment = [
            'id_order' => $response['merchantOrderReference'],
            'amount' => $response['amount']['value'],
            'transaction_id' => isset($transactionId) ? $transactionId : null,
            'card_number' => isset($paymentMethod['pan']) ? $paymentMethod['pan'] : null,
            'card_brand' => isset($paymentMethod['name']) ? $paymentMethod['name'] : null,
            'card_expiration' => isset($paymentMethod['expiry']) ? $paymentMethod['expiry'] : null,
            'card_holder' => isset($paymentMethod['cardholderName']) ? $paymentMethod['cardholderName'] : null,
        ];
        return $orderPayment;
    }

    /**
     * set Ngenius Order Email Content
     *
     * @param array $data
     * @return bool
     */
    public function addNgeniusOrderEmailContent($data)
    {
        if (Db::getInstance()->insert("ngenius_order_email_content", $data)) {
            return true;
        } else {
            Logger::addLog('not saved ngenius order confirmation data!');
            return false;
        }
    }

    /**
     * Gets Ngenius Order Email Content
     *
     * @param int $customerId
     * @param int $savedCardId
     * @return bool
     */
    public function getNgeniusOrderEmailContent($idOrder)
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from("ngenius_order_email_content")
            ->where('id_order ="'.pSQL($idOrder).'"');
        return Db::getInstance()->getRow($sql);
    }

    /**
     * Update Ngenius Order Email Content
     *
     * @param array $data
     * @return bool
     */
    public static function updateNgeniusOrderEmailContent($data)
    {
        return Db::getInstance()->update(
            'ngenius_order_email_content',
            $data,
            'id_order = "'.pSQL($data['id_order']).'"'
        );
    }

    /**
     * send order confirmation email
     *
     * @param object $order
     * @return bool
     */
    public function sendOrderConfirmationMail($order)
    {
        $command = new Command();
        $customer = new Customer((int)$order->id_customer);
        $orderConfirmationData = $command->getNgeniusOrderEmailContent($order->id);
        if ($orderConfirmationData) {
            $data = unserialize($orderConfirmationData['data']);
            Mail::Send(
                (int)$order->id_lang,
                'order_conf',
                Mail::l('Order confirmation', (int)$order->id_lang),
                $data,
                $customer->email,
                $customer->firstname.' '.$customer->lastname,
                null,
                null,
                $file_attachement,
                null,
                _PS_MAIL_DIR_,
                false,
                (int)$order->id_shop
            );
            $mailData = array(
                'id_order' => (int) $order->id,
                'email_send' =>(int) 1,
                'sent_at' => date('Y-m-d H:i:s'),
            );
            $command->updateNgeniusOrderEmailContent($mailData);
            return true;
        }
        return false;
    }

    /**
     * Gets getXCorilationId.
     *
     * @param string $type
     */
    public function getXCorilationId($header)
    {
        $headers = array();
        foreach (explode("\r\n", $header) as $i => $line) {
            if ($i === 0){
                $headers['http_code'] = $line;
            }else {
                list ($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }
        if ($headers['x-correlation-id']) {
            return $headers['x-correlation-id'];
        } elseif ($headers['X-Correlation-Id']) {
            return $headers['X-Correlation-Id'];
        } else {
            return null;
        }       
    }

}
