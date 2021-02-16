<?php
/**
 * Hosted Session Ngenius module for PrestaShop
 *
 * @author     Abzer <info@abzer.com>
 * @package    NetworkInternational_Hsngenius
 * @version   1.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'hsngenius' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Command.php');
include_once _PS_MODULE_DIR_.'hsngenius/classes/Config/Config.php';

class Hsngenius extends PaymentModule
{
    // constant
    public $extra_mail_vars;

    /**
     * Module __construct
     * @return void
     */
    public function __construct()
    {
        $this->name = 'hsngenius';
        $this->tab = 'payments_gateways';
        $this->version = '1.2.1';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6');
        $this->author = 'Abzer';
        $this->controllers = array('validation','');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('N-Genius Online Payment Gateway: Hosted Session');
        $this->description = $this->l('The payment gateway from Network International');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this payment gateway? Please take a backup');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
    }

    /**
     * Module install
     *
     * @return bool
     */
    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('payment')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('displayAdminOrder')
            || !$this->registerHook('actionOrderStatusUpdate')
            || !$this->registerHook('displayBackOfficeHeader')
            || !$this->registerHook('displayAdminOrderContentOrder')
            || !$this->addTab()
            || !$this->createOrderState()
            || !$this->installSchemaNgenius()
             
        ) {
            return false;
        }
       
        return true;
    }

    /**
     * Module uninstall
     *
     * @return bool
     */
    public function uninstall()
    {
        $this->deleteTab();
        return parent::uninstall();
    }
   
    /**
     * Payment Options Hook.
     *
     * @param array $params
     * @return array
     */
    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }
        $config = new Config();
        $this->context->smarty->assign([
            'contoller_link' => Context::getContext()->link->getModuleLink('hsngenius', 'validation', $params = []),
            'displayName' => $config->getDisplayName(),
            'imagePath'  => $this->_path]);
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * Display Back Office display Admin Order Content Order Hook.
     *
     * @param array $params
     * @return string|void;
     */
    public function hookDisplayAdminOrderContentOrder($params)
    {
        if (!$this->active) {
            return;
        }

        $message = '';
        if (isset($this->context->cookie->hsngenius_flashes)) {
            $message = $this->adminDisplayWarning($this->l($this->context->cookie->hsngenius_flashes));
        }

        if (isset($this->context->cookie->hsngenius_errors)) {
            $message = $this->context->controller->errors[] = $this->l($this->context->cookie->hsngenius_errors);
        }

        $this->context->cookie->__unset('hsngenius_flashes');
        $this->context->cookie->__unset('hsngenius_errors');
        return $message;
    }

    /**
     * Payment Return Hook.
     *
     * @param array $params
     * @return string|void;
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }
        
        $this->context->smarty->assign([
                'module' => Configuration::get('DISPLAY_NAME'),
            ]);
        
        return $this->display(__FILE__, 'payment_success.tpl');
    }

    /**
     * Display BackOffice Header Hook.
     *
     * @param array $params
     * @return void;
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS(($this->_path) . 'views/css/hsngeniustab.css');
    }

    /**
     * Order Status Update Hook.
     *
     * @param array $params
     * @return bool|void;
     */
    public function hookActionOrderStatusUpdate($params)
    {
        
        if (!$this->active) {
            return;
        }
        
        $current_context = Context::getContext();
        if ($current_context->controller->controller_type != 'admin') {
            return true;
        }
        if ($this->context->cookie->queryApi) {
            $this->context->cookie->__unset('queryApi');
            return true;
        }
        
        $order = new Order((int)$params['id_order']);
        $command = new Command();
        if ($this->validateNgeniusOrderSatus($params) == false) {
            $this->addNgeniusFlashMessage('Error!. Invalid Order Status.');
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
            return;
        } else {
            if (!empty($params['id_order']) &&  !empty($params['newOrderStatus']) && Validate::isLoadedObject($params['newOrderStatus'])) {
                $ngenusOrder = $command->getNgeniusOrder($order->id);
                if ($params['newOrderStatus']->id == Configuration::get('NGENIUS_FULLY_CAPTURED') && Validate::isLoadedObject($order)) {
                    if ($this->context->cookie->hsngenius_fully_captured) {
                        $this->context->cookie->__unset('hsngenius_fully_captured');
                        return true;
                    }
                    $this->addNgeniusFlashMessage('Oops!. Invalid Order Status.');
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                } elseif ($params['newOrderStatus']->id == Configuration::get('NGENIUS_AUTH_REVERSED') && Validate::isLoadedObject($order)) {
                    if ($this->context->cookie->hsngenius_auth_reversed) {
                        $this->context->cookie->__unset('hsngenius_auth_reversed');
                        return true;
                    }
                    $this->addNgeniusFlashMessage('Oops!. Invalid Order Status.');
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                } elseif ($params['newOrderStatus']->id == Configuration::get('NGENIUS_FULLY_REFUNDED') && Validate::isLoadedObject($order)) {
                    if ($this->context->cookie->hsngenius_fully_refunded) {
                        $this->context->cookie->__unset('hsngenius_fully_refunded');
                        //$command->reinjectQuantity($params['id_order']);
                        $this->addNgeniusFlashMessage('You have successfully refund the transaction!');
                        return true;
                    }
                    $this->addNgeniusFlashMessage('Oops!. Invalid Order Status.');
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                } elseif ($params['newOrderStatus']->id == Configuration::get('NGENIUS_PARTIALLY_REFUNDED') && Validate::isLoadedObject($order)) {
                    if ($this->context->cookie->hsngenius_partially_refunded) {
                        $this->context->cookie->__unset('hsngenius_partially_refunded');
                        $this->addNgeniusFlashMessage('You have partially refund the transaction!');
                        return true;
                    }
                    $this->addNgeniusFlashMessage('Oops!. Invalid Order Status.');
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                } else {
                    return true;
                }
            }
        }
    }

    
    /**
     * Display Admin Order Hook.
     *
     * @param array $params
     * @return string|void;
     */
    public function hookDisplayAdminOrder($params)
    {
        
        if (! $this->active) {
            return;
        }

        if (isset($params['id_order'])) {
            $order = new Order((int)$params['id_order']);
            if ($order->module == 'hsngenius') {
                echo '<script> $(document).ready(function(){ $("#desc-order-partial_refund").hide();}) </script>';
            }
        }

        $id_order = (int)$params['id_order'];
        $order = new Order($id_order);
        if ($order->module == 'hsngenius') {
            $command = new Command();
            $ngeniusOrder = $command->getNgeniusOrder($id_order);
            $formAction = $this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $params['id_order'] . '&vieworder';

            // void / capture
            $authorizedOrder = $command->getAuthorizationTransaction($ngeniusOrder);
            if ($authorizedOrder) {
                if (Tools::isSubmit('fullyCaptureNgenius')) {
                    // fully capture
                    if ($command->capture($order, $authorizedOrder)) {
                        $this->addNgeniusFlashMessage('Successfully Captured!');
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                    } else {
                        $this->addNgeniusFlashMessage('Oops something went wrong!.');
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                    }
                } elseif (Tools::isSubmit('voidNgenius')) {
                    // void / auth reverse
                    if ($command->void($order, $authorizedOrder)) {
                        $this->addNgeniusFlashMessage('You have successfully reversed the authorization!');
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                    } else {
                        $this->addNgeniusFlashMessage('Oops something went wrong!.');
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                    }
                }
            }

            // refund
            $refundedOrder = $command->getRefundedTransaction($ngeniusOrder);
            $totalRefunded = '';
            if ($refundedOrder) {
                if (Tools::isSubmit('partialRefundNgenius')) {
                    if (Tools::getValue('refundAmount') != '' ||  Tools::getValue('refundAmount') != null) {
                        $refundedOrder['amount'] = (float)Tools::getValue('refundAmount');
                        $result = $command->refund($order, $refundedOrder);
                        if ($result != false) {
                            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                        } else {
                            $this->addNgeniusFlashMessage('error in proceed with your refund.!.');
                            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                        }
                    }
                } else {
                    $totalRefunded = $ngeniusOrder['amount'] - $ngeniusOrder['capture_amt'];
                }
            }

            if (Tools::isSubmit('queryApi')) {
                $ngeniusOrder = $command->getNgeniusOrder($order->id);
                if (isset($ngeniusOrder['reference'])) {
                    if ($command->orderItemQueryApi($ngeniusOrder)) {
                        $ngeniusOrder = $command->getNgeniusOrder($order->id);
                        $status = 'Successfully updated!.... Current status : '.$ngeniusOrder['status'];
                        $this->addNgeniusFlashMessage($status);
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                    } else {
                        $status = 'Nothing to update!.... Current status : '.$ngeniusOrder['status'];
                        $this->addNgeniusFlashMessage($status);
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                    }
                } else {
                    $this->addNgeniusFlashMessage('Invalid Ngenius Order Reference!.');
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders') . '&id_order=' . $order->id . '&vieworder');
                }
            }

            $this->context->smarty->assign([
                'ngeniusOrder'      => $ngeniusOrder,
                'authorizedOrder'   => $authorizedOrder,
                'refundedOrder'     => $refundedOrder,
                'formAction'        => $formAction,
                'totalRefunded'     => $totalRefunded,
            ]);
            return $this->display(__FILE__, 'views/templates/admin/payment.tpl');
        }
    }

    /**
     * Add new back office tab.
     *
     * @return bool;
     */
    public function addTab()
    {
        if (!Tab::getIdFromClassName('AdminNgeniusonline')) {
            $tab = new Tab();
            $langs = Language::getLanguages(false);
            foreach ($langs as $l) {
                $tab->name[$l['id_lang']] = $this->l('N-Genius Online');
            }
            $tab->class_name = 'AdminNgeniusonline';
            $tab->id_parent = (int) Tab::getIdFromClassName('ShopParameters');
            $tab->module = 'hsngenius';
            $tab->add();
        }
        if (!Tab::getIdFromClassName('AdminNgeniusReports')) {
            $tab = new Tab();
            $langs = Language::getLanguages(false);
            foreach ($langs as $l) {
                $tab->name[$l['id_lang']] = $this->l('Report');
            }
            $tab->class_name = 'AdminNgeniusReports';
            $tab->id_parent = Tab::getIdFromClassName('AdminNgeniusonline');
            $tab->module = 'hsngenius';
            $tab->add();
        }
        if (!Tab::getIdFromClassName('AdminNgeniusconfiglink')) {
            $tab = new Tab();
            $langs = Language::getLanguages(false);
            foreach ($langs as $l) {
                $tab->name[$l['id_lang']] = $this->l('Settings');
            }
            $tab->class_name = 'AdminNgeniusconfiglink';
            $tab->id_parent = Tab::getIdFromClassName('AdminNgeniusonline');
            $tab->module = 'hsngenius';
            $tab->add();
        }
        if (!Tab::getIdFromClassName('AdminNgeniuscronlog')) {
            $tab = new Tab();
            $langs = Language::getLanguages(false);
            foreach ($langs as $l) {
                $tab->name[$l['id_lang']] = $this->l('Cron Logs');
            }
            $tab->class_name = 'AdminNgeniuscronlog';
            $tab->id_parent = Tab::getIdFromClassName('AdminNgeniusonline');
            $tab->module = 'hsngenius';
            $tab->add();
        }
        return true;
    }

    /**
     * Delete back office tab.
     *
     * @return bool;
     */
    public function deleteTab()
    {
        if ($idTab = Tab::getIdFromClassName('AdminNgeniusonline')) {
            if ($idTab != 0) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }
        if ($idTab = Tab::getIdFromClassName('AdminNgeniusReports')) {
            if ($idTab != 0) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }
        if ($idTab = Tab::getIdFromClassName('AdminNgeniusconfiglink')) {
            if ($idTab != 0) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }
        if ($idTab = Tab::getIdFromClassName('AdminNgeniuscronlog')) {
            if ($idTab != 0) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }
        return true;
    }

    /**
     * Validate Ngenius Order Satus.
     *
     * @param array $params
     * @return bool;
     */
    public function validateNgeniusOrderSatus($params)
    {
        $order = new Order((int)$params['id_order']);
        if (!empty($order->module) && $order->module == 'hsngenius' && !empty($params['newOrderStatus']) &&  Validate::isLoadedObject($params['newOrderStatus'])) {
            if ($params['newOrderStatus']->id == Configuration::get('NGENIUS_PENDING')
                || $params['newOrderStatus']->id == Configuration::get('NGENIUS_AWAIT_3DS')
                || $params['newOrderStatus']->id == Configuration::get('NGENIUS_PROCESSING')
                || $params['newOrderStatus']->id == Configuration::get('NGENIUS_FAILED')
                || $params['newOrderStatus']->id == Configuration::get('NGENIUS_COMPLETE')
                || $params['newOrderStatus']->id == Configuration::get('NGENIUS_AUTHORISED')
                ) {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * Check Currency.
     *
     * @param object $cart
     * @return bool;
     */
    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Load the configuration form
     *
     * @return string|void
     */
    public function getContent()
    {
        $output = null;
        if (Tools::isSubmit('submit' . $this->name)) {
            $DISPLAY_NAME = strval(Tools::getValue('DISPLAY_NAME'));
            $ENVIRONMENT = strval(Tools::getValue('ENVIRONMENT'));
            $TENANT = strval(Tools::getValue('TENANT'));
            $PAYMENT_ACTION = strval(Tools::getValue('PAYMENT_ACTION'));
            $STATUS_OF_NEW_ORDER = strval(Tools::getValue('STATUS_OF_NEW_ORDER'));
            $OUTLET_REFERENCE_ID = strval(Tools::getValue('OUTLET_REFERENCE_ID'));
            $HOSTED_SESSION_API_KEY = strval(Tools::getValue('HOSTED_SESSION_API_KEY'));
            $DIRECT_API_KEY = strval(Tools::getValue('DIRECT_API_KEY'));
            $DEBUG = strval(Tools::getValue('DEBUG'));
            $QUERY_API_TRIES = strval(Tools::getValue('QUERY_API_TRIES'));
            

            if (!$DISPLAY_NAME || empty($DISPLAY_NAME) || !Validate::isGenericName($DISPLAY_NAME)) {
                $output .= $this->displayError($this->l('Invalid name for payment gateway'));
            } elseif (!$OUTLET_REFERENCE_ID || empty($OUTLET_REFERENCE_ID)) {
                $output .= $this->displayError($this->l('Invalid outlet reference id'));
            } elseif (!$HOSTED_SESSION_API_KEY || empty($HOSTED_SESSION_API_KEY)) {
                $output .= $this->displayError($this->l('Invalid Hosted Session API key'));
            } elseif (!$DIRECT_API_KEY || empty($DIRECT_API_KEY)) {
                $output .= $this->displayError($this->l('Invalid Direct API key'));
            } else {
                //Configuration::updateValue('ENABLED', $ENABLED);
                Configuration::updateValue('DISPLAY_NAME', $DISPLAY_NAME);
                Configuration::updateValue('ENVIRONMENT', $ENVIRONMENT);
                Configuration::updateValue('TENANT', $TENANT);
                Configuration::updateValue('PAYMENT_ACTION', $PAYMENT_ACTION);
                Configuration::updateValue('STATUS_OF_NEW_ORDER', $STATUS_OF_NEW_ORDER);
                Configuration::updateValue('OUTLET_REFERENCE_ID', $OUTLET_REFERENCE_ID);
                Configuration::updateValue('DIRECT_API_KEY', $DIRECT_API_KEY);
                Configuration::updateValue('HOSTED_SESSION_API_KEY', $HOSTED_SESSION_API_KEY);
                Configuration::updateValue('DEBUG', $DEBUG);
                Configuration::updateValue('QUERY_API_TRIES', $QUERY_API_TRIES);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output . $this->displayForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     *
     * @return string
     */
    public function displayForm()
    {
        // Get default language
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fieldsForm[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings | V '.$this->version),
            ),
            'input' => array(
                
                // Title
                array(
                    'type' => 'text',
                    'label' => $this->l('Display Name'),
                    'name' => 'DISPLAY_NAME',
                    'col' => '6',
                    'value' => 'N-Genius Online Payment Gateway: Hosted Session',
                    //'hint' => 'Name to display on fontend',
                    'required' => true
                ),
                // Environment
                array(
                    'type'     => 'select',
                    'label'    => $this->l('Environment'),
                    'name'     => 'ENVIRONMENT',
                    'required' => true,
                    'class'    => 't',
                    'col'      => '6',
                    'options'  => array(
                        'query' => $options = array(
                            array('id_option' => 'uat', 'name' => 'UAT',),
                            array( 'id_option' => 'live','name' => 'Live',),
                        ),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                ),
                // Tenant Name
                array(
                    'type'     => 'select',
                    'label'    => $this->l('Tenant'),
                    'name'     => 'TENANT',
                    'required' => true,
                    'class'    => 't',
                    'col'      => '6',
                    'options'  => array(
                        'query' => $options = array(
                            array('id_option' => 'networkinternational', 'name' => 'Network International',),
                            //array( 'id_option' => 'adcb','name' => 'ADCB',),
                        ),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                ),
                // Payment Action
                array(
                    'type'     => 'select',
                    'label'    => $this->l('Payment Action'),
                    'name'     => 'PAYMENT_ACTION',
                    'required' => true,
                    'class'    => 't',
                    'col' => '6',
                    'options' => array(
                        'query' => $options = array(
                            array('id_option' => 'authorize',  'name' => 'Authorize',  ),
                            array('id_option' => 'authorize_capture', 'name' => 'Sale', ),
                        ),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                ),
                // Status of new order
                array(
                    'type'     => 'select',
                    'label'    => $this->l('Status of new order'),
                    'name'     => 'STATUS_OF_NEW_ORDER',
                    'required' => true,
                    'class'    => 't',
                    'col' => '6',
                    'options' => array(
                        'query' => $options = array(
                            array( 'id_option' => 'NGENIUS_PENDING',   'name' => 'N-Genius Online Pending',),
                        ),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                ),
                // Outlet Reference ID
                array(
                    'type' => 'text',
                    'label' => $this->l('Outlet Reference ID'),
                    'name' => 'OUTLET_REFERENCE_ID',
                    'required' => true,
                    'col' => '6',
                    //'hint' => 'Name to display on fontend',
                ),
                // API Key
                array(
                    'type' => 'text',
                    'label' => $this->l('Direct API Key'),
                    'name' => 'DIRECT_API_KEY',
                    'required' => true,
                    'col' => '6',
                    //'hint' => 'Name to display on fontend',
                ),
                // Hosted Session API Key
                array(
                    'type' => 'text',
                    'label' => $this->l('Hosted Session API Key'),
                    'name' => 'HOSTED_SESSION_API_KEY',
                    'required' => true,
                    'col' => '6',
                    //'hint' => 'Name to display on fontend',
                ),
                // Debug
                array(
                    'type'     => 'select',
                    'label'    => $this->l('Debug'),
                    'name'     => 'DEBUG',
                    'col' => '6',
                    'required' => true,
                    'class'    => '',
                    'options' => array(
                        'query' => $options = array(
                            array('id_option' => 1, 'name' => 'Yes',),
                            array('id_option' => 0, 'name' => 'No',),
                        ),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                ),
                // Query api number Of tries
                array(
                    'type'     => 'select',
                    'label'    => $this->l('Query API Tries'),
                    'name'     => 'QUERY_API_TRIES',
                    'col' => '6',
                    'required' => true,
                    'class'    => '',
                    'options' => array(
                        'query' => $options = array(
                            array('id_option' => 1, 'name' => '1'),
                            array('id_option' => 2, 'name' => '2'),
                            array('id_option' => 3, 'name' => '3'),
                            array('id_option' => 4, 'name' => '4'),
                            array('id_option' => 5, 'name' => '5'),
                        ),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-left'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        // Load current value
        $helper->fields_value['DISPLAY_NAME'] = (Configuration::get('DISPLAY_NAME') == false) ? $this->displayName :Configuration::get('DISPLAY_NAME');
        $helper->fields_value['ENVIRONMENT'] = Configuration::get('ENVIRONMENT');
        $helper->fields_value['TENANT'] = Configuration::get('TENANT');
        $helper->fields_value['PAYMENT_ACTION'] = Configuration::get('PAYMENT_ACTION');
        $helper->fields_value['STATUS_OF_NEW_ORDER'] = Configuration::get('STATUS_OF_NEW_ORDER');
        $helper->fields_value['OUTLET_REFERENCE_ID'] = Configuration::get('OUTLET_REFERENCE_ID');
        $helper->fields_value['HOSTED_SESSION_API_KEY'] = Configuration::get('HOSTED_SESSION_API_KEY');
        $helper->fields_value['DIRECT_API_KEY'] = Configuration::get('DIRECT_API_KEY');
        $helper->fields_value['DEBUG'] = Configuration::get('DEBUG');
        $helper->fields_value['QUERY_API_TRIES'] = Configuration::get('QUERY_API_TRIES');

        return $helper->generateForm($fieldsForm).$this->cronDiv();
    }

    /**
     * show the conent will be displayed in the configuration of your module for ngenius cron.
     *
     * @return string
     */
    public function cronDiv()
    {
        $url = _PS_BASE_URL_.__PS_BASE_URI__.'module/hsngenius/cron';
        return  ' <div class="panel clearfix">
            <h3>'.$this->l('N-Genius Online cron task').'</h3><b>
          '.$this->l('Please add the below cron job in your cron module or server.').'</br>'.
          $this->l('This cron will run the Query API to retrieve the status of incomplete requests from N-Genius Online and update the order status in Prestashop.').'</br>'.
          $this->l('It is recommended to run this cron every 60 minutes.').'</b> 
          <p>  <br/><b><a>'.$this->l('*/60 * * * * curl "'.$url.'"').'</a> </b></p>       
        </div> ';
    }

    /**
     * create order state.
     *
     * @return bool
     */
    public function createOrderState()
    {
        foreach ($this->getNgeniusOrderStatus() as $state) {
            $orderStateExist = false;
            $status_name = $state['status']; //'PS_OS_NGENIUS';
            $orderStateId = Configuration::get($status_name);
            $description = $state['label'];
            // save data to sorder_state_lang table
            if ($orderStateId) {
                $orderState = new OrderState($orderStateId);
                if ($orderState->id && !$orderState->deleted) {
                    $orderStateExist = true;
                }
            } else {
                $query = 'SELECT os.`id_order_state` '.
                'FROM `%1$sorder_state_lang` osl '.
                'LEFT JOIN `%1$sorder_state` os '.
                'ON osl.`id_order_state`=os.`id_order_state` '.
                'WHERE osl.`name`="%2$s" AND os.`deleted`=0';
                $orderStateId =  Db::getInstance()->getValue(sprintf($query, _DB_PREFIX_, $description));
                if ($orderStateId) {
                    Configuration::updateValue($status_name, $orderStateId);
                    $orderStateExist = true;
                }
            }

            if (!$orderStateExist) {
                $languages = Language::getLanguages(false);
                $orderState = new OrderState();
                foreach ($languages as $lang) {
                    $orderState->name[$lang['id_lang']] = $description;
                }

                $orderState->send_email = $state['send_email'];
                $orderState->template = $state['template'];
                $orderState->invoice = $state['invoice'];
                $orderState->color = $state['color'];
                $orderState->unremovable = 1;
                $orderState->logable = 0;
                $orderState->delivery = $state['delivery'];
                $orderState->hidden = 0;
                $orderState->module_name = $this->name;
                $orderState->shipped = $state['shipped'];
                $orderState->paid = 0;
                $orderState->pdf_invoice = $state['pdf_invoice'];
                $orderState->pdf_delivery = $state['pdf_delivery'];
                $orderState->deleted = 0;

                if ($orderState->add()) {
                    Configuration::updateValue($status_name, $orderState->id);
                    $orderStateExist = true;
                }
            }
            $file = $this->getLocalPath().'views/images/order_state.gif';
            $newfile = _PS_IMG_DIR_.'os/' . $orderState->id . '.gif';
            copy($file, $newfile);
        }
        return true;
    }

    /**
     * Hosted Session Ngenius Networkinternational order table.
     *
     * @return bool
     */
    public function installSchemaNgenius()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."ngenius_networkinternational`( 
            `nid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'n-genius Id',
            `id_cart` int(10) unsigned NOT NULL COMMENT 'Cart Id',
            `id_order` varchar(55) NOT NULL COMMENT 'Order Id',
            `amount` decimal(12,4) unsigned NOT NULL COMMENT 'Amount',
            `currency` varchar(3) NOT NULL COMMENT 'Currency',
            `reference` varchar(50) NOT NULL COMMENT 'Reference',
            `action` varchar(20) NOT NULL COMMENT 'Action',
            `status` varchar(50) NOT NULL COMMENT 'Status',
            `state` varchar(50) NOT NULL COMMENT 'State',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created On',
            `id_payment` varchar(50) NOT NULL COMMENT 'Transaction ID',
            `capture_amt` decimal(12,4) unsigned NOT NULL COMMENT 'Capture Amount',
            `id_capture` varchar(50) NOT NULL COMMENT 'Capture ID',
            `card_details` text COMMENT 'Card Details',
            `authorization_code` varchar(50) DEFAULT NULL COMMENT 'Authorization Code',
            `result_code` varchar(50) DEFAULT NULL COMMENT 'Result Code',
            `result_message` text COMMENT 'Result Message',
            `query_api_tries` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Query API Tries',
            `api_type` enum('direct','queryapi','cron') DEFAULT NULL COMMENT 'API Type',
            `ch_saved_card` tinyint(1) DEFAULT NULL COMMENT 'Checkbox Saved Card',
            PRIMARY KEY (`nid`),
            UNIQUE KEY `NETWORK_ONLINE_CART_ID_ORDER_ID` (`id_cart`,`id_order`),
            UNIQUE KEY `ngenius_index_referance` (`reference`)
        )ENGINE=`"._MYSQL_ENGINE_."` CHARSET=utf8";

        Db::getInstance()->Execute($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."customer_savedcard`( 
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_customer` int(11) NOT NULL,
            `saved_card` text NOT NULL,
            `date_add` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        )ENGINE=`"._MYSQL_ENGINE_."` CHARSET=utf8";
        Db::getInstance()->Execute($sql);
        
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."ngenius_cron_log`( 
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_cart` int(10) unsigned NOT NULL COMMENT 'Cart Id',
            `id_order` varchar(55) NOT NULL COMMENT 'Order Id',
            `reference` text NOT NULL COMMENT 'Reference',
            `try_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Cron Tries',
            `from_state` varchar(50) NOT NULL COMMENT 'From State',
            `response` text NOT NULL COMMENT 'Response',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `status` varchar(50) NOT NULL COMMENT 'Status',
            PRIMARY KEY (`id`)
        )ENGINE=`"._MYSQL_ENGINE_."` CHARSET=utf8";
        Db::getInstance()->Execute($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."ngenius_order_email_content`( 
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_order` int(11) NOT NULL,
            `data` text NOT NULL,
            `email_send` int(11) DEFAULT NULL,
            `sent_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=`"._MYSQL_ENGINE_."` CHARSET=utf8";
        Db::getInstance()->Execute($sql);

        return true;
    }

    /**
     * Order Configuration URL redirect
     *
     * @return string
     */
    public function getOrderConfUrl($order)
    {
        return $this->context->link->getPageLink(
            'order-confirmation',
            true,
            $order->id_lang,
            array(
                'id_cart' => $order->id_cart,
                'id_module' => $this->id,
                'id_order' => $order->id,
                'key' => $order->secure_key
            )
        );
    }

    /**
     * Hosted Session Ngenius Order Status.
     *
     * @return array
     */
    public function getNgeniusOrderStatus()
    {
        return [
            [
                'status' => 'NGENIUS_PENDING',
                'label' => 'N-Genius Online Pending',
                'invoice' => 0,
                'send_email' => 0,
                'template' => '',
                'delivery' => 0,
                'shipped' => 0,
                'color' => '#4169E1',
                'pdf_invoice' => 0,
                'pdf_delivery' => 0,
            ],
            [
                'status' => 'NGENIUS_AWAIT_3DS',
                'label' => 'N-Genius Online Await 3ds',
                'invoice' => 0,
                'send_email' => 0,
                'template' => '',
                'delivery' => 0,
                'shipped' => 0,
                'color' => '#4169E1',
                'pdf_invoice' => 0,
                'pdf_delivery' => 0,
            ],
            [
                'status' => 'NGENIUS_PROCESSING',
                'label' => 'N-Genius Online Processing',
                'invoice' => 0,
                'send_email' => 0,
                'template' => '',
                'delivery' => 0,
                'shipped' => 0,
                'color' => '#32CD32',
                'pdf_invoice' => 0,
                'pdf_delivery' => 0,
            ],
            [
                'status' => 'NGENIUS_FAILED',
                'label' => 'N-Genius Online Failed',
                'invoice' => 0,
                'send_email' => 1,
                'template' => 'payment_error',
                'delivery' => 0,
                'shipped' => 0,
                'color' => '#8f0621',
                'pdf_invoice' => 0,
                'pdf_delivery' => 0,
            ],
            [
                'status' => 'NGENIUS_COMPLETE',
                'label' => 'N-Genius Online Complete',
                'invoice' => 1,
                'send_email' => 1,
                'template' => 'payment',
                'delivery' => 0,
                'shipped' => 0,
                'color' => '#108510',
                'pdf_invoice' => 1,
                'pdf_delivery' => 0,
            ],
            [
                'status' => 'NGENIUS_AUTHORISED',
                'label' => 'N-Genius Online Authorised',
                'invoice' => 0,
                'send_email' => 0,
                'template' => '',
                'delivery' => 0,
                'shipped' => 0,
                'color' => '#FF8C00',
                'pdf_invoice' => 0,
                'pdf_delivery' => 0,
            ],
            [
                'status' => 'NGENIUS_FULLY_CAPTURED',
                'label' => 'N-Genius Online Fully Captured',
                'invoice' => 1,
                'send_email' => 1,
                'template' => 'payment',
                'delivery' => 0,
                'shipped' => 0,
                'color' => '#108510',
                'pdf_invoice' => 1,
                'pdf_delivery' => 0,
            ],
            [
                'status' => 'NGENIUS_AUTH_REVERSED',
                'label' => 'N-Genius Online Auth Reversed',
                'invoice' => 0,
                'send_email' => 0,
                'template' => '',
                'delivery' => 0,
                'shipped' => 0,
                'color' => '#DC143C',
                'pdf_invoice' => 0,
                'pdf_delivery' => 0,
            ],
            [
                'status' => 'NGENIUS_FULLY_REFUNDED',
                'label' => 'N-Genius Online Fully Refunded',
                'invoice' => 0,
                'send_email' => 1,
                'template' => 'refund',
                'delivery' => 0,
                'shipped' => 0,
                'color' => '#ec2e15',
                'pdf_invoice' => 0,
                'pdf_delivery' => 0,
            ],
            [
                'status' => 'NGENIUS_PARTIALLY_REFUNDED',
                'label' => 'N-Genius Online Partially Refunded',
                'invoice' => 0,
                'send_email' => 1,
                'template' => 'refund',
                'delivery' => 0,
                'shipped' => 0,
                'color' => '#ec2e15',
                'pdf_invoice' => 0,
                'pdf_delivery' => 0,
            ],
        ];
    }

    /**
     * Hosted Session Ngenius Flash Message.
     *
     * @return true
     */
    public function addNgeniusFlashMessage($message)
    {
        $cookie = Context::getContext()->cookie;
        $cookie->__set('hsngenius_flashes', $message);
        $cookie->write();
        return true;
    }

    /**
     * Validate an order in database
     * Function called from a payment module
     *
     * @param int $id_cart
     * @param int $id_order_state
     * @param float   $amount_paid    Amount really paid by customer (in the default currency)
     * @param string  $payment_method Payment method (eg. 'Credit card')
     * @param null    $message        Message to attach to order
     * @param array   $extra_vars
     * @param null    $currency_special
     * @param bool    $dont_touch_amount
     * @param bool    $secure_key
     * @param Shop    $shop
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function validateOrder(
        $id_cart,
        $id_order_state,
        $amount_paid,
        $payment_method = 'Unknown',
        $message = null,
        $extra_vars = array(),
        $currency_special = null,
        $dont_touch_amount = false,
        $secure_key = false,
        Shop $shop = null
    ) {

        $command = new Command();
        if (self::DEBUG_MODE) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Function called', 1, null, 'Cart', (int)$id_cart, true);
        }

        if (!isset($this->context)) {
            $this->context = Context::getContext();
        }
        $this->context->cart = new Cart((int)$id_cart);
        $this->context->customer = new Customer((int)$this->context->cart->id_customer);
        // The tax cart is loaded before the customer so re-cache the tax calculation method
        $this->context->cart->setTaxCalculationMethod();

        $this->context->language = new Language((int)$this->context->cart->id_lang);
        $this->context->shop = ($shop ? $shop : new Shop((int)$this->context->cart->id_shop));
        ShopUrl::resetMainDomainCache();
        $id_currency = $currency_special ? (int)$currency_special : (int)$this->context->cart->id_currency;
        $this->context->currency = new Currency((int)$id_currency, null, (int)$this->context->shop->id);
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
            $context_country = $this->context->country;
        }

        $order_status = new OrderState((int)$id_order_state, (int)$this->context->language->id);
        if (!Validate::isLoadedObject($order_status)) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Order Status cannot be loaded', 3, null, 'Cart', (int)$id_cart, true);
            throw new PrestaShopException('Can\'t load Order status');
        }

        if (!$this->active) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Module is not active', 3, null, 'Cart', (int)$id_cart, true);
            die(Tools::displayError());
        }

        // Does order already exists ?
        if (Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists() == false) {
            if ($secure_key !== false && $secure_key != $this->context->cart->secure_key) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - Secure key does not match', 3, null, 'Cart', (int)$id_cart, true);
                die(Tools::displayError());
            }

            // For each package, generate an order
            $delivery_option_list = $this->context->cart->getDeliveryOptionList();
            $package_list = $this->context->cart->getPackageList();
            $cart_delivery_option = $this->context->cart->getDeliveryOption();

            // If some delivery options are not defined, or not valid, use the first valid option
            foreach ($delivery_option_list as $id_address => $package) {
                if (!isset($cart_delivery_option[$id_address]) || !array_key_exists($cart_delivery_option[$id_address], $package)) {
                    foreach ($package as $key => $val) {
                        $cart_delivery_option[$id_address] = $key;
                        break;
                    }
                }
            }

            $order_list = array();
            $order_detail_list = array();

            do {
                $reference = Order::generateReference();
            } while (Order::getByReference($reference)->count());

            $this->currentOrderReference = $reference;

            $order_creation_failed = false;
            $cart_total_paid = (float)Tools::ps_round((float)$this->context->cart->getOrderTotal(true, Cart::BOTH), 2);

            foreach ($cart_delivery_option as $id_address => $key_carriers) {
                foreach ($delivery_option_list[$id_address][$key_carriers]['carrier_list'] as $id_carrier => $data) {
                    foreach ($data['package_list'] as $id_package) {
                        // Rewrite the id_warehouse
                        $package_list[$id_address][$id_package]['id_warehouse'] = (int)$this->context->cart->getPackageIdWarehouse($package_list[$id_address][$id_package], (int)$id_carrier);
                        $package_list[$id_address][$id_package]['id_carrier'] = $id_carrier;
                    }
                }
            }
            // Make sure CartRule caches are empty
            CartRule::cleanCache();
            $cart_rules = $this->context->cart->getCartRules();
            foreach ($cart_rules as $cart_rule) {
                if (($rule = new CartRule((int)$cart_rule['obj']->id)) && Validate::isLoadedObject($rule)) {
                    if ($error = $rule->checkValidity($this->context, true, true)) {
                        $this->context->cart->removeCartRule((int)$rule->id);
                        if (isset($this->context->cookie) && isset($this->context->cookie->id_customer) && $this->context->cookie->id_customer && !empty($rule->code)) {
                            if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                                Tools::redirect('index.php?controller=order-opc&submitAddDiscount=1&discount_name='.urlencode($rule->code));
                            }
                            Tools::redirect('index.php?controller=order&submitAddDiscount=1&discount_name='.urlencode($rule->code));
                        } else {
                            $rule_name = isset($rule->name[(int)$this->context->cart->id_lang]) ? $rule->name[(int)$this->context->cart->id_lang] : $rule->code;
                            $error = sprintf(Tools::displayError('CartRule ID %1s (%2s) used in this cart is not valid and has been withdrawn from cart'), (int)$rule->id, $rule_name);
                            PrestaShopLogger::addLog($error, 3, '0000002', 'Cart', (int)$this->context->cart->id);
                        }
                    }
                }
            }

            foreach ($package_list as $id_address => $packageByAddress) {
                foreach ($packageByAddress as $id_package => $package) {
                    /** @var Order $order */
                    $order = new Order();
                    $order->product_list = $package['product_list'];

                    if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
                        $address = new Address((int)$id_address);
                        $this->context->country = new Country((int)$address->id_country, (int)$this->context->cart->id_lang);
                        if (!$this->context->country->active) {
                            throw new PrestaShopException('The delivery address country is not active.');
                        }
                    }

                    $carrier = null;
                    if (!$this->context->cart->isVirtualCart() && isset($package['id_carrier'])) {
                        $carrier = new Carrier((int)$package['id_carrier'], (int)$this->context->cart->id_lang);
                        $order->id_carrier = (int)$carrier->id;
                        $id_carrier = (int)$carrier->id;
                    } else {
                        $order->id_carrier = 0;
                        $id_carrier = 0;
                    }

                    $order->id_customer = (int)$this->context->cart->id_customer;
                    $order->id_address_invoice = (int)$this->context->cart->id_address_invoice;
                    $order->id_address_delivery = (int)$id_address;
                    $order->id_currency = $this->context->currency->id;
                    $order->id_lang = (int)$this->context->cart->id_lang;
                    $order->id_cart = (int)$this->context->cart->id;
                    $order->reference = $reference;
                    $order->id_shop = (int)$this->context->shop->id;
                    $order->id_shop_group = (int)$this->context->shop->id_shop_group;

                    $order->secure_key = ($secure_key ? pSQL($secure_key) : pSQL($this->context->customer->secure_key));
                    $order->payment = $payment_method;
                    if (isset($this->name)) {
                        $order->module = $this->name;
                    }
                    $order->recyclable = $this->context->cart->recyclable;
                    $order->gift = (int)$this->context->cart->gift;
                    $order->gift_message = $this->context->cart->gift_message;
                    $order->mobile_theme = $this->context->cart->mobile_theme;
                    $order->conversion_rate = $this->context->currency->conversion_rate;
                    $amount_paid = !$dont_touch_amount ? Tools::ps_round((float)$amount_paid, 2) : $amount_paid;
                    $order->total_paid_real = 0;

                    $order->total_products = (float)$this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS, $order->product_list, $id_carrier);
                    $order->total_products_wt = (float)$this->context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS, $order->product_list, $id_carrier);
                    $order->total_discounts_tax_excl = (float)abs($this->context->cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS, $order->product_list, $id_carrier));
                    $order->total_discounts_tax_incl = (float)abs($this->context->cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS, $order->product_list, $id_carrier));
                    $order->total_discounts = $order->total_discounts_tax_incl;

                    $order->total_shipping_tax_excl = (float)$this->context->cart->getPackageShippingCost((int)$id_carrier, false, null, $order->product_list);
                    $order->total_shipping_tax_incl = (float)$this->context->cart->getPackageShippingCost((int)$id_carrier, true, null, $order->product_list);
                    $order->total_shipping = $order->total_shipping_tax_incl;

                    if (!is_null($carrier) && Validate::isLoadedObject($carrier)) {
                        $order->carrier_tax_rate = $carrier->getTaxesRate(new Address((int)$this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
                    }

                    $order->total_wrapping_tax_excl = (float)abs($this->context->cart->getOrderTotal(false, Cart::ONLY_WRAPPING, $order->product_list, $id_carrier));
                    $order->total_wrapping_tax_incl = (float)abs($this->context->cart->getOrderTotal(true, Cart::ONLY_WRAPPING, $order->product_list, $id_carrier));
                    $order->total_wrapping = $order->total_wrapping_tax_incl;

                    $order->total_paid_tax_excl = (float)Tools::ps_round((float)$this->context->cart->getOrderTotal(false, Cart::BOTH, $order->product_list, $id_carrier), _PS_PRICE_COMPUTE_PRECISION_);
                    $order->total_paid_tax_incl = (float)Tools::ps_round((float)$this->context->cart->getOrderTotal(true, Cart::BOTH, $order->product_list, $id_carrier), _PS_PRICE_COMPUTE_PRECISION_);
                    $order->total_paid = $order->total_paid_tax_incl;
                    $order->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
                    $order->round_type = Configuration::get('PS_ROUND_TYPE');

                    $order->invoice_date = '0000-00-00 00:00:00';
                    $order->delivery_date = '0000-00-00 00:00:00';

                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Order is about to be added', 1, null, 'Cart', (int)$id_cart, true);
                    }

                    // Creating order
                    $result = $order->add();

                    if (!$result) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Order cannot be created', 3, null, 'Cart', (int)$id_cart, true);
                        throw new PrestaShopException('Can\'t save Order');
                    }

                    // Amount paid by customer is not the right one -> Status = payment error
                    // We don't use the following condition to avoid the float precision issues : http://www.php.net/manual/en/language.types.float.php
                    // if ($order->total_paid != $order->total_paid_real)
                    // We use number_format in order to compare two string
                    if ($order_status->logable && number_format($cart_total_paid, _PS_PRICE_COMPUTE_PRECISION_) != number_format($amount_paid, _PS_PRICE_COMPUTE_PRECISION_)) {
                        $id_order_state = Configuration::get('PS_OS_ERROR');
                    }

                    $order_list[] = $order;

                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - OrderDetail is about to be added', 1, null, 'Cart', (int)$id_cart, true);
                    }

                    // Insert new Order detail list using cart for the current order
                    $order_detail = new OrderDetail(null, null, $this->context);
                    $order_detail->createList($order, $this->context->cart, $id_order_state, $order->product_list, 0, true, $package_list[$id_address][$id_package]['id_warehouse']);
                    $order_detail_list[] = $order_detail;

                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - OrderCarrier is about to be added', 1, null, 'Cart', (int)$id_cart, true);
                    }

                    // Adding an entry in order_carrier table
                    if (!is_null($carrier)) {
                        $order_carrier = new OrderCarrier();
                        $order_carrier->id_order = (int)$order->id;
                        $order_carrier->id_carrier = (int)$id_carrier;
                        $order_carrier->weight = (float)$order->getTotalWeight();
                        $order_carrier->shipping_cost_tax_excl = (float)$order->total_shipping_tax_excl;
                        $order_carrier->shipping_cost_tax_incl = (float)$order->total_shipping_tax_incl;
                        $order_carrier->add();
                    }
                }
            }

            // The country can only change if the address used for the calculation is the delivery address, and if multi-shipping is activated
            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
                $this->context->country = $context_country;
            }

            if (!$this->context->country->active) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - Country is not active', 3, null, 'Cart', (int)$id_cart, true);
                throw new PrestaShopException('The order address country is not active.');
            }

            if (self::DEBUG_MODE) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - Payment is about to be added', 1, null, 'Cart', (int)$id_cart, true);
            }

            // Register Payment only if the order status validate the order
            if ($order_status->logable) {
                // $order is the last order loop in the foreach
                // The method addOrderPayment of the class Order make a create a paymentOrder
                // linked to the order reference and not to the order id
                if (isset($extra_vars['transaction_id'])) {
                    $transaction_id = $extra_vars['transaction_id'];
                } else {
                    $transaction_id = null;
                }

                if (!isset($order) || !Validate::isLoadedObject($order) || !$order->addOrderPayment($amount_paid, null, $transaction_id)) {
                    PrestaShopLogger::addLog('PaymentModule::validateOrder - Cannot save Order Payment', 3, null, 'Cart', (int)$id_cart, true);
                    throw new PrestaShopException('Can\'t save Order Payment');
                }
            }

            // Next !
            $only_one_gift = false;
            $cart_rule_used = array();
            $products = $this->context->cart->getProducts();

            // Make sure CartRule caches are empty
            CartRule::cleanCache();
            foreach ($order_detail_list as $key => $order_detail) {
                /** @var OrderDetail $order_detail */

                $order = $order_list[$key];
                if (!$order_creation_failed && isset($order->id)) {
                    if (!$secure_key) {
                        $message .= '<br />'.Tools::displayError('Warning: the secure key is empty, check your payment account before validation');
                    }
                    // Optional message to attach to this order
                    if (isset($message) & !empty($message)) {
                        $msg = new Message();
                        $message = strip_tags($message, '<br>');
                        if (Validate::isCleanHtml($message)) {
                            if (self::DEBUG_MODE) {
                                PrestaShopLogger::addLog('PaymentModule::validateOrder - Message is about to be added', 1, null, 'Cart', (int)$id_cart, true);
                            }
                            $msg->message = $message;
                            $msg->id_cart = (int)$id_cart;
                            $msg->id_customer = (int)($order->id_customer);
                            $msg->id_order = (int)$order->id;
                            $msg->private = 1;
                            $msg->add();
                        }
                    }

                    // Insert new Order detail list using cart for the current order
                    //$orderDetail = new OrderDetail(null, null, $this->context);
                    //$orderDetail->createList($order, $this->context->cart, $id_order_state);

                    // Construct order detail table for the email
                    $products_list = '';
                    $virtual_product = true;

                    $product_var_tpl_list = array();
                    foreach ($order->product_list as $product) {
                        $price = Product::getPriceStatic((int)$product['id_product'], false, ($product['id_product_attribute'] ? (int)$product['id_product_attribute'] : null), 6, null, false, true, $product['cart_quantity'], false, (int)$order->id_customer, (int)$order->id_cart, (int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
                        $price_wt = Product::getPriceStatic((int)$product['id_product'], true, ($product['id_product_attribute'] ? (int)$product['id_product_attribute'] : null), 2, null, false, true, $product['cart_quantity'], false, (int)$order->id_customer, (int)$order->id_cart, (int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});

                        $product_price = Product::getTaxCalculationMethod() == PS_TAX_EXC ? Tools::ps_round($price, 2) : $price_wt;

                        $product_var_tpl = array(
                            'reference' => $product['reference'],
                            'name' => $product['name'].(isset($product['attributes']) ? ' - '.$product['attributes'] : ''),
                            'unit_price' => Tools::displayPrice($product_price, $this->context->currency, false),
                            'price' => Tools::displayPrice($product_price * $product['quantity'], $this->context->currency, false),
                            'quantity' => $product['quantity'],
                            'customization' => array()
                        );

                        $customized_datas = Product::getAllCustomizedDatas((int)$order->id_cart);
                        if (isset($customized_datas[$product['id_product']][$product['id_product_attribute']])) {
                            $product_var_tpl['customization'] = array();
                            foreach ($customized_datas[$product['id_product']][$product['id_product_attribute']][$order->id_address_delivery] as $customization) {
                                $customization_text = '';
                                if (isset($customization['datas'][Product::CUSTOMIZE_TEXTFIELD])) {
                                    foreach ($customization['datas'][Product::CUSTOMIZE_TEXTFIELD] as $text) {
                                        $customization_text .= $text['name'].': '.$text['value'].'<br />';
                                    }
                                }

                                if (isset($customization['datas'][Product::CUSTOMIZE_FILE])) {
                                    $customization_text .= sprintf(Tools::displayError('%d image(s)'), count($customization['datas'][Product::CUSTOMIZE_FILE])).'<br />';
                                }

                                $customization_quantity = (int)$product['customization_quantity'];

                                $product_var_tpl['customization'][] = array(
                                    'customization_text' => $customization_text,
                                    'customization_quantity' => $customization_quantity,
                                    'quantity' => Tools::displayPrice($customization_quantity * $product_price, $this->context->currency, false)
                                );
                            }
                        }

                        $product_var_tpl_list[] = $product_var_tpl;
                        // Check if is not a virutal product for the displaying of shipping
                        if (!$product['is_virtual']) {
                            $virtual_product &= false;
                        }
                    } // end foreach ($products)

                    $product_list_txt = '';
                    $product_list_html = '';
                    if (count($product_var_tpl_list) > 0) {
                        $product_list_txt = $this->getEmailTemplateContent('order_conf_product_list.txt', Mail::TYPE_TEXT, $product_var_tpl_list);
                        $product_list_html = $this->getEmailTemplateContent('order_conf_product_list.tpl', Mail::TYPE_HTML, $product_var_tpl_list);
                    }

                    $cart_rules_list = array();
                    $total_reduction_value_ti = 0;
                    $total_reduction_value_tex = 0;
                    foreach ($cart_rules as $cart_rule) {
                        $package = array('id_carrier' => $order->id_carrier, 'id_address' => $order->id_address_delivery, 'products' => $order->product_list);
                        $values = array(
                            'tax_incl' => $cart_rule['obj']->getContextualValue(true, $this->context, CartRule::FILTER_ACTION_ALL_NOCAP, $package),
                            'tax_excl' => $cart_rule['obj']->getContextualValue(false, $this->context, CartRule::FILTER_ACTION_ALL_NOCAP, $package)
                        );

                        // If the reduction is not applicable to this order, then continue with the next one
                        if (!$values['tax_excl']) {
                            continue;
                        }

                        // IF
                        //  This is not multi-shipping
                        //  The value of the voucher is greater than the total of the order
                        //  Partial use is allowed
                        //  This is an "amount" reduction, not a reduction in % or a gift
                        // THEN
                        //  The voucher is cloned with a new value corresponding to the remainder
                        if (count($order_list) == 1 && $values['tax_incl'] > ($order->total_products_wt - $total_reduction_value_ti) && $cart_rule['obj']->partial_use == 1 && $cart_rule['obj']->reduction_amount > 0) {
                            // Create a new voucher from the original
                            $voucher = new CartRule((int)$cart_rule['obj']->id); // We need to instantiate the CartRule without lang parameter to allow saving it
                            unset($voucher->id);

                            // Set a new voucher code
                            $voucher->code = empty($voucher->code) ? substr(md5($order->id.'-'.$order->id_customer.'-'.$cart_rule['obj']->id), 0, 16) : $voucher->code.'-2';
                            if (preg_match('/\-([0-9]{1,2})\-([0-9]{1,2})$/', $voucher->code, $matches) && $matches[1] == $matches[2]) {
                                $voucher->code = preg_replace('/'.$matches[0].'$/', '-'.(intval($matches[1]) + 1), $voucher->code);
                            }

                            // Set the new voucher value
                            if ($voucher->reduction_tax) {
                                $voucher->reduction_amount = ($total_reduction_value_ti + $values['tax_incl']) - $order->total_products_wt;

                                // Add total shipping amout only if reduction amount > total shipping
                                if ($voucher->free_shipping == 1 && $voucher->reduction_amount >= $order->total_shipping_tax_incl) {
                                    $voucher->reduction_amount -= $order->total_shipping_tax_incl;
                                }
                            } else {
                                $voucher->reduction_amount = ($total_reduction_value_tex + $values['tax_excl']) - $order->total_products;

                                // Add total shipping amout only if reduction amount > total shipping
                                if ($voucher->free_shipping == 1 && $voucher->reduction_amount >= $order->total_shipping_tax_excl) {
                                    $voucher->reduction_amount -= $order->total_shipping_tax_excl;
                                }
                            }
                            if ($voucher->reduction_amount <= 0) {
                                continue;
                            }

                            if ($this->context->customer->isGuest()) {
                                $voucher->id_customer = 0;
                            } else {
                                $voucher->id_customer = $order->id_customer;
                            }

                            $voucher->quantity = 1;
                            $voucher->reduction_currency = $order->id_currency;
                            $voucher->quantity_per_user = 1;
                            $voucher->free_shipping = 0;
                            if ($voucher->add()) {
                                // If the voucher has conditions, they are now copied to the new voucher
                                CartRule::copyConditions($cart_rule['obj']->id, $voucher->id);

                                $params = array(
                                    '{voucher_amount}' => Tools::displayPrice($voucher->reduction_amount, $this->context->currency, false),
                                    '{voucher_num}' => $voucher->code,
                                    '{firstname}' => $this->context->customer->firstname,
                                    '{lastname}' => $this->context->customer->lastname,
                                    '{id_order}' => $order->reference,
                                    '{order_name}' => $order->getUniqReference()
                                );
                                Mail::Send(
                                    (int)$order->id_lang,
                                    'voucher',
                                    sprintf(Mail::l('New voucher for your order %s', (int)$order->id_lang), $order->reference),
                                    $params,
                                    $this->context->customer->email,
                                    $this->context->customer->firstname.' '.$this->context->customer->lastname,
                                    null,
                                    null,
                                    null,
                                    null,
                                    _PS_MAIL_DIR_,
                                    false,
                                    (int)$order->id_shop
                                );
                            }

                            $values['tax_incl'] = $order->total_products_wt - $total_reduction_value_ti;
                            $values['tax_excl'] = $order->total_products - $total_reduction_value_tex;
                        }
                        $total_reduction_value_ti += $values['tax_incl'];
                        $total_reduction_value_tex += $values['tax_excl'];

                        $order->addCartRule($cart_rule['obj']->id, $cart_rule['obj']->name, $values, 0, $cart_rule['obj']->free_shipping);

                        if ($id_order_state != Configuration::get('PS_OS_ERROR') && $id_order_state != Configuration::get('PS_OS_CANCELED') && !in_array($cart_rule['obj']->id, $cart_rule_used)) {
                            $cart_rule_used[] = $cart_rule['obj']->id;

                            // Create a new instance of Cart Rule without id_lang, in order to update its quantity
                            $cart_rule_to_update = new CartRule((int)$cart_rule['obj']->id);
                            $cart_rule_to_update->quantity = max(0, $cart_rule_to_update->quantity - 1);
                            $cart_rule_to_update->update();
                        }

                        $cart_rules_list[] = array(
                            'voucher_name' => $cart_rule['obj']->name,
                            'voucher_reduction' => ($values['tax_incl'] != 0.00 ? '-' : '').Tools::displayPrice($values['tax_incl'], $this->context->currency, false)
                        );
                    }

                    $cart_rules_list_txt = '';
                    $cart_rules_list_html = '';
                    if (count($cart_rules_list) > 0) {
                        $cart_rules_list_txt = $this->getEmailTemplateContent('order_conf_cart_rules.txt', Mail::TYPE_TEXT, $cart_rules_list);
                        $cart_rules_list_html = $this->getEmailTemplateContent('order_conf_cart_rules.tpl', Mail::TYPE_HTML, $cart_rules_list);
                    }

                    // Specify order id for message
                    $old_message = Message::getMessageByCartId((int)$this->context->cart->id);
                    if ($old_message && !$old_message['private']) {
                        $update_message = new Message((int)$old_message['id_message']);
                        $update_message->id_order = (int)$order->id;
                        $update_message->update();

                        // Add this message in the customer thread
                        $customer_thread = new CustomerThread();
                        $customer_thread->id_contact = 0;
                        $customer_thread->id_customer = (int)$order->id_customer;
                        $customer_thread->id_shop = (int)$this->context->shop->id;
                        $customer_thread->id_order = (int)$order->id;
                        $customer_thread->id_lang = (int)$this->context->language->id;
                        $customer_thread->email = $this->context->customer->email;
                        $customer_thread->status = 'open';
                        $customer_thread->token = Tools::passwdGen(12);
                        $customer_thread->add();

                        $customer_message = new CustomerMessage();
                        $customer_message->id_customer_thread = $customer_thread->id;
                        $customer_message->id_employee = 0;
                        $customer_message->message = $update_message->message;
                        $customer_message->private = 0;

                        if (!$customer_message->add()) {
                            $this->errors[] = Tools::displayError('An error occurred while saving message');
                        }
                    }

                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Hook validateOrder is about to be called', 1, null, 'Cart', (int)$id_cart, true);
                    }

                    // Hook validate order
                    Hook::exec('actionValidateOrder', array(
                        'cart' => $this->context->cart,
                        'order' => $order,
                        'customer' => $this->context->customer,
                        'currency' => $this->context->currency,
                        'orderStatus' => $order_status
                    ));

                    foreach ($this->context->cart->getProducts() as $product) {
                        if ($order_status->logable) {
                            ProductSale::addProductSale((int)$product['id_product'], (int)$product['cart_quantity']);
                        }
                    }

                    if (self::DEBUG_MODE) {
                        PrestaShopLogger::addLog('PaymentModule::validateOrder - Order Status is about to be added', 1, null, 'Cart', (int)$id_cart, true);
                    }

                    // Set the order status
                    $new_history = new OrderHistory();
                    $new_history->id_order = (int)$order->id;
                    $new_history->changeIdOrderState((int)$id_order_state, $order, true);
                    $new_history->addWithemail(true, $extra_vars);

                    // Switch to back order if needed
                    if (Configuration::get('PS_STOCK_MANAGEMENT') && ($order_detail->getStockState() || $order_detail->product_quantity_in_stock <= 0)) {
                        $history = new OrderHistory();
                        $history->id_order = (int)$order->id;
                        $history->changeIdOrderState(Configuration::get($order->valid ? 'PS_OS_OUTOFSTOCK_PAID' : 'PS_OS_OUTOFSTOCK_UNPAID'), $order, true);
                        $history->addWithemail();
                    }

                    unset($order_detail);

                    // Order is reloaded because the status just changed
                    $order = new Order((int)$order->id);

                    // Send an e-mail to customer (one order = one email)
                    if ($id_order_state != Configuration::get('PS_OS_ERROR') && $id_order_state != Configuration::get('PS_OS_CANCELED') && $this->context->customer->id) {
                        $invoice = new Address((int)$order->id_address_invoice);
                        $delivery = new Address((int)$order->id_address_delivery);
                        $delivery_state = $delivery->id_state ? new State((int)$delivery->id_state) : false;
                        $invoice_state = $invoice->id_state ? new State((int)$invoice->id_state) : false;

                        $data = array(
                        '{firstname}' => $this->context->customer->firstname,
                        '{lastname}' => $this->context->customer->lastname,
                        '{email}' => $this->context->customer->email,
                        '{delivery_block_txt}' => $this->_getFormatedAddress($delivery, "\n"),
                        '{invoice_block_txt}' => $this->_getFormatedAddress($invoice, "\n"),
                        '{delivery_block_html}' => $this->_getFormatedAddress($delivery, '<br />', array(
                            'firstname'    => '<span style="font-weight:bold;">%s</span>',
                            'lastname'    => '<span style="font-weight:bold;">%s</span>'
                        )),
                        '{invoice_block_html}' => $this->_getFormatedAddress($invoice, '<br />', array(
                                'firstname'    => '<span style="font-weight:bold;">%s</span>',
                                'lastname'    => '<span style="font-weight:bold;">%s</span>'
                        )),
                        '{delivery_company}' => $delivery->company,
                        '{delivery_firstname}' => $delivery->firstname,
                        '{delivery_lastname}' => $delivery->lastname,
                        '{delivery_address1}' => $delivery->address1,
                        '{delivery_address2}' => $delivery->address2,
                        '{delivery_city}' => $delivery->city,
                        '{delivery_postal_code}' => $delivery->postcode,
                        '{delivery_country}' => $delivery->country,
                        '{delivery_state}' => $delivery->id_state ? $delivery_state->name : '',
                        '{delivery_phone}' => ($delivery->phone) ? $delivery->phone : $delivery->phone_mobile,
                        '{delivery_other}' => $delivery->other,
                        '{invoice_company}' => $invoice->company,
                        '{invoice_vat_number}' => $invoice->vat_number,
                        '{invoice_firstname}' => $invoice->firstname,
                        '{invoice_lastname}' => $invoice->lastname,
                        '{invoice_address2}' => $invoice->address2,
                        '{invoice_address1}' => $invoice->address1,
                        '{invoice_city}' => $invoice->city,
                        '{invoice_postal_code}' => $invoice->postcode,
                        '{invoice_country}' => $invoice->country,
                        '{invoice_state}' => $invoice->id_state ? $invoice_state->name : '',
                        '{invoice_phone}' => ($invoice->phone) ? $invoice->phone : $invoice->phone_mobile,
                        '{invoice_other}' => $invoice->other,
                        '{order_name}' => $order->getUniqReference(),
                        '{date}' => Tools::displayDate(date('Y-m-d H:i:s'), null, 1),
                        '{carrier}' => ($virtual_product || !isset($carrier->name)) ? Tools::displayError('No carrier') : $carrier->name,
                        '{payment}' => Tools::substr($order->payment, 0, 32),
                        '{products}' => $product_list_html,
                        '{products_txt}' => $product_list_txt,
                        '{discounts}' => $cart_rules_list_html,
                        '{discounts_txt}' => $cart_rules_list_txt,
                        '{total_paid}' => Tools::displayPrice($order->total_paid, $this->context->currency, false),
                        '{total_products}' => Tools::displayPrice(Product::getTaxCalculationMethod() == PS_TAX_EXC ? $order->total_products : $order->total_products_wt, $this->context->currency, false),
                        '{total_discounts}' => Tools::displayPrice($order->total_discounts, $this->context->currency, false),
                        '{total_shipping}' => Tools::displayPrice($order->total_shipping, $this->context->currency, false),
                        '{total_wrapping}' => Tools::displayPrice($order->total_wrapping, $this->context->currency, false),
                        '{total_tax_paid}' => Tools::displayPrice(($order->total_products_wt - $order->total_products) + ($order->total_shipping_tax_incl - $order->total_shipping_tax_excl), $this->context->currency, false));

                        if (is_array($extra_vars)) {
                            $data = array_merge($data, $extra_vars);
                        }

                        // Join PDF invoice
                        if ((int)Configuration::get('PS_INVOICE') && $order_status->invoice && $order->invoice_number) {
                            $order_invoice_list = $order->getInvoicesCollection();
                            Hook::exec('actionPDFInvoiceRender', array('order_invoice_list' => $order_invoice_list));
                            $pdf = new PDF($order_invoice_list, PDF::TEMPLATE_INVOICE, $this->context->smarty);
                            $file_attachement['content'] = $pdf->render(false);
                            $file_attachement['name'] = Configuration::get('PS_INVOICE_PREFIX', (int)$order->id_lang, null, $order->id_shop).sprintf('%06d', $order->invoice_number).'.pdf';
                            $file_attachement['mime'] = 'application/pdf';
                        } else {
                            $file_attachement = null;
                        }

                        if (self::DEBUG_MODE) {
                            PrestaShopLogger::addLog('PaymentModule::validateOrder - Mail is about to be sent', 1, null, 'Cart', (int)$id_cart, true);
                        }
                        // mail data store ngenius table
                        $mailData = array(
                            'id_order' => (int) $order->id,
                            'data' => serialize($data),
                        );
                        $command->addNgeniusOrderEmailContent($mailData);
                    }

                    // updates stock in shops
                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                        $product_list = $order->getProducts();
                        foreach ($product_list as $product) {
                            // if the available quantities depends on the physical stock
                            if (StockAvailable::dependsOnStock($product['product_id'])) {
                                // synchronizes
                                StockAvailable::synchronize($product['product_id'], $order->id_shop);
                            }
                        }
                    }

                    $order->updateOrderDetailTax();
                } else {
                    $error = Tools::displayError('Order creation failed');
                    PrestaShopLogger::addLog($error, 4, '0000002', 'Cart', intval($order->id_cart));
                    die($error);
                }
            } // End foreach $order_detail_list

            // Use the last order as currentOrder
            if (isset($order) && $order->id) {
                $this->currentOrder = (int)$order->id;
            }

            if (self::DEBUG_MODE) {
                PrestaShopLogger::addLog('PaymentModule::validateOrder - End of validateOrder', 1, null, 'Cart', (int)$id_cart, true);
            }

            return true;
        } else {
            $error = Tools::displayError('Cart cannot be loaded or an order has already been placed using this cart');
            PrestaShopLogger::addLog($error, 4, '0000001', 'Cart', intval($this->context->cart->id));
            die($error);
        }
    }
}
