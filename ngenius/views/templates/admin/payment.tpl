
{if $authorizedOrder }
    <div id="formAddPaymentPanel" class="card mt-2 d-print-none panel col-sm-12">
        <div class="panel-heading">
            <i class="icon-money"></i><h3> {$moduleDisplayName|escape:'htmlall':'UTF-8'}</h3><span class="badge"></span>
        </div>

            <div class="well col-sm-12">
                <div class="col-sm-6">
                    <div class="panel-heading">Capture / Void<span class="badge"></span></div>
                    Authorized Amount : {$ngeniusOrder['currency']|escape:'htmlall':'UTF-8'} {number_format($ngeniusOrder['amount'], 2)|escape:'htmlall':'UTF-8'}<br/>  <br>
                    <div class="col-sm-6">
                        <form class="container-command-top-spacing" action="{$formAction|escape:'htmlall':'UTF-8'}" method="post")>
                            <button type="submit" name="fullyCaptureNgenius" class="btn btn-primary">
                                <i class="icon-check"></i> Full Capture
                            </button>
                        </form>
                    </div>
                    <div class="col-sm-6">
                        <form class="container-command-top-spacing" action="{$formAction|escape:'htmlall':'UTF-8'}" method="post")>
                            <button type="submit" name="voidNgenius" class="btn btn-primary">
                                <i class="icon-check"></i> Void
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="panel-heading"> Payment Information <span class="badge"></span></div>
                    <span>Payment Reference :  {$ngeniusOrder['reference']|escape:'htmlall':'UTF-8'}</span></br></br>
                    <span>Payment Id : {$ngeniusOrder['id_payment']|escape:'htmlall':'UTF-8'}</span></br></br>
                </div>
            </div>

    </div>
{else}

<div id="formAddPaymentPanel" class="card mt-2 d-print-none panel col-sm-12">
    <div class="panel-heading">
        <i class="icon-money"></i><h3> {$moduleDisplayName|escape:'htmlall':'UTF-8'}</h3><span class="badge"></span>
    </div>
    <div class="well col-sm-12">
        <div class="col-sm-4">
            <div class="panel-heading"><b>Refund<span class="badge"></b></span></div>
            <form class="container-command-top-spacing" action="{$formAction|escape:'htmlall':'UTF-8'}" method="post")>
                Captured Amount : {$ngeniusOrder['currency']|escape:'htmlall':'UTF-8'} {number_format($ngeniusOrder['capture_amt'], 2)|escape:'htmlall':'UTF-8'}<br/>  <br>
                <div class="input-group">Amount to refund: <input type="number" name="refundAmount" step="any" required  min="0.01">{$ngeniusOrder['currency']|escape:'htmlall':'UTF-8'}</div><br>
                <button type="submit" name="partialRefundNgenius" class="btn btn-primary">
                    <i class="icon-check"></i> Refund
                </button>
            </form>
        </div>


            <div class="col-sm-8">
                <div class="panel-heading"> Payment Information <span class="badge"></span></div>
                <div class="col-sm-8">
                    <span>Payment Reference : {$ngeniusOrder['reference']|escape:'htmlall':'UTF-8'}</span></br></br>
                    <span>Payment Id        : {$ngeniusOrder['id_payment']|escape:'htmlall':'UTF-8'}</span></br></br>
                    <span>Capture Id       : {$ngeniusOrder['id_capture']|escape:'htmlall':'UTF-8'}</span></br></br>
                </div>
                <div class="col-sm-4 ">
                    <span>Total Paid        : {$ngeniusOrder['currency']|escape:'htmlall':'UTF-8'|escape:'htmlall':'UTF-8'} {number_format($ngeniusOrder['amount'], 2)|escape:'htmlall':'UTF-8'}</span></br></br>
                    <span>Total Capture     : {$ngeniusOrder['currency']|escape:'htmlall':'UTF-8'} {number_format($ngeniusOrder['capture_amt'], 2)|escape:'htmlall':'UTF-8'}</span></br></br>
                    <span>Total Refunded    : {$ngeniusOrder['currency']|escape:'htmlall':'UTF-8'} {number_format($ngeniusOrder['refunded_amt'], 2)|escape:'htmlall':'UTF-8'}</span></br></br>
                </div>
            </div>

    </div>
</div>

{/if}
