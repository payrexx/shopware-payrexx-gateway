(function ($) {
    'use strict';

var deviceSupported = false;
$(document).ready(function() {
    deviceSupported = checkDeviceSupport();
});
$(document).on("DOMNodeInserted", function(e) {
    displayApplePay();
});

function checkDeviceSupport() {
    alert('check apple pay');
    if ((window.ApplePaySession && ApplePaySession.canMakePayments()) !== true) {
        console.warn("Payrexx Apple Pay is not supported on this device/browser");
        return false;
    }
    return true;
}

function displayApplePay() {
    if (deviceSupported) { 
        return; 
    }
    $(".payment-mean-payment-payrexx-apple-pay-label").parent().parent('.payment--method').remove();
}
}(jQuery));