var timeout;
var radio_control = 0;

function timedCount() {
    jQuery(function($) {
        timeout = setTimeout(function() {
            var selected_value = $(".wc-block-components-shipping-rates-control__package input[name=radio-control-0]:checked").val();
            
            if ($(".wp-block-woocommerce-checkout-fields-block")[0]) {
                $.ajax({
                    url: '/wp-admin/admin-ajax.php',
                    type: 'POST',
                    data: {
                        action: 'load_additional_block'
                    },
                    success: function(response) {
                        $('body .wc-block-components-shipping-rates-control__package').append(response);
                    },
                    error: function(error) {
                        console.error('Error loading additional block:', error);
                    },
                });

                var selected_value2 = $('.wc-block-components-radio-control__input:checked').val();
                var delivery_method = selected_value2.split(":");
                var country_code = $('#billing-country').val();

                if (delivery_method[0] == 'dpd_parcels' || delivery_method[0] == 'dpd_sameday_parcels') {
                    $.ajax({
                        url: '/wp-admin/admin-ajax.php',
                        type: 'POST',
                        data: {
                            action: 'dpd_checkout_get_pickup_points_blocks',
                            selected_value: selected_value2,
                            country_code: country_code
                        },
                        dataType: 'json',
                        success: function(points) {
                            var pointsNew = points.all;

                            $('#dpd-wc-pickup-point-shipping-block').show();
                            $('#dpd-wc-pickup-point-shipping-select-block').select2();
                            $('#mp-wc-pickup-point-shipping-select-block').html("");

                            var items = '';

                            $.each(points.all, function(key, value) {
                                items += '<option value=' + value.id + '>' + value.text + '</option>';
                            });

                            $('#dpd-wc-pickup-point-shipping-select-block').html(items);
                        },
                        error: function(error) {
                            console.error('Error loading pickup points:', error);
                        },
                    });
                } else {
                    $('#dpd-wc-pickup-point-shipping-block').hide();
                }
            }
        }, 1500);
    });
}

function stopCount() {
    clearTimeout(timeout);
}

(function($) {
    $(document).on("click", "ul.components-form-token-field__suggestions-list li", function(e) {
        $('#dpd-wc-pickup-point-shipping-block').each(function(i, elem) {
            $(elem).remove();
        });
        jQuery('body').trigger('update_checkout');

        setTimeout(function() {
            $.ajax({
                url: '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'load_additional_block'
                },
                success: function(response) {
                    $('body .wc-block-components-shipping-rates-control').append(response);
                },
                error: function(error) {
                    console.error('Error loading additional block:', error);
                },
            });

            $('.wc-block-components-shipping-rates-control__package .wc-block-components-radio-control__input').on('click', function(e) {
                var selected_value2 = $('.wc-block-components-shipping-rates-control__package .wc-block-components-radio-control__input:checked').val();
                var delivery_method = selected_value2.split(":");

                if (delivery_method[0] == 'dpd_parcels' || delivery_method[0] == 'dpd_sameday_parcels') {
                    $.ajax({
                        url: '/wp-admin/admin-ajax.php',
                        type: 'POST',
                        data: {
                            action: 'dpd_checkout_get_pickup_points_blocks',
                            selected_value: selected_value2
                        },
                        dataType: 'json',
                        success: function(points) {
                            var pointsNew = points.all;

                            $('#dpd-wc-pickup-point-shipping-block').show();
                            $('#dpd-wc-pickup-point-shipping-select-block').select2();
                            $('#mp-wc-pickup-point-shipping-select-block').html("");

                            var items = '';

                            $.each(points.all, function(key, value) {
                                items += '<option value=' + value.id + '>' + value.text + '</option>';
                            });

                            $('#dpd-wc-pickup-point-shipping-select-block').html(items);
                        },
                        error: function(error) {
                            console.error('Error loading pickup points:', error);
                        },
                    });
                } else {
                    $('#dpd-wc-pickup-point-shipping-block').hide();
                }
            });
        }, 3000);

        setTimeout(function() {
            $('.wc-block-components-shipping-rates-control__package .wc-block-components-radio-control__input:checked').click();
        }, 3500);
    });

    $(document).ready(function() {
        setTimeout(function() {
            $("#shipping-method").on("click", ".wc-block-components-button", function() {
                if (!$(this).hasClass("wc-block-checkout__shipping-method-option--selected")) {
                    $(".wc-block-checkout__shipping-method-option--selected").removeClass("wc-block-checkout__shipping-method-option--selected");
                    $(this).addClass("wc-block-checkout__shipping-method-option--selected");
                    timedCount();
                }
            });

            $('.wc-block-components-radio-control__input').click(function() {
                timedCount();
            });

            $(document).on('click', '.wc-block-components-radio-control__input', function() {
                timedCount();
            });

            $('#billing-country').on('change', function() {
                timedCount();
            });
        }, 700);

        setTimeout(function() {
            if ($(".wp-block-woocommerce-checkout-fields-block")[0]) {
                $('#shipping-method-1').on('click', function() {
                    radio_control += 2;
                    timedCount();
                });

                $('#shipping-method-2').on('click', function() {
                    stopCount();
                });
            }
        }, 1100);

        $(document.body).on("change", "#dpd-wc-pickup-point-shipping-select-block", function() {
            var value = this.value;

            $.ajax({
                url: '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'dpd_store_pickup_selection',
                    value: value
                },
                dataType: 'json',
                success: function(response) {},
                error: function(error) {
                    console.error('Error storing pickup selection:', error);
                },
            });
        });

        timedCount();

        if ($(".wp-block-woocommerce-checkout-fields-block")[0]) {
            timedCount();
        }
    });
}(jQuery));