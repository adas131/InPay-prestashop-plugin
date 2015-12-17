
<p>{l s='Your order on %s is complete.' sprintf=$shop_name mod='inpay'}
		<br /><br />
		{l s='You have chosen the Inpay Bitcoin Payment Gateway method.' mod='inpay'}
		<br /><br /><span class="bold">{l s='Your order will be sent very soon.' mod='inpay'}</span>
		<br /><br />{l s='For any questions or for further information, please contact our' mod='inpay'} <a href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='inpay'}</a>.
</p>