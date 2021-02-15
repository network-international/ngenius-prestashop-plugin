<?php

/**
 * Hosted Session Ngenius Config
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

class Config
{
    /**
     * Config tags
     */
    //https://api-gateway.sandbox.ngenius-payments.com/identity/auth/access-token
    const UAT_IDENTITY_URL      = "https://api-gateway.sandbox.ngenius-payments.com";
    //const UAT_IDENTITY_URL      = "https://identity-uat.ngenius-payments.com";
    //const LIVE_IDENTITY_URL     = "https://identity.ngenius-payments.com";
    const LIVE_IDENTITY_URL     = "https://api-gateway.ngenius-payments.com";
    const UAT_API_URL           = "https://api-gateway-uat.ngenius-payments.com";
    const LIVE_API_URL          = "https://api-gateway.ngenius-payments.com";
    const TOKEN_ENDPOINT        = "/identity/auth/access-token";
    //const TOKEN_ENDPOINT        = "/auth/realms/%s/protocol/openid-connect/token";
    const ORDER_ENDPOINT        = "/transactions/outlets/%s/payment/hosted-session/%s";
    const FETCH_ENDPOINT        = "/transactions/outlets/%s/orders/%s";
    const CAPTURE_ENDPOINT      = "/transactions/outlets/%s/orders/%s/payments/%s/captures";
    const VOID_AUTH_ENDPOINT    = "/transactions/outlets/%s/orders/%s/payments/%s/cancel";
    const REFUND_ENDPOINT       = "/transactions/outlets/%s/orders/%s/payments/%s/captures/%s/refund";
    const UAT_SDK_URL           = "https://paypage-uat.ngenius-payments.com/hosted-sessions/sdk.js";
    const LIVE_SDK_URL          = "https://paypage.ngenius-payments.com/hosted-sessions/sdk.js";
    const SAVED_CARD_ORDER_ENDPOINT  = "/transactions/outlets/%s/payment/saved-card";
       
    /**
     * Gets Display Name.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDisplayName($storeId = null)
    {
        return Configuration::get('DISPLAY_NAME', $storeId = null);
    }

    /**
     * Gets Direct Api Key.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiKey($storeId = null)
    {
        return Configuration::get('DIRECT_API_KEY', $storeId = null);
    }

    /**
     * Gets Hosted Sesssion Api Key.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getHostedSessionApiKey($storeId = null)
    {

        return Configuration::get('HOSTED_SESSION_API_KEY', $storeId = null);
    }

    /**
     * Gets Debug On.
     *
     * @param int|null $storeId
     * @return int
     */
    public function isDebugMode($storeId = null)
    {
        return (bool) Configuration::get('DEBUG', $storeId = null);
    }

    /**
     * Gets Outlet Reference ID.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getOutletReferenceId($storeId = null)
    {
        return Configuration::get('OUTLET_REFERENCE_ID', $storeId = null);
    }

    /**
     * Gets Initial Status.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getInitialStatus($storeId = null)
    {
        return Configuration::get('STATUS_OF_NEW_ORDER', $storeId = null);
    }

    /**
     * Gets value of configured environment.
     * Possible values: yes or no.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool) Configuration::get('ENABLED', $storeId = null);
    }

    /**
     * Retrieve apikey and outletReferenceId empty or not
     *
     * @return bool
     */
    public function isComplete($storeId = null)
    {
        if (!empty(Config::getApiKey($storeId)) && !empty(Config::getOutletReferenceId($storeId))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets Environment.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getEnvironment($storeId = null)
    {
        return Configuration::get('ENVIRONMENT', $storeId = null);
    }

    /**
     * Gets Tenant.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getTenant($storeId = null)
    {
        return Configuration::get('TENANT', $storeId = null);
    }

    /**
     * Gets Identity Url.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getIdentityUrl($storeId = null)
    {
        switch ($this->getEnvironment($storeId)) {
            case 'uat':
                $value = Config::UAT_IDENTITY_URL;
                break;
            case 'live':
                $value = Config::LIVE_IDENTITY_URL;
                break;
        }
        return $value;
    }

    /**
     * Gets Api Url.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiUrl($storeId = null)
    {
        switch ($this->getEnvironment($storeId)) {
            case 'uat':
                $value = Config::UAT_API_URL;
                break;
            case 'live':
                $value = Config::LIVE_API_URL;
                break;
        }
        return $value;
    }

    /**
     * Gets SDK Url.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSdkUrl($storeId = null)
    {
        switch ($this->getEnvironment($storeId)) {
            case 'uat':
                $value = Config::UAT_SDK_URL;
                break;
            case 'live':
                $value = Config::LIVE_SDK_URL;
                break;
        }
        return $value;
    }
    
    /**
     * Gets Tenant Name.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getTenantName($storeId = null)
    {
        $tenant = array('networkinternational' => array('uat' => 'ni', 'live' => 'networkinternational'));
        return $tenant[$this->getTenant($storeId)][$this->getEnvironment($storeId).''];
    }

    /**
     * Gets Token Request URL.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getTokenRequestURL($storeId = null)
    {
        $token_endpoint = Config::TOKEN_ENDPOINT;
        //$endpoint = sprintf($token_endpoint, $this->getTenantName($storeId));
        return $this->getIdentityUrl($storeId) . $token_endpoint;
    }

    /**
     * Gets Order Request URL.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getOrderRequestURL($session, $storeId = null)
    {
        $order_endpoint = Config::ORDER_ENDPOINT;
        $endpoint = sprintf($order_endpoint, $this->getOutletReferenceId($storeId), $session);
        return $this->getApiUrl($storeId) . $endpoint;
    }

    /**
     * Gets Fetch Request URL.
     *
     * @param int|null $storeId
     * @param string $orderRef
     * @return string
     */
    public function getFetchRequestURL($orderRef, $storeId = null)
    {
        $fetch_endpoint = Config::FETCH_ENDPOINT;
        $endpoint = sprintf($fetch_endpoint, $this->getOutletReferenceId($storeId), $orderRef);
        return $this->getApiUrl($storeId) . $endpoint;
    }

    /**
     * Gets Debug On.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDebugOn($storeId = null)
    {
        return (bool) $this->getValue(Config::DEBUG, $storeId);
    }

    /**
     * Gets Order Capture URL.
     *
     * @param int|null $storeId
     * @param string $orderRef
     * @param string $paymentRef
     * @return string
     */
    public function getOrderCaptureURL($orderRef, $paymentRef, $storeId = null)
    {
        $capture_endpoint = Config::CAPTURE_ENDPOINT;
        $endpoint = sprintf($capture_endpoint, $this->getOutletReferenceId($storeId), $orderRef, $paymentRef);
        return $this->getApiUrl($storeId) . $endpoint;
    }

    /**
     * Gets Order Void URL.
     *
     * @param int|null $storeId
     * @param string $orderRef
     * @param string $paymentRef
     * @return string
     */
    public function getOrderVoidURL($orderRef, $paymentRef, $storeId = null)
    {
        $void_endpoint = Config::VOID_AUTH_ENDPOINT;
        $endpoint = sprintf($void_endpoint, $this->getOutletReferenceId($storeId), $orderRef, $paymentRef);
        return $this->getApiUrl() . $endpoint;
    }

    /**
     * Gets Refund Void URL.
     *
     * @param int|null $storeId
     * @param string $orderRef
     * @param string $paymentRef
     * @param string $transactionId
     * @return string
     */
    public function getOrderRefundURL($orderRef, $paymentRef, $transactionId, $storeId = null)
    {
        $refund_endpoint = Config::REFUND_ENDPOINT;
        $endpoint = sprintf($refund_endpoint, $this->getOutletReferenceId($storeId), $orderRef, $paymentRef, $transactionId);
        return $this->getApiUrl() . $endpoint;
    }

    /**
     * Gets QUERY API TRIES.
     *
     * @param int|null $storeId
     * @return int
     */
    public function getQueryApiTries($storeId = null)
    {
        return (int) Configuration::get('QUERY_API_TRIES', $storeId = null);
    }

    /**
     * Gets saved card Order Request URL.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSavedCardOrderRequestURL($storeId = null)
    {

        $order_endpoint = Config::SAVED_CARD_ORDER_ENDPOINT;
        $endpoint = sprintf($order_endpoint, $this->getOutletReferenceId($storeId));
        return $this->getApiUrl($storeId) . $endpoint;
    }
}
