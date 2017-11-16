{*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
 * @author    luca.ioffredo93@gmail.com
 * @copyright 2017 luca.ioffredo93@gmail.com 
 * @license   luca.ioffredo93@gmail.com
 * @category  PrestaShop Module 
*}

<h3>{l s='Coupon Campaign' mod='createcoupon'}: {$campaing_cart_rules.name|escape:'htmlall':'UTF-8'}</h3>
<table style="width: 100%;" cellspacing="0">
{foreach from=$cart_rule item=code}
    <tr>
        <td style="width: 100%; text-align: left; vertical-align: middle;">
            <p>{$campaing_cart_rules.name|escape:'htmlall':'UTF-8'} - {l s='Code' mod='createcoupon'}: {$code|escape:'htmlall':'UTF-8'} - {l s='Expires' mod='createcoupon'}: {$campaing_cart_rules.date_to|truncate:13:''|escape:'html':'UTF-8'}</p>
        </td>
    </tr>
{foreachelse}
    <tr>
        <td style="width: 100%; text-align: center;">
            {l s='No items were found in the search' mod='createcoupon'}
        </td>
    </tr>    
{/foreach}
</table> 