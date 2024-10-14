var dpd_parcel_modal, dpd_gmap_api_key = dpd.gmap_api_key,
    dpd_gmap_base_url = "https://maps.googleapis.com/maps/api/geocode/json",
    dpd_parcel_map = {};
! function(a) {
    function e(e) {
        e && e.preventDefault();

        var d = a('input[name="dpd-modal-address"]').val() + " " + a('input[name="dpd-modal-city"]').val();
        d && function e(d) {
            if (!dpd_gmap_api_key) {
                console.log("Postal code geocoder is missing Google Maps API key");
                return
            }
            var n = jQuery.param({
                address: d,
                type: "street_address",
                key: dpd_gmap_api_key
            });
            // alert(a.status);
            a.getJSON(dpd_gmap_base_url + "?" + n, function(a) {
                if ("OK" == a.status) {
                    var e = function a(e) {
                        var d;
                        try {
                            d = e.results[0].geometry.location
                        } catch (n) {}
                        return d
                    }(a);
                    e ? (dpd_parcel_map.my_location = e, dpd_parcel_map.my_marker.setPosition(dpd_parcel_map.my_location), dpd_parcel_map.map.setZoom(15), dpd_parcel_map.map.panTo(dpd_parcel_map.my_location), setTimeout("dpd_parcel_map.map.setZoom(14)", 1e3)) : console.log("Cannot geocode coordinates from address")
                } else console.log("Google Maps Geocoder Error", a)
            })
        }(d)
    }

    function d(e) {
        e.preventDefault();
        var d = a(this).data("terminal-code"),
            n = a(this).data("terminal-title"),
            o = a(this).data("method"),
            r = a(this).data("cod");
        p(!1), a.ajax({
            url: dpd.wc_ajax_url.toString().replace("%%endpoint%%", "choose_dpd_terminal"),
            dataType: "json",
            method: "POST",
            data: {
                action: "choose_dpd_terminal",
                terminal_field: o,
                terminal: d,
                cod: r,
                security: dpd.ajax_nonce
            },
            success: function(a) {
                console.log("Terminal id: " + a.shipping_parcel_id)
            },
            error: function(a, e, d) {
                console.error("DPD Parcel store: " + d)
            },
            complete: function() {
                a(document.body).trigger("update_checkout"), a("#wc_shipping_dpd_parcels_terminal").val(d), a("#dpd-selected-parcel").html(n), a("#dpd-close-parcel-modal").trigger("click"), setTimeout(function() {
                    p(!0)
                }, 1e3)
            }
        })
    }

    function n(e) {
        var d = e.dpd_terminal;
        dpd_parcel_map.marker_info = a("#dpd-parcel-modal-info");
        var n = dpd_parcel_map.marker_info.find("h3"),
            p = dpd_parcel_map.marker_info.find(".info-address"),
            o = dpd_parcel_map.marker_info.find(".working-hours-wrapper"),
            r = dpd_parcel_map.marker_info.find(".mon"),
            l = dpd_parcel_map.marker_info.find(".tue"),
            t = dpd_parcel_map.marker_info.find(".wed"),
            i = dpd_parcel_map.marker_info.find(".thu"),
            m = dpd_parcel_map.marker_info.find(".fri"),
            c = dpd_parcel_map.marker_info.find(".sat"),
            s = dpd_parcel_map.marker_info.find(".sun"),
            f = dpd_parcel_map.marker_info.find(".info-phone"),
            u = dpd_parcel_map.marker_info.find(".info-email"),
            h = dpd_parcel_map.marker_info.find(".select-terminal");
        if (n.html(d.company), p.html(d.street + ", " + d.pcode + ", " + d.city || "-"), null == d.mon && null == d.tue && null == d.wed && null == d.thu && null == d.fri && null == d.sat && null == d.sun && o.hide(), null != d.mon) {
            var g = d.mon.split("|");
            r.find(".morning").html(g[0]), r.find(".afternoon").html(g[1])
        } else r.hide();
        if (null != d.tue) {
            var k = d.tue.split("|");
            l.find(".morning").html(k[0]), l.find(".afternoon").html(k[1])
        } else l.hide();
        if (null != d.wed) {
            var y = d.wed.split("|");
            t.find(".morning").html(y[0]), t.find(".afternoon").html(y[1])
        } else t.hide();
        if (null != d.thu) {
            var v = d.thu.split("|");
            i.find(".morning").html(v[0]), i.find(".afternoon").html(v[1])
        } else i.hide();
        if (null != d.fri) {
            var $ = d.fri.split("|");
            m.find(".morning").html($[0]), m.find(".afternoon").html($[1])
        } else m.hide();
        if (null != d.sat) {
            var _ = d.sat.split("|");
            c.find(".morning").html(_[0]), c.find(".afternoon").html(_[1])
        } else c.hide();
        if (null != d.sun) {
            var w = d.sun.split("|");
            s.find(".morning").html(w[0]), s.find(".afternoon").html(w[1])
        } else s.hide();
        d.phone ? f.html('<a href="tel:' + d.phone + '">' + d.phone + "</a>") : f.html(""), d.email ? u.html('<a href="mailto:' + d.email + '">' + d.email + "</a>") : u.html(""), d.phone || d.email || u.html("-"), h.data("terminal-code", d.parcelshop_id), h.data("terminal-title", d.company + ", " + d.street), h.attr("data-cod", d.cod), dpd_parcel_map.marker_info.show(), dpd_parcel_map.map.panTo(e.getPosition())
    }

    function p(o) {
        o ? a(document).ajaxStop(function() {
            0 != (dpd_parcel_modal = a("#dpd-parcel-modal")).length && (a("body").on("click", "#dpd-show-parcel-modal", function(d) {
                d.preventDefault(), e(null), navigator.geolocation ? navigator.geolocation.getCurrentPosition(function(a) {
                    dpd_parcel_map.my_location = {
                        lat: a.coords.latitude,
                        lng: a.coords.longitude
                    }
                }, function() {
                    dpd_parcel_map.my_location = {
                        lat: 54.8897105,
                        lng: 23.9258975
                    }
                }) : dpd_parcel_map.my_location = {
                    lat: 54.8897105,
                    lng: 23.9258975
                }, dpd_parcel_map.markers = [], dpd_parcel_map.marker_info.hide(), dpd_parcel_map.map = new google.maps.Map(document.getElementById("dpd-parcel-modal-map"), {
                    center: dpd_parcel_map.my_location,
                    zoom: 15,
                    maxZoom: 17,
                    gestureHandling: "greedy",
                    disableDefaultUI: !0,
                    zoomControl: !0,
                    zoomControlOptions: {
                        position: google.maps.ControlPosition.LEFT_CENTER
                    }
                }), dpd_parcel_map.my_marker = new google.maps.Marker({
                    position: dpd_parcel_map.my_location,
                    map: dpd_parcel_map.map
                }), p(!1), a.ajax({
                    url: dpd.wc_ajax_url.toString().replace("%%endpoint%%", "get_dpd_parcels"),
                    dataType: "json",
                    method: "POST",
                    data: {
                        action: "get_dpd_parcels",
                        fe_ajax_nonce: dpd.fe_ajax_nonce
                    },
                    success: function(e) {

                        ! function e(d) {
                            if (dpd_parcel_map.markers.length > 0) {
                                for (var p, o, r = 0; r < dpd_parcel_map.markers.length; r++) dpd_parcel_map.markers[r].setMap(null);
                                dpd_parcel_map.markers = []
                            }

                            for (var r = 0; r < d.length; r++) {
                                o = d[r];
                                try {
                                    (p = new google.maps.Marker({
                                        position: {
                                            lat: parseFloat(o.latitude),
                                            lng: parseFloat(o.longitude)
                                        },
                                        map: dpd_parcel_map.map,
                                        icon: dpd.theme_uri + "point.png"
                                    })).dpd_terminal = a.extend({}, o), google.maps.event.addListener(p, "click", function() {
                                        this.hasOwnProperty("dpd_terminal") && n(this)
                                    }), dpd_parcel_map.markers.push(p)
                                } catch (l) {
                                    console.log("Cannot create marker for terminal", o)
                                }
                            }

                            new MarkerClusterer(dpd_parcel_map.map, dpd_parcel_map.markers, {
                                imagePath: dpd.theme_uri + "m",
                                maxZoom: 13,
                                minimumClusterSize: 1
                            })
                        }(e)
                    },
                    error: function(a, e, d) {
                        console.error("DPD Parcel store: " + d)
                    },
                    complete: function() {
                        setTimeout(function() {
                            p(!0)
                        }, 1e3)
                        // p(!0)
                        // e();
                        // p(!1);
                    }
                }), a("#dpd-parcel-modal").css("display", "block"), a("body").css("overflow", "hidden")
            }), a("body").on("click", "#dpd-close-parcel-modal", function(e) {
                e.preventDefault(), a("#dpd-parcel-modal").css("display", "none"), a("body").css("overflow", "auto")
            }), a("body").on("click", "#dpd-parcel-modal", function(e) {
                e.target == a(this).get(0) && (a("#dpd-parcel-modal").css("display", "none"), a("body").css("overflow", "auto"))
            }), dpd_parcel_map.marker_info = a("#dpd-parcel-modal-info"), a("body").on("click", "#dpd-parcel-modal-info .select-terminal", d), dpd_parcel_modal.find(".search-location").off("click").click(e), a(document).on("keyup keypress", '#dpd-parcel-modal input[type="text"]', function(a) {
                if (13 == a.which) return a.preventDefault(), e(), !1
            }))
        }) : a(document).off("ajaxStop")
    }
    a(document).ready(function() {
        p(!0)
        // e()
    })
}(jQuery);