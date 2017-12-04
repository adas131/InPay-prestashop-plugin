{capture name=path}{l s='Payment'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h1>{l s='Payment Error' mod='tranzila'}</h1>
<p>
{l s='There is an error in your transaction' mod='tranzila'}.

<br/><br/>{l s='Please' mod='tranzila'} <a href="index.php?controller=order&step=1">{l s='click here' mod='tranzila'}</a> {l s='to return to checkout page' mod='tranzila'}.
<br /><br />{l s='For any questions or for further information, please contact our' mod='tranzila'} <a href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='tranzila'}</a>.
</p>