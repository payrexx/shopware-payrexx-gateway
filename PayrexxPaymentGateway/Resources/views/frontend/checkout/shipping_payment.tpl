{extends file='parent:frontend/checkout/shipping_payment.tpl'}

{block name='frontend_index_content'}
    {$smarty.block.parent}
    {if $applePayActive}
        <script async src="{link file='frontend/_public/src/js/applepay.js' fullPath}"></script>
    {/if}
    {if $googlePayActive}
        <script async src="https://pay.google.com/gp/p/js/pay.js"></script>
        <script async src="{link file='frontend/_public/src/js/googlepay.js' fullPath}"></script>
    </script>
    {/if}
{/block}