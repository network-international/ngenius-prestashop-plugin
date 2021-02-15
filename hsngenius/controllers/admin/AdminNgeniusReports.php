<?php

/**
 * Admin Ngenius Reports Controller
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

class AdminNgeniusReportsController extends AdminController
{

    /**
     * function __construct
     *
     * @return void
     */
    
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'ngenius_networkinternational';
        $this->lang = false;
        $this->explicitSelect = false;
        $this->allow_export = false;
        $this->deleted = false;
        $this->_orderBy = 'nid';
        $this->_orderWay = 'DESC';
        $this->list_no_link = true;
        
        $this->statusArr = [
            'NGENIUS_PENDING'           => 'N-Genius Online Pending',
            'NGENIUS_AWAIT_3DS'         => 'N-Genius Online Await 3ds',
            'NGENIUS_PROCESSING'        => 'N-Genius Online Processing',
            'NGENIUS_FAILED'            => 'N-Genius Online Failed',
            'NGENIUS_COMPLETE'          => 'N-Genius Online Complete',
            'NGENIUS_AUTHORISED'        => 'N-Genius Online Authorised',
            'NGENIUS_FULLY_CAPTURED'    => 'N-Genius Online Fully Captured',
            'NGENIUS_AUTH_REVERSED'     => 'N-Genius Online Auth Reversed',
            'NGENIUS_FULLY_REFUNDED'    => 'N-Genius Online Fully Refunded',
            'NGENIUS_PARTIALLY_REFUNDED' => 'N-Genius Online Partially Refunded'
        ];
        
        $this->apiTypeArr = [
            'direct'  => 'Direct',
            'queryapi' => 'Query API',
            'cron' => 'Cron',
        ];

        parent::__construct();
        $this->_use_found_rows = false;

        
        $this->fields_list = array(
            
            'id_order' => array(
                'title' => $this->l('Order Id', array(), 'Admin.Global'),
                'orderby' => false,
                
            ),
           
            'amount' => array(
                'title' => $this->l('Order Amount', array(), 'Admin.Global'),
                'callback' => 'setOrderCurrency',
                'orderby' => false,
            ),
            
            'reference' => array(
                'title' => $this->l('Reference', array(), 'Admin.Global'),
                'orderby' => false,
            ),

            'action' => array(
                'title' => $this->l('Action', array(), 'Admin.Global'),
                'orderby' => false,
            ),

            'state' => array(
                'title' => $this->l('State', array(), 'Admin.Global'),
                'orderby' => false,
            ),
            
            'status' => array(
                'title' => $this->l('Status', array(), 'Admin.Global'),
                'orderby' => false,
                'callback' => 'renderStatus',
            ),
             
            'id_payment' => array(
                'title' => $this->l('Payment Id', array(), 'Admin.Global'),
                'orderby' => false,
            ),

            'capture_amt' => array(
                'title' => $this->l('Capture Amount', array(), 'Admin.Global'),
                'orderby' => false,
                'callback' => 'setOrderCurrency',
            ),

            'state' => array(
                'title' => $this->l('State', array(), 'Admin.Global'),
                'orderby' => false,
            ),

            'authorization_code' => array(
                'title' => $this->l('Auth Code', array(), 'Admin.Global'),
                'orderby' => false,
            ),

            'result_code' => array(
                'title' => $this->l('Result Code', array(), 'Admin.Global'),
                'orderby' => false,
            ),
            'api_type' => array(
                'title' => $this->l('API Type', array(), 'Admin.Global'),
                'orderby' => false,
                'callback' => 'renderApiType',
            ),

            'created_at' => array(
                'title' => $this->l('Date', array(), 'Admin.Global'),
                'orderby' => false,
                'type' => 'datetime',
                'filter_key' => 'a!created_at',
            ),
        );
    }

    /**
     * set order currency.
     *
     * @param array $echo
     * @param string $tr
     * @return string
     */
    public static function setOrderCurrency($echo, $tr)
    {
        $order = new Order($tr['id_order']);
        return Tools::displayPrice($echo, (int) $order->id_currency);
    }
    
    /**
     * Render List.
     *
     * @return object
     */
    public function renderList()
    {
        $this->_select = ' nid as  id_ngenius_networkinternational';
        return parent::renderList();
    }

    /**
     * Render status.
     *
     * @param string $status
     * @return string
     */
    public function renderStatus($status)
    {
        return $this->statusArr[$status];
    }

    /**
     * Render API Type.
     *
     * @param string $status
     * @return string
     */
    public function renderApiType($apiType)
    {
        return $this->apiTypeArr[$apiType];
    }
}
