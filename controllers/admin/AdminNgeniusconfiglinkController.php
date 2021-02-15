<?php
class AdminNgeniusconfiglinkController extends ModuleAdminController
{
     
    public function __construct()
    {
         
        $this->className = 'AdminNgeniusconfiglink';
        parent::__construct();
    }
     
    public function init()
    {
         Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . Tools::safeOutput($this->module->name));
         parent::init();
    }
}
