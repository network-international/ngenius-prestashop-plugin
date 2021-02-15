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
include_once _PS_MODULE_DIR_.'hsngenius/classes/Request/TokenRequest.php';


class HsngeniusValidationModuleFrontController extends ModuleFrontController
{

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;

        $this->addJS('https://code.jquery.com/ui/1.12.1/jquery-ui.js');
        parent::setMedia();
        
        $command = new Command();
        $cart = $this->context->cart;
        $storeId = $cart->id_shop;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'hsngenius') {
                $authorized = true;
                break;
            }
        }
        
        if (!$authorized) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $this->context->smarty->assign([
            'params' => $_REQUEST,
        ]);
        
        // validate Customer
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // create order
        $cart     = $this->context->cart;
        $currency = $this->context->currency;
        $mailVars = array( );

        if (!isset($cart->id)) {
            //$this->errors[] = $this->l('Your Cart is empty!.');
            Tools::redirect('index.php?controller=order&step=1');
        }

        //-----------------------------
        $savedCards = [];
        if ($customerSavedCards = $command->getCustomerSavedCards($customer->id)) {
            $savedCards = $customerSavedCards;
             
        }
        //-----------------------------
        
        $config = new Config();
        $this->context->smarty->assign([
            'hostedSessionApiKey' => $config->getHostedSessionApiKey($storeId),
            'outletReferenceId' => $config->getOutletReferenceId($storeId),
            'psBaseUrl' => _PS_BASE_URL_.__PS_BASE_URI__,
            'orderTotal' => $currency->iso_code.number_format($cart->getOrderTotal(true, Cart::BOTH), 2),
            'paymentGateway' => $config->getDisplayName($storeId),
            'sdkUrl' => $config->getSdkUrl($storeId),
            'savedCards' => $savedCards,
        ]);
        
        $this->setTemplate('payment_return.tpl');
    }
}
