<?php

/**
 * Hosted Session Ngenius Validation Module Front Controller
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

include_once _PS_MODULE_DIR_.'hsngenius/classes/Command.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Logger.php';
include_once _PS_MODULE_DIR_.'hsngenius/classes/Config/Config.php';


class HsngeniusTokenizationModuleFrontController extends ModuleFrontController
{
      
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        if (isset($_POST['cvv']) && isset($_POST['savedCardId'])) {
            $cvv = $_POST['cvv'];
            $savedCardId = $_POST['savedCardId'];
            if (strlen($cvv) == 3 || strlen($cvv) == 4) {
                $result = $this->placeOrder($cvv, $savedCardId);
                echo $result;
                exit;
            }
        }
    }

    /**
     * Place order.
     *
     * @param string $cvv
     * @param int $savedCardId
     * @return string
     */
    public function placeOrder($cvv, $savedCardId)
    {
        $cart = $this->context->cart;
        $command = new Command();
        $config = new Config();
        $customer = new Customer($cart->id_customer);
        // create order
        $currency = $this->context->currency;
        $total    = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $mailVars = array( );
        
        $this->module->validateOrder(
            $cart->id,
            Configuration::get('NGENIUS_PENDING'),
            $total,
            $this->module->displayName,
            null,
            $mailVars,
            (int)$currency->id,
            false,
            $customer->secure_key
        );

        if ($data = $command->getCustomerSavedCard($customer->id, $savedCardId)) {
            $savedCard = json_decode($data['saved_card'], true);
            $order = $this->getOrder($savedCard, $cvv);
            $this->buildNgeniusOrder($order);
        }
        
        $paymentType = Configuration::get('PAYMENT_ACTION');

        switch ($paymentType) {
            case "authorize_capture": // sale
                if ($result = $command->savedCardSale($order, $total, $session = null)) {
                    return $result;
                }
                break;
            case "authorize": // authorize
                if ($result = $command->savedCardAuthorize($order, $total, $session = null)) {
                    return $result;
                }
                break;
            default:
                return '{ "error":"Oops something went wrong!." }';
        }
    }

    /**
     * Gets order.
     *
     * @param array $savedCard
     * @param string $cvv
     * @return array
     */
    public function getOrder($savedCard, $cvv)
    {
        $cart     = $this->context->cart;
        $address = new Address($cart->id_address_delivery);
        return $order = [
            'action' => null,
            'amount' => [
                'currencyCode' => $this->context->currency->iso_code,
                'value' => (float)$cart->getOrderTotal(true, Cart::BOTH) * 100,
            ],
            'billingAddress'    => [
                'firstName'     => $address->firstname,
                'lastName'      => $address->lastname,
                'address1'      => $address->address1,
                'city'          => $address->city,
                'countryCode'   => $this->context->country->iso_code,
            ],
            'payment'    => [
                'maskedPan'         => $savedCard['maskedPan'],
                'expiry'            => $savedCard['expiry'],
                'cardholderName'    => $savedCard['cardholderName'],
                'scheme'            => $savedCard['scheme'],
                'cardToken'         => $savedCard['cardToken'],
                'recaptureCsc'      => $savedCard['recaptureCsc'],
                'cvv'               => $cvv,
            ],
            'emailAddress' => $this->context->customer->email,
            'merchantOrderReference' =>  $this->module->currentOrder,
            'method' => null,
            'uri' => null,
        ];
    }

    /**
     * Build ngenius order.
     *
     * @param array $order
     * @return bool
     */
    public function buildNgeniusOrder($order)
    {
        $command = new Command();
        $cart     = $this->context->cart;
        $data['status']     = Config::getInitialStatus();
        $data['id_order']   = $order['merchantOrderReference'];
        $data['id_cart']    = $cart->id;
        $data['amount']     = $order['amount']['value'];
        $data['currency']   = $order['amount']['currencyCode'];
        $data['action']     = Configuration::get('PAYMENT_ACTION') == 'authorize' ? 'AUTH' : 'SALE';
        if ($command->placeNgeniusOrder($data)) {
            return true;
        }
        return false;
    }
}
