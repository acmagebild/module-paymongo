/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magebild_Paymongo/js/model/provider-validator'
    ],
    function (Component, additionalValidators, providerValidator) {
        'use strict';
        additionalValidators.registerValidator(providerValidator);
        return Component.extend({});
    }
);
