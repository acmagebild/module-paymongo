define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        var config = window.checkoutConfig.payment,
            paymongo = 'paymongo';

        if(config[paymongo].isActive){
            rendererList.push(
                {
                    type: 'paymongo',
                    component: 'Magebild_Paymongo/js/view/payment/method-renderer/paymongo'
                }
            );
        }
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
