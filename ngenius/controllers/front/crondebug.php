<?php

class NGeniusCronDebugModuleFrontController extends ModuleFrontController
{
    /**
     * Processing of API response
     *
     * @return void
     * @throws PrestaShopException
     */
    public function postProcess(): void
    {
        $this->context->smarty->assign([
                                           'module' => \Configuration::get('DISPLAY_NAME'),
                                       ]);
        $this->setTemplate('module:ngenius/views/templates/front/cron_debug.tpl');
    }
}
