{extends file='parent:frontend/checkout/confirm.tpl'}

{block name='frontend_checkout_confirm_left_payment_method'}
    {$smarty.block.parent}
    {if $sUserData.additional.payment.name == 'payment_payrexx_apple_pay' || $sUserData.additional.payment.name == 'payment_payrexx_google-pay'}
        <p class="payrexx-payment--method-warning" style="display:none; color: #d9400b;">
            <strong>{$sUserData.additional.payment.description} not supported on this device</strong>
        </p>
    {/if}
{/block}
