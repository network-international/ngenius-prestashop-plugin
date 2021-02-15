<?php
/**
 * Hosted Session Ngenius TransactionCapture
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
include_once _PS_MODULE_DIR_.'hsngenius/classes/Http/AbstractTransaction.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Command.php';

class TransactionCapture extends AbstractTransaction
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
     * @param array $responseEnc
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
            // capture amount
            $capturedAmt = 0;
            if (isset($response['_embedded']['cnp:capture']) && is_array($response['_embedded']['cnp:capture'])) {
                $lastTransaction = end($response['_embedded']['cnp:capture']);
                if (isset($lastTransaction['state']) && ($lastTransaction['state'] == 'SUCCESS') && isset($lastTransaction['amount']['value'])) {
                    $capturedAmt = $lastTransaction['amount']['value'];
                }
            }
            
            // Transaction Id
            $transactionId = '';
            if (isset($lastTransaction['_links']['self']['href'])) {
                $transactionArr = explode('/', $lastTransaction['_links']['self']['href']);
                $transactionId = end($transactionArr);
            }
            if (isset($lastTransaction['state']) && $lastTransaction['state'] == 'SUCCESS') {
                $state = isset($response['state']) ? $response['state'] : '';
                $orderReference = isset($response['orderReference']) ? $response['orderReference'] : '';
                $orderStatus = 'NGENIUS_FULLY_CAPTURED';
                $ngeniusOrder = [
                    'capture_amt' => $capturedAmt > 0 ? $capturedAmt / 100 : 0,
                    'status' => $orderStatus,
                    'state' => $state,
                    'reference' => $orderReference,
                    'id_capture' => $transactionId,
                ];

                $command->updateNgeniusNetworkinternational($ngeniusOrder);
                $order = new Order($response['merchantOrderReference']);
                $command->addCustomerMessage(json_decode(json_encode($response), true), $order);
                $command->setFlagStatus('hsngenius_fully_captured', true);
                $order->setCurrentState((int)Configuration::get($orderStatus));
                
                return [
                    'result' => [
                        'captured_amt' => $capturedAmt,
                        'state' => $state,
                        'order_status' => $orderStatus,
                        'payment_id' => $transactionId
                    ]
                ];
            } else {
                Logger::addLog('Capture failed');
                Logger::addLog(json_encode($response));
                return false;
            }
        }
    }
}
