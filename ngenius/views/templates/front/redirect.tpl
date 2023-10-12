
<div>
	<h3>{l s='Redirect your customer' mod='ngenius'}:</h3>
	<ul class="alert alert-info">
			<li>{l s='This action should be used to redirect your customer to the website of your payment processor' mod='ngenius'}.</li>
	</ul>

	<div class="alert alert-warning">
		{l s='You can redirect your customer with an error message' mod='ngenius'}:
		<a href="{$link->getModuleLink('ngenius', 'redirect', ['action' => 'error'], true)|escape:'htmlall':'UTF-8'}" title="{l s='Look at the error' mod='ngenius'}">
			<strong>{l s='Look at the error message' mod='ngenius'}</strong>
		</a>
	</div>

	<div class="alert alert-success">
		{l s='You can also redirect your customer to the confirmation page' mod='ngenius'}:
		<a href="{$link->getModuleLink('ngenius', 'confirmation', ['cart_id' => $cart_id, 'secure_key' => $secure_key], true)|escape:'htmlall':'UTF-8'}" title="{l s='Confirm' mod='ngenius'}">
			<strong>{l s='Go to the confirmation page' mod='ngenius'}</strong>
		</a>
	</div>
</div>
