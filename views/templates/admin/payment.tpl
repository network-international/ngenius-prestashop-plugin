{*
 * @author    Abzer <info@abzer.com>
 * @category  NetworkInternational
 * @package   NetworkInternational_Hsngenius
 *
*}
{if $ngeniusOrder }
    <div id="formAddPaymentPanel" class="panel col-sm-12">
        <div class="panel-heading">
            <i class="icon-money"></i> N-Genius Online Payment Gateway<span class="badge"></span>
        </div> 
        <div class="well col-sm-12">        
            <div class="col-sm-4">
                <div class="panel-heading"> Card Details<span class="badge"></span></div>
                {assign var=card value=$ngeniusOrder['card_details']|json_decode:1}                     
                Card Holder Name   : {$card['cardholderName']}<br/><br/>             
                Credit Card Number : {$card['pan']}<br/>  <br/> 
                Card Brand         : {$card['name']}<br/>  <br/>                  
            </div> 
            <div class="col-sm-8">
                <div class="col-sm-8">
                    <div class="panel-heading"> Auth Response<span class="badge"></span></div>            
                    Auth Code   : {$ngeniusOrder['authorization_code']}<br/>  <br/>
                    Result Code : {$ngeniusOrder['result_code']}<br/>  </br> 
                    Message     : {$ngeniusOrder['result_message']}<br/>  <br/>                               
                </div>  
                <div class="col-sm-4">
                    <div class="panel-heading"> Query API<span class="badge"></span></div>
                    <form class="container-command-top-spacing" action="{$formAction}" method="post")>
                        <br/>  </br> 
                        {if ($ngeniusOrder['status'] == 'NGENIUS_FAILED' && $ngeniusOrder['state'] == 'AWAIT_3DS')}
                            <i class="icon-close"></i>
                        {else}
                            <button type="submit" name="queryApi" class="btn btn-primary">
                                <i class="icon-check"></i>Get Order Status
                            </button>
                        {/if}
                    </form>
                </div>
            </div>                            
        </div> 

        {if $authorizedOrder }
            <div class="well col-sm-12">        
                <div class="col-sm-6">
                    <div class="panel-heading">Capture / Void<span class="badge"></span></div>
                    Authorized Amount : {$ngeniusOrder['currency']}{number_format($ngeniusOrder['amount'], 2)}<br/>  <br> 
                    <div class="col-sm-6">
                        <form class="container-command-top-spacing" action="{$formAction}" method="post")>
                            <button type="submit" name="fullyCaptureNgenius" class="btn btn-primary">
                                <i class="icon-check"></i> Full Capture
                            </button>
                        </form>
                    </div>
                    <div class="col-sm-6">       
                        <form class="container-command-top-spacing" action="{$formAction}" method="post")>
                            <button type="submit" name="voidNgenius" class="btn btn-primary">
                                <i class="icon-check"></i> Void
                            </button>
                        </form>
                    </div>                          
                </div>  
                <div class="col-sm-6">
                    <div class="panel-heading"> Payment Information <span class="badge"></span></div>       
                    <span>Payment Reference :  {$ngeniusOrder['reference']}</span></br></br>
                    <span>Payment Id : {$ngeniusOrder['id_payment']}</span></br></br> 
                </div>                             
            </div>
        {/if}

        {if $refundedOrder }
            <div class="well col-sm-12"> 
                <div class="col-sm-4">
                    <div class="panel-heading">Refund<span class="badge"></span></div>
                    <form class="container-command-top-spacing" action="{$formAction}" method="post")>
                        Captured Amount : {$ngeniusOrder['currency']} {number_format($ngeniusOrder['capture_amt'], 2)}<br/>  <br>                      
                         <div class="input-group">Amount to refund: <input type="number" name="refundAmount" step="any" required  min="0.01" max="{$ngeniusOrder['capture_amt']}">{$ngeniusOrder['currency']}</div><br> 
                        <button type="submit" name="partialRefundNgenius" class="btn btn-primary">
                            <i class="icon-check"></i> Refund
                            </button>
                    </form>                                
                </div>      
                <div class="col-sm-8">    
                    <div class="panel-heading"> Payment Information <span class="badge"></span></div>
                    <div class="col-sm-8">
                        <span>Payment Reference : {$ngeniusOrder['reference']}</span></br></br>
                        <span>Payment Id        : {$ngeniusOrder['id_payment']}</span></br></br>
                         <span>Capture Id       : {$ngeniusOrder['id_capture']}</span></br></br>
                    </div> 
                    <div class="col-sm-4 ">
                        <span>Total Paid        : {$ngeniusOrder['currency']} {number_format($ngeniusOrder['amount'], 2)}</span></br></br>
                        <span>Total Capture     : {$ngeniusOrder['currency']} {number_format($ngeniusOrder['capture_amt'], 2)}</span></br></br>
                        <span>Total Refunded    : {$ngeniusOrder['currency']} {number_format($totalRefunded, 2)}</span></br></br>                               
                    </div>                              
                </div> 
            </div>
        {/if}
    </div>
{/if}