{*
 * @author    Abzer <info@abzer.com>
 * @category  NetworkInternational
 * @package   NetworkInternational_Hsngenius
 *
*}

{block name="content"}

    <div class=" alert alert-success"> YOUR ORDER IS CONFIRMED!.</div>
    <div class="box">
        <div class="table_block table-responsive">
            <p>
            {l s='You have chosen the ' mod='hsngenius'} <b>{$module}</b> {l s='method.' mod='hsngenius'}<br /><br />        
            {l s='For any questions or for further information, please contact our' mod='hsngenius'} <a href="{$link->getPageLink('contact', true)}"><b>{l s='customer support' mod='hsngenius'}</b></a>.
            </p>
        </div>
    </div>
    
{/block}
