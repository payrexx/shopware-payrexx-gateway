(function ($) {
    'use strict';

    $(document).ready(function() {
        $(".payment-mean-payment-payrexx-google-pay-label").parent().parent('.payment--method').hide();
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
                    $(".payment-mean-payment-payrexx-google-pay-label").parent().parent('.payment--method').show();
                } else {
                    showConsoleWarning();
                }
            }).catch(function(err) {
                showConsoleWarning();
                console.log(err);
            });
        } catch (err) {
            showConsoleWarning();
            console.log(err);
        }

        function showConsoleWarning() {
            console.warn("Payrexx Google Pay is not supported on this device/browser");
        }
    });
}(jQuery));
