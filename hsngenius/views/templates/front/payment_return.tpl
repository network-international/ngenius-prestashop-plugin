{*
 * @author    Abzer <info@abzer.com>
 * @category  NetworkInternational
 * @package   NetworkInternational_Hsngenius
 *
*}

{block name="content"}

    <script type="text/javascript" src="{$sdkUrl}"></script>    
    <link rel="stylesheet" type="text/css" href="{$psBaseUrl}/modules/hsngenius/views/css/hsngenius.css" />

    <div class="col-xs-12 col-lg-12 box box-ng clearfix" style="min-height: 340px;">                
        <div class="cart-grid-body cart-grid-body-ng col-xs-12 col-lg-8">
            <article>                
                <div>  
                    {if $savedCards }
                        <div id="saved-card" >
                            <div class="col-md-12">                              
                                <div id="token" >
                                    <div class="card-titles">
                                        <p>Your credit and debit cards</p>
                                        <div class="ct-right">
                                            <span class="name-title">Name on card</span>
                                            <span class="exp-title">Expires on</span>
                                        </div>
                                    </div> 
                                    <div id="accordion">
                                        {foreach $savedCards as $savedCard}
                                            {assign var=card value=$savedCard['saved_card']|json_decode:1}

                                                <h3 id="h3_{$savedCard['id']}">
                                                    <div class="card-type"><img src="{$psBaseUrl}/modules/hsngenius/views/images/{$card['scheme']}.png"><span>{$card['scheme']}</span></div>
                                                    <span class="card-num">{$card['maskedPan']}</span>

                                                    <div class="h-right">
                                                        <button class="delete sv-delete" id="del_{$savedCard['id']}" value="{$savedCard['id']}">
                                                        <i class="icon-trash"></i>
                                                        </button>
                                                        <div class="sc-expiry">{$card['expiry']}</div>
                                                        <div class="sc-name">{$card['cardholderName']}</div>
                                                    </div>
                                                </h3>
                                                <div id="div_acrd_{$savedCard['id']}" class="card-wrap">
                                                    <div class="card-inner">
                                                       <p class="sc-cvv">
                                                           <label>CVV</label>
                                                           <input type="password" name="cvv" id="{$savedCard['id']}" class="cvv"  maxlength="4">
                                                        </p>                                
                                                        <button id="btn_{$savedCard['id']}" class="btn btn-primary standard-checkout pay-btn pay-token" value="{$savedCard['id']}">PAY NOW</button>
                                                    </div>
                                                </div>                                        
                                        {/foreach}
                                    </div>
                                </div>   
                            </div>                            
                        </div>
                    {else}
                        {$savedCards ="''"}
                    {/if}
                    

                    <div id="card">
                        <div class="card-titles"><p>Add Debit/Credit Card</p></div>
                        <div class="col-md-12 crd-inner-wrap">
                            <div id="mount-id" style="height: 190px;"></div> 
                            <span id="ch-span">
                                <label for="chsavedcard" > 
                                    <input type="checkbox" name="chsavedcard" id="chsavedcard"  value="1"> Save this card
                                </label>
                            </span>
                            <button onclick="createSession()" class="btn btn-primary standard-checkout" 
                            id="pay-now" style="display:none; margin-left: 12px;position: relative; z-index: 9;">PAY NOW</button>
                        </div>
                    </div>

                    <br/>

                    <div id="3ds_iframe"></div>
                    <div id="loading"> 
                        <img src="{$psBaseUrl}/modules/hsngenius/views/images/loading.gif"><br/><br/>
                        <span class="pro-text">Please do not refresh the page and wait while we are processing your payment!.</span>
                    </div>
                    <div id="error"></div>

                    
                </div>
            </article>           
        </div>

        <div class="cart-grid-right col-xs-12 col-lg-4" id="nge-ord-sum">
            <div class="cart-summary-line cart-total">
                <h2 style="color: #000;">Order Summary </h2>
                <div class="nge-total-foot">
                    <b>Order Total: 
                   {$orderTotal}</b>
                </div>
            </div>            
        </div>
    </div>
    <script>
        "use strict";   
        var otpFlag = false;    
        // accordion for saved card
        $( function() {
            $( "#accordion").accordion();
        });

        $(document).ready(function(){ 
            $('#ch-span').hide();
            var savedCards  = {$savedCards};
            if (savedCards) { 
                var scCount = $(".card-wrap").length;
            }
            // customer saved card delete
            $(".sv-delete").click(function() {
                if (confirm("Are you sure you want to delete this card?")) {
                    var svid = this.value;
                    $.ajax({
                        url:"deletesavedcard",                    
                        type: "post",
                        data: { 
                            svid:svid,
                        },                                    
                        success:function(result){ 
                            $("#h3_"+result).hide();
                            $("#div_acrd_"+result).hide();
                            scCount = scCount-1;
                            if (scCount == 0) {
                                window.location.reload(true);
                            }      
                        },
                        error: function(error) {
                            console.log(error);
                       }
                    });
                }
                return false;  
            });

            if($("#saved-card").is(":visible")){
                $( ".pay-token" ).prop( "disabled", true );
            }  

            // customer cvv validation              
            $(".cvv").keyup(function() {
                var cvv = this.value;
                if ((cvv.length === 3 || cvv.length === 4) && /^\d+$/.test(cvv) ) {
                    $('#btn_'+$(this).attr('id')).prop( "disabled", false );                   
                    this.style.borderColor = "gray";
                } else {
                    $('#btn_'+$(this).attr('id')).prop( "disabled", true );
                    this.style.borderColor = "red";
                }
            });

            // customer pay by saved card
            $(".pay-token").click(function() { 
                var savedCardId = this.value;
                var cvv = $('#'+savedCardId).val();
                $.ajax({
                    url:"tokenization", 
                    type: "post",
                    dataType: 'json',
                    data: { 
                        cvv:cvv,
                        savedCardId:savedCardId,
                    },
                    beforeSend: function() { 
                        $("#card").hide(); 
                        $("#error").hide();
                        $("#token").hide();
                        $("#saved-card").hide(); 
                        $("#loading").show();
                        //$("#radio-card-div").hide();                        
                    },
                    success:function(result){                           
                        $("#loading").hide();
                        check3ds(result);
                    },
                    error: function(error) {
                        console.log(error);
                   }
                });  
            });
        });  

        var merchantRedirectUrl = '{$psBaseUrl}'+'module/hsngenius/redirect?ref=';  
        var reloadpage = true;
        
        /* Method call to mount the card input on your website */        
        window.NI.mountCardInput('mount-id', {
            style: {
                main:{
                    padding:'0px'
                },
                base: {
                    backgroundColor: '#FFFFFF'
                },
                input: {
                    borderWidth: '1px',
                    borderRadius: '5px',
                    borderStyle: 'solid',
                    backgroundColor: '#FFFFFF',
                    borderColor: '#f1f1f1',
                    color: '#000000',
                    padding: '5px'
                }
            }, // Style configuration you can pass to customize the UI
            apiKey: '{$hostedSessionApiKey}', // API Key for WEB SDK from the portal
            outletRef: '{$outletReferenceId}', // outlet reference from the portal
            onSuccess: function (data) {               
                document.getElementById("pay-now").style.display = "block";
                document.getElementById("ch-span").style.display = "block";                
                document.getElementById("loading").style.display = "none";
                document.getElementById("pay-now").disabled = true;

            },
            onFail: function (jqXHR, status, err) {
                document.getElementById("error").innerHTML = jqXHR.eventType;
            },
            onChangeValidStatus: function validateResponse(validationResponse){ 
                if (typeof(validationResponse) === 'object')
                    validateStatus(validationResponse);                
            }
        });

        // validate response
        function validateStatus(response){            
            if (response.isPanValid === true && response.isExpiryValid === true && response.isCVVValid === true && response.isNameValid === true)
               document.getElementById("pay-now").disabled = false;
            else 
                document.getElementById("pay-now").disabled = true;                     
        }

        // reload the web page
        setInterval(reloadWebPage, 5*60*1000);

        function reloadWebPage() {
            if (reloadpage === true)
                window.location.reload(true);
        }
        
        // customer pay by new card
        var sessionId; 
        function createSession() { 
            var chSavedCard = document.querySelector('#chsavedcard').checked;
            window.NI.generateSessionId().then(function (response) {                 
                if (typeof response.session_id !== 'undefined') {
                    sessionId = response.session_id;
                    $.ajax({
                        url:"payment", 
                        type: "post",
                        dataType: 'json',
                        data: { 
                            sid:sessionId,
                            chSavedCard:chSavedCard,
                        },
                        beforeSend: function() { 
                            if( document.getElementById("saved-card")){
                                document.getElementById("saved-card").style.display = "none";
                            }                             
                            document.getElementById("mount-id").style.display = "none";                            
                            document.getElementById("loading").style.display = "block";
                            document.getElementById("pay-now").style.display = "none";
                            document.getElementById("error").style.display = "none";                            
                            document.getElementById("ch-span").style.display = "none";
                            document.getElementById("card").style.display = "none";                            
                        },
                        success:function(result){ 
                            document.getElementById("loading").style.display = "none";
                            document.getElementById("error").style.display = "none"; 

                            check3ds(result);               
                        },
                        error: function(error) {
                            console.log(error);
                       }
                    });     
                } else {
                    document.getElementById("error").innerHTML = 'Invalid Card Details! Please try again';
                } 
            }).catch(function (err) { 
                document.getElementById("error").innerHTML = 'Oops. Something went wrong. Please try again later.';               
            });
        }
        
        // payment response 3ds authentication
        function check3ds(paymentResponse) {  
            if (typeof paymentResponse.orderReference !== 'undefined') { 
                if (typeof paymentResponse.state !== 'undefined' && paymentResponse.state == 'AWAIT_3DS' && otpFlag == false ) { 
                    otpFlag = true;          
                    document.getElementById("loading").style.display = "none";
                    window.NI.handlePaymentResponse(paymentResponse, {
                        mountId: '3ds_iframe',              
                    }).then(function (response) {
                        document.getElementById("loading").style.display = "block";
                        window.location.href = merchantRedirectUrl+paymentResponse.orderReference;
                    });
                } else {
                    document.getElementById("loading").style.display = "block";
                    window.location.href = merchantRedirectUrl+paymentResponse.orderReference;
                }               
            } else {
                document.getElementById("error").style.display = "block"; 
                document.getElementById("error").innerHTML = paymentResponse.error;
            }             
        }
    </script>
{/block}
