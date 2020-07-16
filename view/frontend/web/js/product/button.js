define(
    [
    'jquery',
    'underscore'
    ],function ($, _) {

        return function (config) {

            $(config.buttonId).on(
                'mouseenter',function (e) {

                    var radioAndCheckboxElements = {};
                    var inputElements = [];

                    $.each(
                        $(config.requiredOptionsSelector),function () {

                            /**
                            * skip swatch clearfix elements and hidden elements 
                            */
                            if(typeof $(this).prop("name") === "undefined" || $(this).attr("type") === "hidden") {
                                return;
                            }

                            if($(this).attr("type") === "radio" || $(this).attr("type") === "checkbox") {

                                if(String($(this).attr("name")) in radioAndCheckboxElements) {
                                    radioAndCheckboxElements[$(this).attr("name")].push($(this).is(':checked'))
                                } else {
                                    radioAndCheckboxElements[$(this).attr("name")] = [$(this).is(':checked')]
                                }

                            } else {

                                inputElements.push(Boolean($(this).val()));
                            }
                        }
                    );

                    var areInputElementsSet = _.every(inputElements);

                    $.each(
                        radioAndCheckboxElements,function (name,valueSet) {
                            _.contains(valueSet,true) ? radioAndCheckboxElements[name] = true : radioAndCheckboxElements[name] = false;
                        }
                    );

                    var areRadioAndCheckboxElementsSet = _.every(_.values(radioAndCheckboxElements));

                    var isValid = areInputElementsSet && areRadioAndCheckboxElementsSet;

                    if(isValid) {
                        $('#button-prevent').hide();
                    }else{
                        $('#button-prevent').show();
                    }

                }
            );
        }

    }
);
