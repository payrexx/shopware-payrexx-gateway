{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_fieldset_input_label'}
    {if ($payment_mean.name|lower == 'payment_payrexx_apple_pay') OR ($payment_mean.name|lower == 'payment_payrexx_google-pay')}
        <div class="method--label is--first">
            <label class="method--name is--strong payment-mean-{$payment_mean.name|lower|replace:"_":"-"}-label" for="payment_mean{$payment_mean.id}" id="payment_mean{$payment_mean.id}_label">{$payment_mean.description}</label>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}