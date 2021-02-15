<?php

/**
 * AdminNgeniuscronlog Controller
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

class AdminNgeniuscronlogController extends AdminController
{

    /**
     * function __construct
     *
     * @return void
     */
    
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'ngenius_cron_log';
        $this->lang = false;
        $this->explicitSelect = false;
        $this->allow_export = false;
        $this->deleted = false;
        $this->_orderBy = 'id';
        $this->_orderWay = 'DESC';
        $this->list_no_link = true;
        
        parent::__construct();
        $this->_use_found_rows = false;

        
        $this->fields_list = array(
            
            'id_order' => array(
                'title' => $this->l('Order Id', array(), 'Admin.Global'),
                'orderby' => false,
                
            ),
            'id_cart' => array(
                'title' => $this->l('Cart Id', array(), 'Admin.Global'),
                'orderby' => false,
                
            ),
           
            'try_num' => array(
                'title' => $this->l('Try Count', array(), 'Admin.Global'),
                'orderby' => false,
            ),
            
             'reference' => array(
                'title' => $this->l('Reference', array(), 'Admin.Global'),
                'orderby' => false,
            ),
            
            'from_state' => array(
                'title' => $this->l('Previous State', array(), 'Admin.Global'),
                'orderby' => false,
            ),

            'response' => array(
                'title' => $this->l('Current State | Amount | Auth Response', array(), 'Admin.Global'),
                'orderby' => false,
            ),

            'created_at' => array(
                'title' => $this->l('Ran At', array(), 'Admin.Global'),
                'orderby' => false,
                'type' => 'datetime',
                'filter_key' => 'a!created_at',
            ),
            
            'status' => array(
                'title' => $this->l('Status', array(), 'Admin.Global'),
                'orderby' => false,
            )
        );
    }
}
