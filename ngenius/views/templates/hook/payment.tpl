
<div class="row">
	<div class="col-xs-12 col-md-6">
		<p class="payment_module" id="ngenius_payment_button">
			{if $cart->getOrderTotal() < 2}
				<a href="">
					<img src="{$domain|cat:$payment_button|escape:'html':'UTF-8'}" alt="{l s='Pay with my payment module' mod='ngenius'}" />
					{l s='Minimum amount required in order to pay with my payment module:' mod='ngenius'} {convertPrice price=2}
				</a>
			{else}
				<a href="{$link->getModuleLink('ngenius', 'redirect', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with my payment module' mod='ngenius'}">
					<img src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/network_logo.png" alt="{l s='Pay with my payment module' mod='ngenius'}" width="100px" height="auto" />
					{l s='Pay with my payment module' mod='ngenius'}
				</a>
			{/if}
		</p>
	</div>
</div>
