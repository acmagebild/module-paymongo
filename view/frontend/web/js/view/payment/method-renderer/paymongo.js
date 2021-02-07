define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Customer/js/customer-data'
    ],
    function ($, Component, quote, setPaymentMethodAction,customerData) {
        'use strict';
        var config = window.checkoutConfig.payment;

        return Component.extend({
            defaults: {
                template: 'Magebild_Paymongo/payment/form',
                subpayment: null
            },
            getCode: function() {
                return 'paymongo';
            },
            getEwallets: function(){
                if(typeof config['paymongo'] == 'undefined')
                    return [];
                return config['paymongo'].ewallets;
            },
            getData: function () {
                var data = {
                    method: this.getCode(),
                    additional_data: {
                        provider: config['paymongo']['provider']
                    }
                };
                return data;
            },
            selectProvider: function(){
                //Reset
                $('.paymongo-ewallets-container .ewallet').each(function(idx, elem){
                    $(elem).removeClass('selected');
                });
                $('.ewallet.paymongo_' + this).addClass('selected');
                config['paymongo']['provider'] = this;
            },
            continueToPaymonggo: function(){
                //Validators
                this.selectPaymentMethod();

                setPaymentMethodAction(this.messageContainer, quote.paymentMethod()).done(
                    function(){
                        customerData.invalidate(['cart']);
                        $.mage.redirect(
                            config['paymongo'].redirectUrl
                        );
                    }
                )

                return false;
            }
        });
    }
);
