define(['jquery', 'jquery/validate', 'mage/validation', 'domReady!'], function($) {

    'use strict';

    return function(config){

        var optionsData = config.optionsData;
        /**
         * Returns SKU with custom options appended;
         * has side effect of updating the product price
         */
        document.getSkuForOptions = function() {
            var skuSuffix = '';
            var price = document.round(document.basePrice);

            $('.product-custom-option').each(function(u) {
                var optionId = $(this).attr('name').match(/\[(\d+)\]/)[1];

                if (optionsData[optionId]) {
                    var configOptions = optionsData[optionId];

                    var val = $(this).val();
                    if (val) {
                        var sku = (configOptions[val]) ?
                            configOptions[val].sku : configOptions.sku;
                        var identifier = (sku === null) ?
                            'id~' + optionId : sku;
                    }

                    var elType = $(this).attr('type');
                    if (typeof elType == 'undefined') {
                        elType = $(this).prop('tagName').toLowerCase();
                    }

                    switch (elType) {
                        case 'checkbox':
                        case 'radio':
                            if ($(this).is(':checked') && val) {
                                skuSuffix += '***' + identifier;
                                price += document.round(configOptions[val].price);
                            }
                            break;
                        case 'select':
                            if (val && $(this).hasClass('datetime-picker')) {
                                var role = $(this).data('calendar-role');
                                skuSuffix += '***' + identifier + '===' + role + '===' + val;
                                price += document.round(configOptions.price)
                            } else if (val) {
                                skuSuffix += '***' + identifier;
                                price += document.round(configOptions[val].price);
                            }
                            break;
                        default:
                            if (val) {
                                skuSuffix += '***' + identifier + '===' + val;
                                price += document.round(configOptions[val].price);
                            }
                    }
                }
            });

            document.priceWithOptions = price;
            return skuSuffix;
        };

        document.round = function(value) {
            return parseInt(
                Number(Math.round(parseFloat(value) + 'e' + 2) + 'e-' + 2)
            );
        };


        document.basePrice = document.previousPrice;
        document.customOptions = document.getSkuForOptions();

        /**
         * Validate the add to cart form when inputs are updated
         */
        $('#product_addtocart_form').on('change', function() {
            $.mage.validation({
                errorPlacement: function() {} // Hides default error labels
            }, this);

            if ($(this).valid()) {
                document.customOptions = document.getSkuForOptions();
                $('.button-prevent').hide();
                document.resetPriceAndSku(true);
            } else {
                $('.mage-error:not(.product-custom-option)', this).hide(); // Hides Magento error labels
                $('.button-prevent').show();
            }
        });
    };
});