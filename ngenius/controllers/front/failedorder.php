<?php

class NGeniusFailedorderModuleFrontController extends ModuleFrontController
{
    /**
     * Processing of API response
     *
     * @return void
     */
    public function postProcess(): void
    {
        $status = Tools::getValue('status', 'Declined'); // Default to "Declined"
        $this->context->smarty->assign([
                                           'module' => \Configuration::get('DISPLAY_NAME'),
                                           'status' => $status,
                                       ]);
        $this->setTemplate('module:ngenius/views/templates/front/payment_error.tpl');
    }
}
