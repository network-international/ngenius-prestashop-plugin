
{extends "$layout"}

{block name="content"}
  	<section id="content-hook_order_confirmation" class="card">
      	<div class="card-block">
        	<div class="row">
          		<div class="col-md-12">
          			<div class=" alert alert alert-danger"> Your order has Declined.</div>
            		{l s='For any questions or for further information, please contact our' mod='ngenius'}
        			<a href="{$link->getPageLink('contact', true)|escape:'htmlall':'UTF-8'}"> <b><u>{l s='customer support' mod='ngenius'}</u></b></a>
          		</div>
        	</div>
      	</div>
    </section>
{/block}
