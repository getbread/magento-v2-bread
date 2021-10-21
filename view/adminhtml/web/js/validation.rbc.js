require(
    [
        'jquery',
        'Magento_Ui/js/modal/alert',
        'domReady!'
    ],
    function ($, alert) {
        var apiMode = $("#payment_ca_breadcheckout_api_mode").val(),
            apiVersion = 'bread_2',    
            breadProdKey = "#payment_ca_breadcheckout_bread_api_public_key",
            breadProdSecret = "#payment_ca_breadcheckout_bread_api_secret_key",
            breadSandKey = "#payment_ca_breadcheckout_bread_api_sandbox_public_key",
            breadSandSecret = "#payment_ca_breadcheckout_bread_api_sandbox_secret_key",
            validationUrl = window.location.origin + "/admin/breadadmin/bread/validateCredentials";
    
            var selector = [breadProdKey,breadProdSecret,breadSandKey,breadSandSecret].join(", ");
                    

        $(selector).on(
            "input",function () {
                var key = "";
                var secret = "";
                key = apiMode === "1" ? $(breadProdKey).val() : $(breadSandKey).val();
                secret = apiMode === "1" ? $(breadProdSecret).val() : $(breadSandSecret).val();

                var secretKeyEntered = secret.indexOf('*') === -1;

                if(secretKeyEntered) {
                    $.ajax(
                        validationUrl,{
                            type: "post",
                            data: {
                                form_key: window.FORM_KEY,
                                apiVersion: apiVersion,
                                apiMode: apiMode,
                                pubKey: key,
                                secKey: secret
                            }
                        }
                    ).done(
                        function (response) {
                            if(response === false) {
                                alert(
                                    {
                                        title: 'Error with api credentials validation',
                                        content: 'Please confirm that you are using correct key values',
                                        actions: {
                                            always: function (){}
                                        }
                                        }
                                );
                            }
                        }
                    );
                }

            }
        );

    }
);
