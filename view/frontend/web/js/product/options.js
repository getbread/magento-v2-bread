define(
    [
    'jquery',
    'jquery/validate',
    'mage/validation',
    'domReady!'
    ], function ($) {

        'use strict';

        return function (config) {
            var optionsData = config.optionsData;
            var productType = config.productType;

            /**
             * Returns SKU with custom options appended;
             * has side effect of updating the product price
             */
            document.getSkuForOptions = function (selectedOptions) {

                var price;
                var skuSuffix = '';
                var selected = (typeof spConfig !== 'undefined') ? spConfig.getIdOfSelectedProduct(selectedOptions) : null;

                if(productType === 'configurable' && selected !== null) {
                    price = document.round(spConfig.optionPrices[selected].finalPrice.amount * 100);
                } else {
                    price = document.round(document.basePrice);
                }

                var skipIds = [];

                $('.product-custom-option').each(
                    function () {
                        var optionId;

                        if($(this).attr('type') !== 'file') {
                            optionId = $(this).attr('name').match(/\[(\d+)\]/)[1];
                        } else {
                            optionId = $(this).attr('name').match(/\_(\d+)\_/)[1];
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

                                    if(skipIds.indexOf(optionId) === -1) {

                                        var dateSelected = true;
                                        $('[id^=options_' + optionId + '_]').each(
                                            function () {
                                                if($(this).val() === '') {
                                                    dateSelected = false;
                                                }
                                            }
                                        );

                                        if(dateSelected) {
                                            price += document.round(configOptions.price);
                                        }

                                        skipIds[optionId] = optionId;
                                    }

                                } else if (val) {
                                    skuSuffix += '***' + identifier + '===' + val;
                                    price += document.round(configOptions[val].price);
                                }
                                break;
                            case 'text':
                                if(val !== "") {
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
                    }
                );

                document.priceWithOptions = price;
                return skuSuffix;
            };

            document.round = function (value) {
                return parseInt(
                    Number(Math.round(parseFloat(value) + 'e' + 2) + 'e-' + 2)
                );
            };

            document.basePrice = document.previousPrice;
            document.customOptions = document.getSkuForOptions();

            /**
             * Validate the add to cart form when inputs are updated
             */
            $('#product_addtocart_form').on(
                'change', function () {

                    if(productType === 'configurable') {

                        var selectedOptions = {};
                        $('[name^="super_attribute"]').each(
                            function () {
                                var attributeId = $(this).attr('name').match(/\[(\d+)\]/)[1];
                                selectedOptions[attributeId] = $(this).val();
                            }
                        );

                    }

                    document.customOptions = document.getSkuForOptions(selectedOptions);
                    document.resetPriceAndSku(true);
                    document.splitPayResetPriceAndSku(true);
                }
            );
        };
    }
);
