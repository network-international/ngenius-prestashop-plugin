{*
 * @author    Abzer <info@abzer.com>
 * @category  NetworkInternational
 * @package   NetworkInternational_Hsngenius
 *
*}

{block name="content"}

    <div class=" alert alert alert-danger"> YOUR ORDER IS FAILED!.</div>
	<div class="box">            
        {l s='There is a problem with payment module ' mod='hsngenius'}: <b>{$module}</b>.<br/><br/>
    
        {l s='For any questions or for further information, please contact our' mod='hsngenius'} 
        <a href="{$link->getPageLink('contact', true)}"> <b><u>{l s='customer support' mod='hsngenius'}</u></b></a>        
    </div>

{/block}
