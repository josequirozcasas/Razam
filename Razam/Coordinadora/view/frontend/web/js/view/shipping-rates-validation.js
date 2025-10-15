/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-rates-validation-rules',
    'Razam_Coordinadora/js/model/shipping-rates-validator',
    'Razam_Coordinadora/js/model/shipping-rates-validator-rules'
], function (
    uiComponent,
    defaultShippingRatesValidator,
    defaultShippingRatesValidationRules,
    coordinadoraShippingRatesValidator,
    coordinadoraShippingRatesValidationRules
) {
    'use strict';

    defaultShippingRatesValidator.registerValidator('coordinadora', coordinadoraShippingRatesValidator);
    defaultShippingRatesValidationRules.registerValidator('coordinadora', coordinadoraShippingRatesValidationRules);

    return uiComponent;
});
