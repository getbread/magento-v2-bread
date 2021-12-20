require(
    [
        'jquery',
        'Magento_Ui/js/modal/alert',
        'domReady!'
    ],
    function ($, alert) {
        var apiMode = $("#payment_us_breadcheckout_api_mode").val(),
            apiVersion = $("#payment_us_breadcheckout_api_version").val(),
            prodKey = "#payment_us_breadcheckout_api_public_key",
            prodSecret = "#payment_us_breadcheckout_api_secret_key",
            sandKey = "#payment_us_breadcheckout_api_sandbox_public_key",
            sandSecret = "#payment_us_breadcheckout_api_sandbox_secret_key",
            //Bread 2.0
            breadProdKey = "#payment_us_breadcheckout_bread_api_public_key",
            breadProdSecret = "#payment_us_breadcheckout_bread_api_secret_key",
            breadSandKey = "#payment_us_breadcheckout_bread_api_sandbox_public_key",
            breadSandSecret = "#payment_us_breadcheckout_bread_api_sandbox_secret_key",
            tenant = $("#payment_us_breadcheckout_tenant").val(),
            validationUrl = window.location.origin + "/admin/breadadmin/bread/validateCredentials";

        var selector = apiVersion === 'bread_2' ?
            [breadProdKey, breadProdSecret, breadSandKey, breadSandSecret, tenant].join(", ") :
            [prodKey, prodSecret, sandKey, sandSecret].join(", ");


        $(selector).on(
            "input", function () {
                var tenant = "";
                var key = "";
                var secret = "";
                if (apiVersion === 'bread_2') {
                    key = apiMode === "1" ? $(breadProdKey).val() : $(breadSandKey).val();
                    secret = apiMode === "1" ? $(breadProdSecret).val() : $(breadSandSecret).val();
                } else {
                    key = apiMode === "1" ? $(prodKey).val() : $(sandKey).val();
                    secret = apiMode === "1" ? $(prodSecret).val() : $(sandSecret).val();
                }

                var secretKeyEntered = secret.indexOf('*') === -1;

                if (secretKeyEntered) {
                    $.ajax(
                        validationUrl, {
                        type: "post",
                        data: {
                            form_key: window.FORM_KEY,
                            apiVersion: apiVersion,
                            apiMode: apiMode,
                            pubKey: key,
                            secKey: secret,
                            tenant: tenant
                        }
                    }
                    ).done(
                        function (response) {
                            if (response === false) {
                                alert(
                                    {
                                        title: 'Error with api credentials validation',
                                        content: 'Please confirm that you are using correct key values',
                                        actions: {
                                            always: function () { }
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
