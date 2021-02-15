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


class HsngeniusPaymentModuleFrontController extends ModuleFrontController
{
      
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
    
        if (isset($_POST['sid'])) {
            $result = $this->placeOrder($_POST['sid'], $_POST['chSavedCard']);
            echo $result;
            exit;
        }
    }

    /**
     * Place order.
     *
     * @param string $session
     * @param string $chSavedCard
     * @return string
     */
    public function placeOrder($session, $chSavedCard)
    {
        $cart     = $this->context->cart;
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

        // order end
        $order = $this->getOrder();
        $this->buildNgeniusOrder($order, $chSavedCard);

        $paymentType = Configuration::get('PAYMENT_ACTION');
        switch ($paymentType) {
            case "authorize_capture": // sale
                if ($result = Command::order($order, $total, $session)) {
                    return $result;
                }
                break;
            case "authorize": // authorize
                if ($result = Command::authorize($order, $total, $session)) {
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
     * @return array
     */
    public function getOrder()
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
            'emailAddress' => $this->context->customer->email,
            'merchantOrderReference' => $this->module->currentOrder,
            'method' => null,
            'uri' => null
        ];
    }

    /**
     * Build ngenius order.
     *
     * @param array $order
     * @return bool
     */
    public function buildNgeniusOrder($order, $chSavedCard)
    {
        $command = new Command();
        $cart     = $this->context->cart;
        $data['status']     = Config::getInitialStatus();
        $data['id_order']   = $order['merchantOrderReference'];
        $data['id_cart']    = $cart->id;
        $data['amount']     = $order['amount']['value'];
        $data['currency']   = $order['amount']['currencyCode'];
        $data['action']     = Configuration::get('PAYMENT_ACTION') == 'authorize' ? 'AUTH' : 'SALE';
        $data['ch_saved_card'] = ($chSavedCard == 'true') ? 1 : 0;
        if ($command->placeNgeniusOrder($data)) {
            return true;
        }
        return false;
    }
}
