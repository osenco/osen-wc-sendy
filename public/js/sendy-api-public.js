let script = document.createElement('script');
script.src = 'https://maps.googleapis.com/maps/api/js?&libraries=places&key=AIzaSyD5y2Y1zfyWCWDEPRLDBDYuRoJ8ReHYXwY&callback=initMap';
document.head.appendChild(script);

// jQuery.getScript("https://maps.googleapis.com/maps/api/js?&libraries=places&key=AIzaSyD5y2Y1zfyWCWDEPRLDBDYuRoJ8ReHYXwY&callback=initMap");
function initMap() { }
(function ($) {
    'use strict';
    console.log('ready');
    $('.woocommerce-shipping-destination').hide();
    $('.woocommerce-shipping-fields').hide();
    $('.woocommerce-shipping-totals').hide();
    //$('.shipping').hide();
    $(() => {
        initMap = function () {
            console.log('Initializing maps');
            $("input[name='billing_address_1']").attr('id', 'api_to');
            if ($('#api_to').length) {
                $('#api_to').val($.cookie('name'));

                let country = 'ke';
                let options = {
                    componentRestrictions: { country: country },
                    // types: ['address']
                };
                let autocomplete = new google.maps.places.Autocomplete($("#api_to")[0], options);
                google.maps.event.addListener(
                    autocomplete,
                    'place_changed',
                    function () {
                        let place = autocomplete.getPlace();
                        let to_name = place.name;
                        let to_lat = place.geometry.location.lat();
                        let to_long = place.geometry.location.lng();
                        sendRequest(to_name, to_lat, to_long);
                        $.cookie('name', to_name);
                    }
                );
            }
        }
    });

    function sendRequest(to_name, to_lat, to_long) {
        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'post',
            data: {
                'action': 'getPriceQuote',
                'to_name': to_name,
                'to_lat': to_lat,
                'to_long': to_long,
            },
            beforeSend: function () {
                $('#info-block').hide();
                $('.divHidden').hide();
                $('.loader').show();
                $("#submitBtn").show();
                $("#submitBtn").css("background-color", "grey");
                $("#submitBtn").val('Estimating...');
            },
            success: function (res) {
                console.log('sendy_response', res);
                if (res.status) {
                    let price = res.data.amount;
                    if (price) {
                        $('.loader').hide();
                        $('#submitBtn').hide();
                        passDeliveryInfo();
                        //location.reload();
                    } else {
                        $('.loader').hide();
                        $('#submitBtn').hide();
                        $('#api_to').attr("placeholder", "Change delivery destination");
                        $('#info-block').show();
                    }
                } else {
                    $('.loader').hide();
                    $('#submitBtn').hide();
                    $('#error-block').show();
                    $("#error-block").text(res.description);
                }

            },
            error: function (errorThrown) {
                console.log('sendy_error', errorThrown);
                $('.loader').hide();
            }
        });
    }

    function passDeliveryInfo() {
        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'post',
            data: {
                'action': 'displayDelivery',
            },
            success: function (res) {
                console.log(res);
            },
            error: function (errorThrown) {
                console.log(errorThrown);
            }
        });
    }

})(jQuery);