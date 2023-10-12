<?php

use NGenius\Logger;
use NGenius\Command;
use NGenius\Config\Config;

class NGeniusRedirectModuleFrontController extends ModuleFrontController
{
    public const NGENIUS_CAPTURE_LITERAL = 'cnp:capture';

    /**
     * Processing of API response
     *
     * @return void
     * @throws Exception
     */
    public function postProcess()
    {
        $logger = new Logger();
        $config = new Config();
        $command = new Command();
        $log = [];
        $log['path'] = __METHOD__;
        $ref = $_REQUEST['ref'];

        $isValidRef = preg_match('/([a-z0-9]){8}-([a-z0-9]){4}-([a-z0-9]){4}-([a-z0-9]){4}-([a-z0-9]){12}$/', $ref);

        if (!$isValidRef) {
            Tools::redirect(\Tools::getHttpHost(true) . __PS_BASE_URI__.'module/ngenius/failedorder');
        }

        $ngeniusOrder = $this->getNgeniusOrder($ref);

        $cart_id = $ngeniusOrder["id_cart"];

        $this->context->cart = new Cart($cart_id);

        $this->context->cookie->id_cart = $cart_id;

        $cart = $this->context->cart;

        $response = $command->getOrderStatusRequest($ref);

        $orderState = $response['_embedded']['payment'][0]['state'] ?? null;

        if ($orderState != 'AUTHORISED'
            && $orderState != 'CAPTURED'
            && $orderState != 'PURCHASED'
        ) {
            $command::deleteNgeniusOrder($cart_id);
            $log['redirected_to'] = 'module/ngenius/failedorder';
            $logger->addLog($log);
            /** @noinspection PhpUndefinedConstantInspection */
            Tools::redirect(\Tools::getHttpHost(true) . __PS_BASE_URI__.'module/ngenius/failedorder');
        }

        if (!Order::getByCartId($cart_id)) {

            $this->module->validateOrder(
                (int)$cart_id,
                $config->getInitialStatus(),
                (float)$ngeniusOrder["amount"],
                $this->module->l($config->getModuleName(), 'validation'),
                null,
                [],
                $cart->id_currency,
                false,
                $cart->secure_key
            );
        }

        $order = Order::getByCartId($cart_id);

        $this->updateNgeniusOrderStatusToProcessing($ngeniusOrder, $order);

        $order->setCurrentState((int)Configuration::get($config->getOrderStatus().'_PROCESSING'));

        $this->processOrder($response, $ngeniusOrder);

        $redirectLink = $this->module->getOrderConfUrl($order);
        $log['redirected_to'] = $redirectLink;
        $logger->addLog($log);
        Tools::redirectLink($redirectLink);
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
        $command = new Command();
        $config = new Config();
        $order = Order::getByCartId($ngeniusOrder["id_cart"]);
        $captureAmount = 0;
        $transactionId = null;
        if (Validate::isLoadedObject($order)) {
            $paymentId = $this->getPaymentId($response);
            $state = $response['_embedded']['payment'][0]['state'] ?? null;
            switch ($state) {
                case 'CAPTURED':
                    $captureAmount = $this->getCapturedAmount($response, $ngeniusOrder['amount']);
                    $lastTransaction = $this->getLastTransaction($response);
                    $transactionId = $this->getTransactionId($lastTransaction);
                    $status = $config->getOrderStatus().'_COMPLETE';
                    $command->sendOrderConfirmationMail($order);
                    break;

                case 'AUTHORISED':
                    $command->sendOrderConfirmationMail($order);
                    $status = $config->getOrderStatus().'_AUTHORISED';
                    break;

                case 'PURCHASED':
                    $command->sendOrderConfirmationMail($order);
                    $status = $config->getOrderStatus().'_COMPLETE';
                    break;

                case 'FAILED':
                    $status = $config->getOrderStatus().'_DECLINED';
                    break;

                default:
                    $status = $config->getOrderStatus().'_PENDING';
                    break;
            }
            if (isset($state)) {
                if ($cronJob) {
                    $this->updateNgeniusOrderStatusToProcessing($ngeniusOrder, $order);
                    $order->setCurrentState((int)Configuration::get($config->getOrderStatus().'_PROCESSING'));
                }
                $authResponse = $response['_embedded']['payment'][0]['authResponse'] ?? null;
                $data = [
                    'id_payment' => $paymentId,
                    'capture_amt' => $captureAmount,
                    'status' => $status,
                    'state' => $state,
                    'reference' => $ngeniusOrder['reference'],
                    'id_capture' => $transactionId,
                    'auth_response' => json_encode($authResponse, true),
                ];
                $command->updateNngeniusNetworkinternational($data);
                $command->updatePsOrderPayment($this->getOrderPaymentRequest($response));
                $command->addCustomerMessage($response, $order);
                $order->setCurrentState((int)Configuration::get($status));
                return true;
            }
        }
    }

    /**
     * Gets Captured Amount
     *
     * @param array $response
     * @return string
     */
    public function getCapturedAmount($response, $orderAmount)
    {
        $captureAmount = 0;
        if (isset($response['_embedded']['payment'][0]['_embedded'][self::NGENIUS_CAPTURE_LITERAL])
            && is_array($response['_embedded']['payment'][0]['_embedded'][self::NGENIUS_CAPTURE_LITERAL])
        ) {
            foreach ($response['_embedded']['payment'][0]['_embedded'][self::NGENIUS_CAPTURE_LITERAL] as $capture) {
                if (isset($capture['state']) && ($capture['state'] == 'SUCCESS')
                    && isset($capture['amount']['value'])
                ) {
                    $captureAmount = $orderAmount;
                }
            }
        }
        return $captureAmount;
    }

    /**
     * Gets Last Transaction
     *
     * @param array $response
     * @return string
     */
    public function getLastTransaction($response)
    {
        $lastTransaction = '';
        if (isset($response['_embedded']['payment'][0]['_embedded'][self::NGENIUS_CAPTURE_LITERAL])
            && is_array($response['_embedded']['payment'][0]['_embedded'][self::NGENIUS_CAPTURE_LITERAL])
        ) {
            $lastTransaction = end($response['_embedded']['payment'][0]['_embedded'][self::NGENIUS_CAPTURE_LITERAL]);
        }
        return $lastTransaction;
    }

    /**
     * Gets payment id
     *
     * @param array $response
     * @return string
     */
    public function getPaymentId($response)
    {
        $paymentId = '';
        if (isset($response['_embedded']['payment'][0]['_id'])) {
            $transactionIdRes = explode(":", $response['_embedded']['payment'][0]['_id']);
            $paymentId = end($transactionIdRes);
        }
        return $paymentId;
    }

    /**
     * Gets transaction Id
     *
     * @param array $response
     * @return string
     */
    public function getTransactionId($lastTransaction)
    {
        $transactionId = '';
        if (isset($lastTransaction['_links']['self']['href'])) {
            $transactionArr = explode('/', $lastTransaction['_links']['self']['href']);
            $transactionId = end($transactionArr);
        } elseif ($lastTransaction['_links']['cnp:refund']['href'] ?? false) {
            $transactionArr = explode('/', $lastTransaction['_links']['cnp:refund']['href']);
            $transactionId = $transactionArr[count($transactionArr)-2];
        }
        return $transactionId;
    }

    /**
     * Gets Order Payment Request
     *
     * @param array $response
     * @return array
     */
    public static function getOrderPaymentRequest(array $response): array
    {
        $paymentMethod = $response['_embedded']['payment'][0]['paymentMethod'] ?? null;
        if (isset($response['_embedded']['payment'][0]['state'])) {
            $transactionIdRes = explode(":", $response['_embedded']['payment'][0]['_id']);
            $transactionId = end($transactionIdRes);
        }
        return [
            'id_order' => $response['merchantOrderReference'],
            'amount' => $response['amount']['value'],
            'transaction_id' => $transactionId ?? null,
            'card_number' => $paymentMethod['pan'] ?? null,
            'card_brand' => $paymentMethod['name'] ?? null,
            'card_expiration' => $paymentMethod['expiry'] ?? null,
            'card_holder' => $paymentMethod['cardholderName'] ?? null,
        ];
    }

    /**
     * Gets ngenius order by reference
     *
     * @param string $reference
     * @return array|bool
     */
    public static function getNgeniusOrder(string $reference): array|bool
    {
        $sql = new \DbQuery();
        $sql->select('*')->from("ning_online_payment")->where('reference ="'.pSQL($reference).'"');
        return  \Db::getInstance()->getRow($sql);
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
        $config = new Config();
        $ngeniusOrder = [
            'status' => $config->getOrderStatus().'_PROCESSING',
            'reference' => $ngenusOrder['reference'],
            'id_order'  => $order->id
        ];
        $command->updateNngeniusNetworkinternational($ngeniusOrder);
        $command->addCustomerMessage(null, $order);
        return true;
    }
}
