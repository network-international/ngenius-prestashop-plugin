<?php

/**
 * Hosted Session Ngenius Token Request
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
include_once _PS_MODULE_DIR_.'hsngenius/classes/Logger.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Command.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Config/Config.php';

class Tokenrequest
{
    /**
     * Builds access token request
     *
     * @return array|bool
     * @throws \Exception
     */
    public function getAccessToken()
    {
        $logger = new Logger();
        $log['path'] = __METHOD__;
        $result = array();
        $config = new Config();
        $tokenRequestURL = $config->getTokenRequestURL();
        $tokenHeaders = array("Authorization: Basic ".$config->getApiKey(), "Accept:application/vnd.ni-identity.v1+json", "Content-Type: application/vnd.ni-identity.v1+json");
        $post = '';
        $response = Tokenrequest::curl("POST", $tokenRequestURL, $tokenHeaders, $post);
        
        $log['token_response'] = $response;
        
        try {
            $result = json_decode($response);
            if (isset($result->access_token)) {
                $log['isvalid_access_token'] = true;
                return $result->access_token;
            } else {
                $log['isvalid_access_token'] = json_encode($response);
                return false;
            }
        } catch (\Exception $e) {
            $log['isvalid_access_token'] = $e->message();
            return false;
        } finally {
            $logger->addLog($log);
        }
    }
    /**
     * Gets curl.
     *
     * @param string $type
     * @param string $url
     * @param string $headers
     * @param string $post
     * @return array
     */
    public function curl($type, $url, $headers, $post)
    {
        $logger = new Logger();
        $log['path'] = __METHOD__;        
        $command = new Command();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        if ($type == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        $server_output = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($server_output, 0, strpos($server_output, "\r\n\r\n"));
        $body = substr($server_output, $header_size);
        
        curl_close($ch);

        $log['X-Correlation-Id'] = $command->getXCorilationId($header);
        $logger->addLog($log);

        return $body;
    }
}
