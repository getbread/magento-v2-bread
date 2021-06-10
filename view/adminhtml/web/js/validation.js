require(
    [
        'jquery',
        'Magento_Ui/js/modal/alert',
        'domReady!'
    ],
    function ($, alert) {
        var apiMode = $("#payment_us_breadcheckout_api_mode").val(),
            apiVersion = $("#payment_us_breadcheckout_api_version").val(),
            apiUrl = $("#payment_us_breadcheckout_api_url").val(),
            prodKey = "#payment_us_breadcheckout_api_public_key",
            prodSecret = "#payment_us_breadcheckout_api_secret_key",
            sandKey = "#payment_us_breadcheckout_api_sandbox_public_key",
            sandSecret = "#payment_us_breadcheckout_api_sandbox_secret_key",
            selector = [prodKey,prodSecret,sandKey,sandSecret].join(", "),
            validationUrl = window.location.origin + "/admin/breadadmin/bread/validateCredentials";
        $(selector).on(
            "input",function () {
                var key = apiMode === "1" ? $(prodKey).val() : $(sandKey).val();
                var secret = apiMode === "1" ? $(prodSecret).val() : $(sandSecret).val();
                var secretKeyEntered = secret.indexOf('*') === -1;

                if(secretKeyEntered) {
                    $.ajax(
                        validationUrl,{
                            type: "post",
                            data: {
                                form_key: window.FORM_KEY,
                                apiVersion: apiVersion,
                                apiUrl: apiUrl,
                                apiMode: apiMode,
                                pubKey: key,
                                secKey: secret
                            },
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
