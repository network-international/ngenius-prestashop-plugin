
{if (isset($status) == true) && ($status == 'ok')}
<h3>{l s='Your order on %s is complete.' sprintf=$shop_name mod='ngenius'}</h3>
<p>
	<br />- {l s='Amount' mod='ngenius'} : <span class="price"><strong>{$total|escape:'htmlall':'UTF-8'}</strong></span>
	<br />- {l s='Reference' mod='ngenius'} : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span>
	<br /><br />{l s='An email has been sent with this information.' mod='ngenius'}
	<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='ngenius'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='ngenius'}</a>
</p>
{else}
<h3>{l s='Your order on %s has not been accepted.' sprintf=$shop_name mod='ngenius'}</h3>
<p>
	<br />- {l s='Reference' mod='ngenius'} <span class="reference"> <strong>{$reference|escape:'html':'UTF-8'}</strong></span>
	<br /><br />{l s='Please, try to order again.' mod='ngenius'}
	<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='ngenius'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='ngenius'}</a>
</p>
{/if}
<hr />
