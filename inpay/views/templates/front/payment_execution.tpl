{if $error != ''}
    {$error}
    <br/>
    {l s='Please contact administrator or try again.' mod='inpay'}
    <br/>
    {l s='Thank you.' mod='inpay'}
{else}
    {l s='Redirecting to InPay invoice, please wait...' mod='inpay'}
    <form action="{$redirect_url}" method="post" id="inpay_form">
    </form>
    <script type="text/javascript">
    {literal}
        document.getElementById("inpay_form").submit();        
    {/literal}
    </script>
{/if}