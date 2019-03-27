define([
    'jquery',
    'jquery/validate',
    'mage/validation',
    'domReady!'
], function($) {

    'use strict';

    return function(config){
        var optionsData = config.optionsData;
        var productType = config.productType;

        $('.button-prevent').show();

        /**
         * Returns SKU with custom options appended;
         * has side effect of updating the product price
         */
        document.getSkuForOptions = function(selectedOptions) {

            var skuSuffix = '';
            var selected = (typeof spConfig !== 'undefined') ? spConfig.getIdOfSelectedProduct(selectedOptions) : null;

            if(productType === 'configurable' && selected !== null){
                var price = document.round(spConfig.optionPrices[selected].finalPrice.amount * 100);
            } else {
                var price = document.round(document.basePrice);
            }

            $('.product-custom-option').each(function(u) {

                if($(this).attr('type') !== 'file'){
                    var optionId = $(this).attr('name').match(/\[(\d+)\]/)[1];
                } else {
                    var optionId = $(this).attr('name').match(/\_(\d+)\_/)[1];
                }

                if (optionsData[optionId]) {
                    var configOptions = optionsData[optionId];

                    var val = $(this).val();
                    if (val) {
                        var identifier = 'id~' + optionId;
                    }

                    var elType = $(this).attr('type');
                    if (typeof elType == 'undefined') {
                        elType = $(this).prop('tagName').toLowerCase();
                    }

                    switch (elType) {
                        case 'checkbox':
                        case 'radio':
                            if ($(this).is(':checked') && val) {
                                skuSuffix += '***' + identifier + '===' + val;
                                price += document.round(configOptions[val].price);
                            }
                            break;
                        case 'select':
                            if (val && $(this).hasClass('datetime-picker')) {
                                var role = $(this).data('calendar-role');
                                skuSuffix += '***' + identifier + '===' + role + '===' + val;
                                price += document.round(configOptions.price)
                            } else if (val) {
                                skuSuffix += '***' + identifier + '===' + val;
                                price += document.round(configOptions[val].price);
                            }
                            break;
                        case 'text':
                            if(val !== ""){
                                skuSuffix += '***' + identifier + '===' + val;
                                price += document.round(configOptions.price);
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
            $.mage.validation({errorPlacement:function () {},highlight:function () {}}, $(this));

            if(productType === 'configurable'){

                var selectedOptions = {};
                var validSuperAttribute = '';
                $('[name^="super_attribute"]').each(function() {
                    var attributeId = $(this).attr('name').match(/\[(\d+)\]/)[1];
                    selectedOptions[attributeId] = $(this).val();
                    validSuperAttribute = validSuperAttribute + Boolean(selectedOptions[attributeId]);
                });
                validSuperAttribute =  Boolean(validSuperAttribute && validSuperAttribute.search("false") === -1);

                var isValid = $(this).valid() && validSuperAttribute;

            }else{
                var isValid = $(this).valid();
            }

            if (isValid) {
                document.customOptions = document.getSkuForOptions(selectedOptions);
                $('.button-prevent').hide();
                document.resetPriceAndSku(true);
            } else {
                $('.button-prevent').show();
            }
        });
    };
});