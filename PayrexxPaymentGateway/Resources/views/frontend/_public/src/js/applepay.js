(function ($) {
    'use strict';

    $(document).ready(function() {
        if ((window.ApplePaySession && ApplePaySession.canMakePayments()) !== true) {
            $(".payment-mean-payment-payrexx-apple-pay-label").parent().parent('.payment--method').hide();
            console.warn("Payrexx Apple Pay is not supported on this device/browser");
        }
    });
}(jQuery));
