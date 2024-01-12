{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_fieldset_input_label'}
    {if $payment_mean.name|lower|strstr:'payrexx'}
        <div class="method--label is--first">
            <label class="method--name is--strong {$payment_mean.name|lower|replace:"_":"-"}-label" for="payment_mean{$payment_mean.id}">
            {$payment_mean.description}
            </label>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
