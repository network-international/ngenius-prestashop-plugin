<?php

/**
 * Hosted Session Ngenius TransactionRefund
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

include_once _PS_MODULE_DIR_.'hsngenius/classes/Http/AbstractTransaction.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Command.php';
class TransactionRefund extends Abstracttransaction
{

    /**
     * Processing of API request body
     *
     * @param array $data
     * @return string
     */
    protected function preProcess(array $data)
    {
        return json_encode($data);
    }

    /**
     * Processing of API response
     *
     * @param array $response
     * @return array|bool
     */
    protected function postProcess($responseEnc)
    {
        
        $response = json_decode($responseEnc, true);

        $command = new Command();
        if (isset($response['errors']) && is_array($response['errors'])) {
            Logger::addLog(json_encode($response));
            return false;
        } else {
            $captured_amt = 0;
            if (isset($response['_embedded']['cnp:capture']) && is_array($response['_embedded']['cnp:capture'])) {
                foreach ($response['_embedded']['cnp:capture'] as $capture) {
                    if (isset($capture['state']) && ($capture['state'] == 'SUCCESS') && isset($capture['amount']['value'])) {
                        $captured_amt += $capture['amount']['value'];
                    }
                }
            }

            $refunded_amt = 0;
            if (isset($response['_embedded']['cnp:refund']) && is_array($response['_embedded']['cnp:refund'])) {
                $lastTransaction = end($response['_embedded']['cnp:refund']);
                foreach ($response['_embedded']['cnp:refund'] as $refund) {
                    if (isset($refund['state']) && ($refund['state'] == 'SUCCESS') && isset($refund['amount']['value'])) {
                        $refunded_amt += $refund['amount']['value'];
                    }
                }
            }

            $last_refunded_amt = 0;
            if (isset($lastTransaction['state']) && ($lastTransaction['state'] == 'SUCCESS') && isset($lastTransaction['amount']['value'])) {
                $last_refunded_amt = $lastTransaction['amount']['value'] / 100;
            }

            $transactionId = '';
            if (isset($lastTransaction['_links']['self']['href'])) {
                $transactionArr = explode('/', $lastTransaction['_links']['self']['href']);
                $transactionId = end($transactionArr);
            }
            if (isset($lastTransaction['state']) && $lastTransaction['state'] == 'SUCCESS') {
                $orderReference = isset($response['orderReference']) ? $response['orderReference'] : '';
                $state = isset($response['state']) ? $response['state'] : '';

                $captureAmt = $captured_amt > 0 ? $captured_amt / 100 : 0;
                $refundedAmt = $refunded_amt > 0 ? $refunded_amt / 100 : 0;
                
                if (($captureAmt - $refundedAmt) == 0) {
                    $orderStatus = 'NGENIUS_FULLY_REFUNDED';
                    $command->setFlagStatus('hsngenius_fully_refunded', true);
                } else {
                    $orderStatus = 'NGENIUS_PARTIALLY_REFUNDED';
                    $command->setFlagStatus('hsngenius_partially_refunded', true);
                }
                
                $ngeniusOrder = [
                    'capture_amt' => (float)($captureAmt - $refundedAmt),
                    'status' => $orderStatus,
                    'state' => $state,
                    'reference' => $orderReference,
                ];

                $command->updateNgeniusNetworkinternational($ngeniusOrder);
                $order = new Order($response['merchantOrderReference']);
                $command->addCustomerMessage(json_decode(json_encode($response), true), $order);
                $order->setCurrentState((int)Configuration::get($orderStatus));
                
                return [
                    'result' => [
                        'total_refunded' => $refunded_amt,
                        'refunded_amt' => $last_refunded_amt,
                        'state' => $state,
                        'order_status' => $orderStatus,
                        'payment_id' => $transactionId
                    ]
                ];
            } else {
                Logger::addLog('Refund failed');
                Logger::addLog(json_encode($response));
                return false;
            }
        }
    }
}
