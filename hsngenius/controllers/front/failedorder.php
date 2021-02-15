<?php
/**
 * Hosted Session Ngenius Redirect Module Failed order Controller
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */


class HsngeniusFailedorderModuleFrontController extends ModuleFrontController
{

    /**
     * Processing of API response
     *
     * @return void
     */
    public function postProcess()
    {

        $this->display_column_left = false;
        $this->display_column_right = false;

        $this->context->smarty->assign([
            'module' => Configuration::get('DISPLAY_NAME'),
        ]);
        $this->setTemplate('payment_error.tpl');
    }
}
