<?php

namespace NGenius;

use Exception;
use NGenius\Config\Config;
use NGenius\Http\TransactionAuth;
use NGenius\Http\TransactionCapture;
use NGenius\Http\TransactionOrderRequest;
use NGenius\Http\TransactionPurchase;
use NGenius\Http\TransactionRefund;
use NGenius\Http\TransactionSale;
use NGenius\Http\TransactionVoid;
use NGenius\Request\VoidRequest;
use NGenius\Request\SaleRequest;
use NGenius\Request\PurchaseRequest;
use NGenius\Request\TokenRequest;
use NGenius\Request\RefundRequest;
use NGenius\Request\CaptureRequest;
use NGenius\Request\OrderStatusRequest;
use NGenius\Request\AuthorizationRequest;
use NGenius\Validator\VoidValidator;
use NGenius\Validator\RefundValidator;
use NGenius\Validator\CaptureValidator;
use NGenius\Validator\ResponseValidator;
use Ngenius\NgeniusCommon\NgeniusHTTPCommon;
use Ngenius\NgeniusCommon\NgeniusHTTPTransfer;
use Ngenius\NgeniusCommon\Formatter\ValueFormatter;

class Command extends Model
{

    const NGENIUS_CAPTURE_LITERAL = 'cnp:capture';
    const NGENIUS_REFUND_LITERAL  = 'cnp:refund';
    const AMOUNT_LITERAL          = 'Amount : ';

    private NgeniusHTTPTransfer $httpTransfer;
    private Config $config;
    private ResponseValidator $responseValidator;

    public function __construct()
    {
        $this->config            = new Config();
        $this->responseValidator = new ResponseValidator();
        $this->httpTransfer      = new NgeniusHTTPTransfer("");
        $this->httpTransfer->setHttpVersion($this->config->getHTTPVersion());
    }

    /**
     * Order Authorize.
     *
     * @param array $order
     * @param float $amount
     *
     * @return bool|string
     * @throws Exception
     */
    public function authorize($order, float $amount): bool|string
    {
        $authorizationRequest = new AuthorizationRequest();
        $transactionAuth      = new TransactionAuth();

        $requestData = $authorizationRequest->build($order, $amount);

        if (is_array($requestData)) {
            $this->buildHttpTransfer($requestData);
            $response = NgeniusHTTPCommon::placeRequest($this->httpTransfer);

            return $this->responseValidator->validate($transactionAuth->postProcess($response) ?? []);
        }

        return false;
    }

    /**
     * Order sale.
     *
     * @param array $order
     * @param float $amount
     *
     * @return bool|string
     * @throws Exception
     */
    public function order($order, float $amount): bool|string
    {
        $saleRequest     = new SaleRequest();
        $transactionSale = new TransactionSale();

        $requestData = $saleRequest->build($order, $amount);

        if (is_array($requestData)) {
            $this->buildHttpTransfer($requestData);
            $response = NgeniusHTTPCommon::placeRequest($this->httpTransfer);

            return $this->responseValidator->validate($transactionSale->postProcess($response) ?? []);
        }

        return false;
    }

    /**
     * Order purchase.
     *
     * @param array $order
     * @param float $amount
     *
     * @return bool|string
     * @throws Exception
     */
    public function purchase($order, float $amount): bool|string
    {
        $purchaseRequest     = new PurchaseRequest();
        $transactionPurchase = new TransactionPurchase();

        $requestData = $purchaseRequest->build($order, $amount);

        if (is_array($requestData)) {
            $this->buildHttpTransfer($requestData);
            $response = NgeniusHTTPCommon::placeRequest($this->httpTransfer);

            return $this->responseValidator->validate($transactionPurchase->postProcess($response) ?? []);
        }

        return false;
    }

    /**
     * Order capture.
     *
     * @param array $ngeniusOrder
     *
     * @return bool|string
     * @throws Exception
     */
    public function capture(array $ngeniusOrder): bool|string
    {
        $captureRequest     = new CaptureRequest();
        $captureValidator   = new CaptureValidator();
        $transactionCapture = new TransactionCapture();

        $requestData = $captureRequest->build($ngeniusOrder);

        if (is_array($requestData)) {
            $this->buildHttpTransfer($requestData);
            $response = NgeniusHTTPCommon::placeRequest($this->httpTransfer);

            if ($captureValidator->validate($transactionCapture->postProcess($response) ?? [])) {
                return $response;
            }
        }

        return false;
    }

    /**
     * Order void.
     *
     * @param array $ngeniusOrder
     *
     * @return bool|string
     * @throws Exception
     */
    public function void(array $ngeniusOrder): bool|string
    {
        $voidRequest     = new VoidRequest();
        $voidValidator   = new VoidValidator();
        $transactionVoid = new TransactionVoid();

        $requestData = $voidRequest->build($ngeniusOrder);
        if (is_array($requestData)) {
            $this->buildHttpTransfer($requestData);
            $response = NgeniusHTTPCommon::placeRequest($this->httpTransfer);

            if ($voidValidator->validate($transactionVoid->postProcess($response) ?? [])) {
                return $response;
            }
        }

        return false;
    }

    /**
     * Order refund.
     *
     * @param array $ngeniusOrder
     *
     * @return bool
     * @throws Exception
     */
    public function refund(array $ngeniusOrder): bool|string
    {
        $refundRequest     = new RefundRequest();
        $refundValidator   = new RefundValidator();
        $transactionRefund = new TransactionRefund();

        $requestData = $refundRequest->build($ngeniusOrder);

        if (is_array($requestData)) {
            $this->buildHttpTransfer($requestData);
            $response = NgeniusHTTPCommon::placeRequest($this->httpTransfer);

            if ($refundValidator->validate($transactionRefund->postProcess($response) ?? [])) {
                return $response;
            }
        }

        return false;
    }

    /**
     * Update Prestashop Order Payment table
     *
     * @param array $data
     *
     * @return bool
     */
    public static function updatePsOrderPayment(array $data): bool
    {
        $logger                        = new Logger();
        $command                       = new Command();
        $log                           = array();
        $order                         = new \Order($data['id_order']);
        $log['path']                   = __METHOD__;
        $orderPayment                  = new \OrderPayment();
        $orderPayment->order_reference = pSQL($command->getOrderReference($data['id_order']));
        $orderPayment->id_currency     = (int)$order->id_currency;
        $orderPayment->amount          = (float)($data['amount'] / 100);
        $orderPayment->payment_method  = pSQL('N-Genius Payment Gateway');
        $orderPayment->transaction_id  = pSQL($data['transaction_id']);
        $orderPayment->card_number     = pSQL($data['card_number']);
        $orderPayment->card_brand      = pSQL($data['card_brand']);
        $orderPayment->card_expiration = pSQL($data['card_expiration']);
        $orderPayment->card_holder     = pSQL($data['card_holder']);
        if ($orderPayment->add()) {
            $log['ps_order_payment'] = true;
            $logger->addLog($log);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets Order Reference
     *
     * @param int $orderId
     *
     * @return bool|array|null
     */
    public static function getOrderReference(int $orderId): bool|array|null
    {
        $order = new \Order($orderId);
        if (\Validate::isLoadedObject($order)) {
            return $order->reference;
        } else {
            return null;
        }
    }

    /**
     * Add Customer Message
     *
     * @param array|null $response
     * @param array $order
     *
     * @return bool
     */
    public static function addCustomerMessage(?array $response, $order): bool
    {
        $logger      = new Logger();
        $command     = new Command();
        $log         = array();
        $log['path'] = __METHOD__;
        $command->addCustomerThread($order);
        $thread                               = $command->getCustomerThread($order);
        $message                              = $command->buildCustomerMessage($response, $order);
        $customer_message                     = new \CustomerMessage();
        $customer_message->id_customer_thread = (int)$thread['id_customer_thread'];
        $customer_message->private            = (int)1;
        $customer_message->message            = pSQL($message);
        if ($customer_message->add()) {
            $log['customer_message'] = $message;
            $logger->addLog($log);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Add Customer Thread
     *
     * @param array $order
     *
     * @return bool
     */
    public static function addCustomerThread($order): bool
    {
        $command = new Command();
        if (!$command->getCustomerThread($order)) {
            $customer_thread              = new \CustomerThread();
            $customer_thread->id_contact  = (int)0;
            $customer_thread->id_customer = (int)$order->id_customer;
            $customer_thread->id_shop     = (int)$order->id_shop;
            $customer_thread->id_order    = (int)$order->id;
            $customer_thread->id_lang     = (int)$order->id_lang;
            $customer                     = new \Customer($order->id_customer);
            $customer_thread->email       = $customer->email;
            $customer_thread->status      = 'open';
            $customer_thread->token       = \Tools::passwdGen(12);

            return ($customer_thread->add()) ? (bool)true : (bool)false;
        } else {
            return false;
        }
    }

    /**
     * build customer message for order
     *
     * @param array|null $response
     * @param array $order
     *
     * @return string
     */
    public static function buildCustomerMessage(?array $response, $order): string
    {
        $command      = new Command();
        $ngeniusOrder = $command->getNgeniusOrder($order->id);

        $message = '';
        if ($ngeniusOrder) {
            $status    = 'Status : ' . $ngeniusOrder['status'] . ' | ';
            $state     = ' State : ' . $ngeniusOrder['state'] . ' | ';
            $paymentId = null;
            $amount    = null;

            if (isset($response['_embedded']['payment'][0])) {
                $paymentIdArr = explode(':', $response['_embedded']['payment'][0]['_id']);
                $paymentId    = 'Transaction ID : ' . end($paymentIdArr) . ' | ';
                $amount       = $command->getTransactionAmount($response) . ' | ';
            }
            // capture
            if (isset($response['_embedded'][self::NGENIUS_CAPTURE_LITERAL])
                && is_array($response['_embedded'][self::NGENIUS_CAPTURE_LITERAL])) {
                $lastTransaction = end($response['_embedded'][self::NGENIUS_CAPTURE_LITERAL]);
                if (isset($lastTransaction['_links']['self']['href'])) {
                    $transactionArr = explode('/', $lastTransaction['_links']['self']['href']);
                    $paymentId      = 'Capture ID : ' . end($transactionArr) . ' | ';
                }
                $amount = $command->getCaptureAmount($lastTransaction);
            }
            // refund
            if (isset($response['_embedded'][self::NGENIUS_REFUND_LITERAL])
                && is_array($response['_embedded'][self::NGENIUS_REFUND_LITERAL])) {
                $lastTransaction = end($response['_embedded'][self::NGENIUS_REFUND_LITERAL]);
                $paymentId       = $command->getRefundPaymentId($lastTransaction);
                $amount          = $command->getRefundAmount($response, $lastTransaction);
            }
            $created = date('Y-m-d H:i:s');

            return $message . $status . $state . $paymentId . $amount . $created;
        } else {
            return $message;
        }
    }

    /**
     * get transaction amount
     *
     * @param $lastTransaction
     *
     * @return string|null
     */
    public function getRefundPaymentId($lastTransaction): ?string
    {
        $paymentId = null;
        if (isset($lastTransaction['_links']['self']['href'])) {
            $transactionArr = explode('/', $lastTransaction['_links']['self']['href']);
            $paymentId      = 'Refunded ID : ' . end($transactionArr) . ' | ';
        }

        return $paymentId;
    }

    /**
     * get transaction amount
     *
     * @param array $response
     *
     * @return string
     */
    public function getTransactionAmount(array $response): ?string
    {
        $amount = null;
        if (isset($response['amount']['value'])) {
            $value        = (float)$response['amount']['value'] / 100;
            $currencyCode = $response['amount']['currencyCode'];
            $value        = ValueFormatter::formatOrderStatusAmount($currencyCode, $value);
            ValueFormatter::formatCurrencyDecimals($currencyCode, $value);

            $amount = self::AMOUNT_LITERAL . $currencyCode . " " . $value . ' | ';
        }

        return $amount;
    }

    /**
     * get transaction amount
     *
     * @param array $lastTransaction
     *
     * @return string
     */
    public function getCaptureAmount(array $lastTransaction): ?string
    {
        $amount = null;
        if (isset($lastTransaction['state'])
            && ($lastTransaction['state'] == 'SUCCESS')
            && isset($lastTransaction['amount']['value'])) {
            $value        = $lastTransaction['amount']['value'] / 100;
            $currencyCode = $lastTransaction['amount']['currencyCode'];

            $value = ValueFormatter::formatOrderStatusAmount($currencyCode, $value);

            ValueFormatter::formatCurrencyDecimals($currencyCode, $value);

            $amount = self::AMOUNT_LITERAL . $currencyCode . " " . $value . ' | ';
        }

        return $amount;
    }

    /**
     * get refund amount
     *
     * @param array $response
     * @param array $lastTransaction
     *
     * @return string
     */
    public function getRefundAmount(array $response, array $lastTransaction): ?string
    {
        $amount = null;
        foreach ($response['_embedded'][self::NGENIUS_REFUND_LITERAL] as $refund) {
            if (isset($refund['state']) && ($refund['state'] == 'SUCCESS') && isset($refund['amount']['value'])) {
                $value        = $refund['amount']['value'] / 100;
                $currencyCode = $lastTransaction['amount']['currencyCode'];

                $value = ValueFormatter::formatOrderStatusAmount($currencyCode, $value);

                ValueFormatter::formatCurrencyDecimals($currencyCode, $value);

                $amount = self::AMOUNT_LITERAL . $currencyCode . " " . $value . ' | ';
            }
        }

        return $amount;
    }

    /**
     * send order confirmation email
     *
     * @param object $order
     *
     * @return bool
     */
    public function sendOrderConfirmationMail(object $order): bool
    {
        $command               = new Command();
        $logger                = new Logger();
        $log                   = [];
        $log['path']           = __METHOD__;
        $customer              = new \Customer((int)$order->id_customer);
        $orderConfirmationData = $command->getNgeniusOrderEmailContent($order->id);
        if ($orderConfirmationData) {
            $data          = unserialize($orderConfirmationData['data']);
            $orderLanguage = new \Language((int)$order->id_lang);
            /** @noinspection PhpUndefinedConstantInspection */
            \Mail::Send(
                (int)$order->id_lang,
                'order_conf',
                \Context::getContext()->getTranslator()->trans(
                    'Order confirmation',
                    array(),
                    'Emails.Subject',
                    $orderLanguage->locale
                ),
                $data,
                $customer->email,
                $customer->firstname . ' ' . $customer->lastname,
                null,
                null,
                null,
                null,
                _PS_MAIL_DIR_,
                false,
                (int)$order->id_shop
            );
            $mailData = array(
                'id_order'   => (int)$order->id,
                'email_send' => (int)1,
                'sent_at'    => date('Y-m-d H:i:s'),
            );
            $command->updateNgeniusOrderEmailContent($mailData);
            $log['order_confirmation_email'] = true;
            $logger->addLog($log);

            return true;
        }

        return false;
    }

    /**
     * Gets Order Status Request
     *
     * @param string $ref
     * @param int|null $storeId
     *
     * @return array
     */
    public function getOrderStatusRequest(string $ref, int $storeId = null): array
    {
        $tokenRequest            = new TokenRequest();
        $orderStatusRequest      = new OrderStatusRequest();
        $transactionOrderRequest = new TransactionOrderRequest;
        $requestData             = [
            'token'   => $tokenRequest->getAccessToken(),
            'request' => $orderStatusRequest->getBuildArray($ref, $storeId),
        ];
        $this->buildHttpTransfer($requestData);

        return $transactionOrderRequest->postProcess(NgeniusHTTPCommon::placeRequest($this->httpTransfer));
    }

    /**
     * @param $requestData
     *
     * @return void
     */
    public function buildHttpTransfer($requestData): void
    {
        $this->httpTransfer->setPaymentHeaders($requestData['token']);
        $this->httpTransfer->setMethod($requestData['request']['method']);
        $this->httpTransfer->setData($requestData['request']['data']);
        $this->httpTransfer->setUrl($requestData['request']['uri']);
    }
}
