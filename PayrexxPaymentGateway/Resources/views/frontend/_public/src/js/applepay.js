(function ($) {
    'use strict';

    $(document).ready(function() {
        checkApplePaySupport();
    });

    $(document).on("DOMNodeInserted", function(e) {
        checkApplePaySupport();
    });

    /**
     * Check the deive to support apple pay.
     */
    function checkApplePaySupport() {
       if ((window.ApplePaySession && ApplePaySession.canMakePayments()) !== true) {
            $(".payment-payrexx-apple-pay-label").parent().parent('.payment--method').hide();
            $(".payrexx-payment--method-warning").show();
        }
    }
}(jQuery));
