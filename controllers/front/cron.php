<?php

/**
 * Hosted Session Ngenius Cron Module Front Controller
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

include_once _PS_MODULE_DIR_.'hsngenius/classes/Command.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/CronLogger.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Config/Config.php';
include_once _PS_MODULE_DIR_.'hsngenius/controllers/front/redirect.php';

class HsngeniusCronModuleFrontController extends HsngeniusRedirectModuleFrontController
{

    /**
     * Cron Task.
     *
     * @return void
     */
    public function postProcess()
    {
        $config = new Config();
        
        if ($this->cronTask()) {
            CronLogger::addLog('Cron Works');
            die;
        }
    }


    /**
     * Cron Task.
     *
     * @return bool|void
     */

    public function cronTask()
    {
        $data = [];
        $cronData = [];
        $config = new Config();
        $command = new Command();
        $tries = $config->getQueryApiTries();

        // data from xml
        $config_file = _PS_MODULE_DIR_.'hsngenius/ngeniuscron.xml';
        $xml = @simplexml_load_file($config_file);
        if (isset($xml->number_of_days) && !empty($xml->number_of_days)) {
            $days = intval($xml->number_of_days);
        } else {
            $days = 1;
        }
        if (isset($xml->limit) && !empty($xml->limit)) {
            $limit = intval($xml->limit);
        } else {
            $limit = 10;
        }
        // end
        $sql = new DbQuery();
        $sql->select('*')
            ->from("ngenius_networkinternational")
            ->where(' DATE_ADD(created_at, interval 60 MINUTE) < NOW() AND DATE_ADD(NOW(), interval -'.$days.' DAY) < created_at
                AND (
                        status = "'.pSQL('NGENIUS_PENDING').'" 
                        OR status = "'.pSQL('NGENIUS_PROCESSING').'" 
                        OR status = "'.pSQL('NGENIUS_AWAIT_3DS').'" 
                        OR status = "'.pSQL('NGENIUS_FAILED').'"
                    )  
                AND (`api_type` IS NULL OR `api_type` != "cron")  
            ')
            ->orderBy('nid DESC')
            ->limit($limit);
        
        $ngeniusOrders = Db::getInstance()->executeS($sql);
        
        foreach ($ngeniusOrders as $ngeniusOrder) {
            $cronData = [];
            $data = [];
            $psOrderData = [];
            if (isset($ngeniusOrder['reference']) && !empty($ngeniusOrder['reference'])) {
                $cronData['id_cart'] = $ngeniusOrder['id_cart'];
                $cronData['id_order'] = $ngeniusOrder['id_order'];
                $cronData['try_num'] = $ngeniusOrder['query_api_tries'] + 1;
                $cronData['from_state'] = $ngeniusOrder['state'];
                
                $data['reference'] = $ngeniusOrder['reference'];
                $data['query_api_tries'] = (int) $cronData['try_num'];
                
                $response = $this->getOrderStatusRequest($ngeniusOrder['reference']);
                $response = json_decode(json_encode($response), true);

                if ($response && isset($response['_embedded']['payment']) && is_array($response['_embedded']['payment'])) {
                    $payment = $response['_embedded']['payment'][0];
                    $state = isset($payment['state']) ? $payment['state'] : null;
                    $amountValue = isset($payment['amount']['value']) ? $payment['amount']['value']/100 : null;
                    $amountCurrency = isset($payment['amount']['currencyCode']) ? $payment['amount']['currencyCode'] : null;
                    $authRes = isset($payment['authResponse']) ? $payment['authResponse'] : [];
                    $cronData['response'] = $state.'|'.$amountCurrency.$amountValue.'|'.json_encode($authRes);
                    $cronData['status'] = 'SUCCESS';
                    
                    if ($tries <= $cronData['try_num']) {
                        if ($ngeniusOrder['state'] == 'FAILED' && $state == 'FAILED') {
                            $cronData['status'] = 'ORDER UPDATED';
                            $data['api_type'] = 'cron';
                        } else {
                            if ($this->processOrder($response, $ngeniusOrder, $cronJob = true)) {
                                $cronData['status'] = 'ORDER UPDATED';
                            } else {
                                $cronData['status'] = 'ORDER NOT UPDATED';
                            }
                        }
                    }
                    CronLogger::addLog(json_encode($this->getNgeniusOrder($ngeniusOrder['reference'])).json_encode($response));
                } else {
                    $cronData['status'] = 'FAILED';
                }
                
                $command->updateNgeniusNetworkinternational($data);
                CronLogger::addDbLog($cronData);
            } else {
                if ($ngeniusOrder['status'] != 'NGENIUS_FAILED') {
                    $cronData['id_cart']    = $ngeniusOrder['id_cart'];
                    $cronData['id_order']   = $ngeniusOrder['id_order'];
                    $cronData['try_num']    = $ngeniusOrder['query_api_tries'] + 1;
                    $cronData['from_state'] = $ngeniusOrder['state'];
                    $cronData['response']   = $ngeniusOrder['currency'].round($ngeniusOrder['amount'], 2);
                    $cronData['status']     = 'SUCCESS';

                    $data['query_api_tries'] = (int) $cronData['try_num'];
                    $data['id_order'] = (int) $ngeniusOrder['id_order'];

                    if ($tries === $cronData['try_num']) {
                        $order = new Order($ngeniusOrder['id_order']);
                        $order->setCurrentState((int)Configuration::get('NGENIUS_FAILED'));
                        $data['status'] = 'NGENIUS_FAILED';
                        $data['api_type'] = 'cron';
                        $cronData['status'] = 'ORDER UPDATED';

                        // ps order payment
                        $psOrderData = [
                            'id_order'      => $ngeniusOrder['id_order'],
                            'amount'        => $ngeniusOrder['amount'] * 100,
                            'transaction_id' => null,
                            'card_number'   => null,
                            'card_brand'    =>  null,
                            'card_expiration' => null,
                            'card_holder'   => null,
                        ];
                        $command->updatePsOrderPayment($psOrderData, $order);
                    }
                    $command->updateNngeniusTable($data);
                    CronLogger::addDbLog($cronData);
                }
            }
        }
        return true;
    }
}
