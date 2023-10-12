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
        $this->context->smarty->assign([
            'module' => \Configuration::get('DISPLAY_NAME'),
        ]);
        $this->setTemplate('module:ngenius/views/templates/front/payment_error.tpl');
    }
}
