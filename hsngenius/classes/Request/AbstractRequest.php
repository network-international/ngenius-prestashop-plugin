<?php

/**
 * Hosted Session Ngenius Abstractrequest Model
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
include_once _PS_MODULE_DIR_.'hsngenius/classes/Logger.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Config/Config.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Request/TokenRequest.php';

abstract class AbstractRequest
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * Builds ENV request
     *
     * @param array $order
     * @param float $amount
     * @param string $session
     * @return array|bool
     */
    public function build($order, $amount, $session)
    {
        $logger = new Logger();
        $log['path'] = __METHOD__;
        $config = new Config();
        $tokenRequest = new TokenRequest();
        $token = $tokenRequest->getAccessToken();

        if ($config->isComplete() && $token) {
            $log['is_valid_token'] = 'yes';
            $logger->addLog($log);
            return[
                'token' => $token,
                'request' => $this->getBuildArray($order, $amount, $session)
            ];
            
        } else {
            $log['is_valid_token'] = 'Invalid Token.';
            $logger->addLog($log);
            die( '{ "error":"Something went wrong Please try again.<br><button onclick=\"location.reload()\" class=\"btn btn-primary\">Click here to continue</button>" }');
            exit;  
            return false;
        }
    }

    /**
     * Builds abstract ENV request array
     *
     * @param array $order
     * @param float $amount
     * @param string $session
     * @return array
     */
    abstract public function getBuildArray($order, $amount, $session);
}
