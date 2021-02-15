<?php

/**
 * Hosted Session Ngenius Abstract Transaction
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
include_once _PS_MODULE_DIR_.'hsngenius/classes/Logger.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Command.php';

abstract class AbstractTransaction
{

    /**
     * Places request to gateway. Returns result as ENV array.
     *
     * @param TransferInterface $transferObject
     * @return array|bool
     * @throws Exception
     */
    public function placeRequest(TransferFactory $transferObject)
    {
        $logger = new Logger();
        $log['path'] = __METHOD__;
        $command = new Command();
        $data = $this->preProcess($transferObject->getBody());
        $result = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $transferObject->getUri());
        curl_setopt($ch, CURLOPT_HTTPHEADER, $transferObject->getHeaders());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        if ($transferObject->getMethod() == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($transferObject->getMethod() == "PUT") {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
            
        $server_output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        $header = substr($server_output, 0, strpos($server_output, "\r\n\r\n"));
        $response = substr($server_output, $header_size);

        $log['X-Correlation-Id'] = $command->getXCorilationId($header);
        $log['response'] = $response;
        
        if ($httpcode > 399 && $httpcode < 500) {
            $log['HTTP_CODE'] = $httpcode;
            $logger->addLog($log);     
            die( '{ "error":"Something went wrong Please try again.<br><button onclick=\"location.reload()\" class=\"btn btn-primary\">Click here to continue</button>" }');
            exit; 
        }        

        curl_close($ch);
        $result = json_decode($response);
        try {
            if (isset($result)) {
                $log['isvalid_response'] = true;
                return $this->postProcess($response);
            } else {
                $log['isvalid_response'] = true;
                return false;
            }
        } catch (Exception $e) {
            $log['isvalid_response'] = $e->message();
            return false;
        } finally {
            $logger->addLog($log);
        }
    }
   
    /**
     * Processing of API request body
     *
     * @param array $data
     * @return string|array
     */
    abstract protected function preProcess(array $data);

    /**
     * Processing of API response
     *
     * @param array $response
     * @return array|bool
     */
    abstract protected function postProcess($response);
}
