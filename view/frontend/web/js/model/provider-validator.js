define(
    [
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/checkout-data'
    ],
    function ($t, messageList, checkout) {
        'use strict';
        return {
            validate: function () {
               var config = window.checkoutConfig.payment;
               var isValid = true;

                if(typeof config['paymongo'] == 'undefined'){
                    return isValid;
                }
                if(typeof config['paymongo']['provider'] == 'undefined'){
                    messageList.addErrorMessage({ message: $t('Please choose a Paymongo provider') });
                    isValid = false;
                }
                return isValid;
            }
        }
    }
);
