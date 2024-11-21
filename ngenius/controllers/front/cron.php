<?php

use NGenius\Command;
use NGenius\CronLogger;
use NGenius\Config\Config;
use Ngenius\NgeniusCommon\Processor\ApiProcessor;

/** @noinspection PhpUndefinedConstantInspection */
include_once _PS_MODULE_DIR_ . 'ngenius/controllers/front/redirect.php';

class NGeniusCronModuleFrontController extends NGeniusRedirectModuleFrontController
{
    /**
     * Cron Task.
     *
     * @return void
     */
    public function postProcess(): void
    {
        $command    = new Command();
        $cronLogger = new CronLogger();
        $token      = $_REQUEST['token'];
        if (isset($token) && $token == \Configuration::get('NING_CRON_TOKEN')) {
            if ($crondatas = $command->validateNgeniusCronSchedule()) {
                foreach ($crondatas as $crondata) {
                    $data = [
                        'id'          => $crondata['id'],
                        'executed_at' => date("Y-m-d h:i:s"),
                        'status'      => 'running',
                    ];
                    $command->updateNgeniusCronSchedule($data);
                    if ($this->cronTask()) {
                        $data = [
                            'id'          => $crondata['id'],
                            'finished_at' => date("Y-m-d h:i:s"),
                            'status'      => 'complete',
                        ];
                        $command->updateNgeniusCronSchedule($data);
                        $command->addNgeniusCronSchedule();
                        $cronLogger->addLog('Successfully run the cron job!.');
                        die;
                    }
                }
            } elseif (!$command->getNgeniusCronSchedule()) {
                $command->addNgeniusCronSchedule();
                $cronLogger->addLog('Successfully add the cron job!.');
                die;
            }
        } else {
            $cronLogger->addLog('Invalid Token!.');
            die;
        }
    }


    /**
     * Cron Task.
     *
     * @return bool|void
     * @throws Exception
     */
    public function cronTask()
    {
        $sql        = new DbQuery();
        $config     = new Config();
        $command    = new Command();
        $cronLogger = new CronLogger();

        $cronLogger->addLog('N-GENIUS: Cron started');

        $sql->select('*')
            ->from("ning_online_payment")
            ->where(
                'DATE_ADD(created_at, interval 60 MINUTE) < NOW()
                AND status ="' . pSQL($config->getOrderStatus() . '_PENDING') . '"
                AND (id_payment ="" OR id_payment ="null")'
            );
        $ngeniusOrders = \Db::getInstance()->executeS($sql);
        $counter       = 0;

        $cronLogger->addLog('N-GENIUS: Found ' . sizeof($ngeniusOrders) . ' unprocessed order(s)');

        foreach ($ngeniusOrders as $ngeniusOrder) {
            if ($counter >= 5) {
                $cronLogger->addLog("N-GENIUS: Breaking loop at 5 orders to avoid timeout");
                break;
            }

            $ngeniusOrder['status'] = 'NGENIUS_CRON';
            $command->updateNgeniusNetworkinternational($ngeniusOrder);

            try {
                $response = $command->getOrderStatusRequest($ngeniusOrder['reference']);
                $response = json_decode(json_encode($response), true);

                // Check if the response contains an error message and code
                if (isset($response['message']) && isset($response['code'])) {
                    if (isset($response['code']) == 404 || isset($response['errors'])) {
                        throw new Exception("Error " . $response['code'] . ": " . $response['message']);
                    }

                    if ($response && isset($response['_embedded']['payment']) && is_array(
                            $response['_embedded']['payment']
                        )) {
                        $apiProcessor = new ApiProcessor($response);

                        if ($this->processOrder($apiProcessor, $ngeniusOrder, true)) {
                            $cronLogger->addLog("N-GENIUS: State is " . $ngeniusOrder['state']);
                            $cronLogger->addLog(json_encode($this->getNgeniusOrder($ngeniusOrder['reference'])));
                        } else {
                            $cronLogger->addLog('N-GENIUS: Failed to process order');
                        }
                    } else {
                        $cronLogger->addLog("N-GENIUS: Payment result not found");
                    }
                }
            catch
                (Exception $exception) {
                    $cronLogger->addLog("N-GENIUS: Exception " . $exception->getMessage());
                }

            $counter++;
        }

        $cronLogger->addLog("N-GENIUS: Cron ended");

        return true;
    }
    }
