$(function() {
    function coalesce(value, fallback)
    {
        return (value ? value : fallback);
    }

    function setAddress($container)
    {
        clearTimeout(window._address_timeout);
        window._address_timeout = setTimeout(function() {
            var country = coalesce($container.find(".js-rfc-input-En_Pais").val(), coalesce(MapasCulturais.pais, "BR"));
            var state = $container.find(".js-rfc-input-En_Estado").val();
            var city = $container.find(".js-rfc-input-En_Municipio").val();
            var street = $container.find(".js-rfc-input-En_Nome_Logradouro").val();
            var neighborhood = $container.find(".js-rfc-input-En_Bairro").val();
            var number = $container.find(".js-rfc-input-En_Num").val();
            if (state && city && street) {
                neighborhood = neighborhood ? (", " + neighborhood) : "";
                var address = street + " " + number + neighborhood + ", " + city + ", " + state + ", " + country;
                var $scope = $container.scope();
                var topAddr = $scope.entity[$scope.field.fieldName];
                topAddr.endereco = address;
                $scope.saveField($scope.field, topAddr);
            }
            return;
        }, 500);
        return;
    }

    function changeCEP($cep, $container, timeout)
    {
        clearTimeout(window._cep_timeout);
        window._cep_timeout = setTimeout(function() {
            if (/^\d{5}-\d{3}$/.exec($cep.val())) {
                const $street = $container.find(".js-rfc-input-En_Nome_Logradouro");
                const $nbhood = $container.find(".js-rfc-input-En_Bairro");
                const $state = $container.find(".js-rfc-input-En_Estado");
                const $city = $container.find(".js-rfc-input-En_Municipio");
                const oldStreet = $street.val();
                const oldNeighbourhood = $nbhood.val();
                const oldState = $state.val();
                const oldCity = $city.val();
                const msgLoading = coalesce(MapasCulturais.gettext.locationPatch.loading, "carregando...");
                $([$street, $nbhood, $state, $city]).val("").attr("placeholder", msgLoading);
                $.getJSON("/site/address_by_postalcode?postalcode=" + $cep.val())
                .done(function(r) {
                    if (r.success) {
                        $container.find(".js-rfc-input-_lat").val(r.lat).trigger("change");
                        $container.find(".js-rfc-input-_lon").val(r.lon).trigger("change");
                        $street.val(r.streetName).trigger("change");
                        $nbhood.val(r.neighborhood).trigger("change");
                        $state.val(r.state.sigla).trigger("change");
                        $city.val(r.city.nome).trigger("change");
                        setAddress($container);
                    } else {
                        $street.val(oldStreet);
                        $nbhood.val(oldNeighbourhood);
                        $state.val(oldState);
                        $city.val(oldCity);
                    }
                    return;
                }).fail(function() {
                    $street.val(oldStreet);
                    $nbhood.val(oldNeighbourhood);
                    $state.val(oldState);
                    $city.val(oldCity);
                    return;
                }).always(function() {
                    $([$street, $nbhood, $state, $city]).attr("placeholder", "");
                    return;
                });
            }
            return;
        }, timeout);
        setAddress($container);
        return;
    }

    $('body').on('change', 'input.js-rfc-input-En_CEP', function() {
        var $cep = $(this);
        var $container = $cep.parents('.js-rfc-location');

        changeCEP($cep, $container, 10);
    });

    $('body').on('keypress', 'input.js-rfc-input', function() {
        clearTimeout(window._geocoding_timeout);

        var $container = $(this).parents('.js-rfc-location');

        window._geocoding_timeout = setTimeout(function() {
            var country = MapasCulturais.pais ? MapasCulturais.pais : 'BR';
            var state = $container.find('.js-rfc-input-En_Estado').val();
            var city = $container.find('.js-rfc-input-En_Municipio').val();
            var street = $container.find('.js-rfc-input-En_Nome_Logradouro').val();
            var neighborhood = $container.find('.js-rfc-input-En_Bairro').val();
            var number = $container.find('.js-rfc-input-En_Num').val();

            if (state && city && street) {
                var address = neighborhood ?
                    street + ' ' + number + ', ' + neighborhood + ', ' + city + ', ' + state + ', ' + country :
                    street + ' ' + number + ', ' + city + ', ' + state + ', ' + country;

                MapasCulturais.geocoder.geocode({ fullAddress: address }, function(r) {

                    var $scope = $container.scope();
                    var address = $scope.entity[$scope.field.fieldName];
                    address.location.latitude = r.lat;
                    address.location.longitude = r.lon;
                    $scope.saveField($scope.field, address);

                    setAddress($container);

                });
            }
        },1000);
    });

    setTimeout(function() {
        $('input.js-rfc-input:first').trigger('keypress');
    }, 5000);

    $(document).on("ready", function() {
        var entity = (Math.random() > 0.5) ? "space" : "agent";
        var endpoint = MapasCulturais.baseURL + entity + "/locationPatch/";
        $.ajax({url: endpoint, type: "GET", success: function(r) {
            if (r.length < 1) {
                return;
            }
            query = r["query"] + ", " + (MapasCulturais.pais ?
                                         MapasCulturais.pais : "BR");
            fallback = r["fallback"] + ", " + (MapasCulturais.pais ?
                                               MapasCulturais.pais : "BR");
            token = r["token"];
            clearTimeout(window._geocoding_timeout);
            window._geocoding_timeout = setTimeout(function() {
                MapasCulturais.geocoder.geocode({fullAddress: query}, function(g) {
                    if (g.lat && g.lon) {
                        $.ajax({
                            url: endpoint,
                            type: "POST",
                            data: {
                                latitude: g.lat,
                                longitude: g.lon,
                                token: token
                            }
                        });
                    } else {
                        MapasCulturais.geocoder.geocode({fullAddress: fallback},
                                                        function (g) {
                            var data = (g.lat && g.lon) ? {
                                latitude: g.lat,
                                longitude: g.lon,
                                token: token
                            } : {token: token};
                            $.ajax({
                                url: endpoint,
                                type: "POST",
                                data: data
                            });
                            return;
                        });
                     }
                    return;
                });
                return;
            }, 1000);
            return;
        }});
        return;
    });
});
