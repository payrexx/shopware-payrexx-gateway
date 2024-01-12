(function ($) {
    'use strict';

    $(document).ready(function() {
        checkGooglePaySupport();
    });

    $(document).on("DOMNodeInserted", ".content--wrapper", function(e) {
        checkGooglePaySupport();
    });

    /**
     * Check the deive to support google pay.
     */
    function checkGooglePaySupport() {
        $(".payment-payrexx-google-pay-label").parent().parent('.payment--method').hide();
        try {
            const baseRequest = {
                apiVersion: 2,
                apiVersionMinor: 0
            };
            const allowedCardNetworks = ['MASTERCARD', 'VISA'];
            const allowedCardAuthMethods = ['CRYPTOGRAM_3DS'];
            const baseCardPaymentMethod = {
                type: 'CARD',
                parameters: {
                    allowedAuthMethods: allowedCardAuthMethods,
                    allowedCardNetworks: allowedCardNetworks
                }
            };

            const isReadyToPayRequest = Object.assign({}, baseRequest);
            isReadyToPayRequest.allowedPaymentMethods = [
                baseCardPaymentMethod
            ];
            const paymentsClient = new google.payments.api.PaymentsClient(
                {
                    environment: 'TEST'
                }
            );
            paymentsClient.isReadyToPay(isReadyToPayRequest).then(function(response) {
                if (response.result) {
                    $(".payment-payrexx-google-pay-label").parent().parent('.payment--method').show();
                } else {
                    $(".payrexx-payment--method-warning").show();
                }
            }).catch(function(err) {
                console.log(err);
            });
        } catch (err) {
            console.log(err);
        }
    }
}(jQuery));
