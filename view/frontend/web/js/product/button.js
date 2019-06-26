define([
    'jquery',
    'underscore'
],function ($, _) {

    return function (config) {

        $(config.buttonId).on('mouseover',function (e) {

            var radioAndCheckbox = {};
            var inputElements = [];

            $.each($('.product-options-wrapper [aria-required="true"]'),function () {

                if(typeof $(this).prop("name") === "undefined" || $(this).attr("type") === "hidden"){
                    return;
                }

                if($(this).attr("type") === "radio" || $(this).attr("type") === "checkbox"){

                    String($(this).attr("name")) in radioAndCheckbox ?
                        radioAndCheckbox[$(this).attr("name")].push($(this).is(':checked'))
                        : radioAndCheckbox[$(this).attr("name")] = [$(this).is(':checked')]
                    ;

                } else {

                    inputElements.push(Boolean($(this).val()));
                }
            });

            inputElements = _.every(inputElements);

            $.each(radioAndCheckbox,function (i,el) {
                _.contains(el,true) ? radioAndCheckbox[i] = true : radioAndCheckbox[i] = false;

            });

            radioAndCheckbox = _.every(_.values(radioAndCheckbox)) ? true : false;

            var isValid = inputElements && radioAndCheckbox;

            if(isValid){
                $('.button-prevent').hide();
            }else{
                $('.button-prevent').show().fadeOut(5000);
            }

        });
    }

});