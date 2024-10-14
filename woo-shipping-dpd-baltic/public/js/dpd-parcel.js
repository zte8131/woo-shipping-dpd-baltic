var dpd_gmap_api_key  = dpd.gmap_api_key;
var dpd_gmap_base_url = 'https://maps.googleapis.com/maps/api/geocode/json';

var dpd_parcel_modal;
var dpd_parcel_map = {};

(function($) {

	function onSearchLocationClick(event) {
		if (event) {
			event.preventDefault();
		}

		var address = $( 'input[name="dpd-modal-address"]' ).val() + ' ' + $( 'input[name="dpd-modal-city"]' ).val();

		if (address) {
			setGeocodedAddress( address );
		}
	}

	/**
	 * Attempt to get postal code from Google Geocoder JSON response.
	 *
	 * @param  {object} 	 data [Google Geocode JSON response]
	 * @return {string|null}      [Postal code]
	 */
	function getCoordinatesFromJson(data) {
		var coords;

		try {
			coords = data.results[0].geometry.location;
		} catch (ex) {
		}

		return coords;
	}

	function setGeocodedAddress(address) {
		if ( ! dpd_gmap_api_key) {
			console.log( 'Postal code geocoder is missing Google Maps API key' );
			return;
		}

		// Build URL query
		var params = jQuery.param(
			{
				address: address,
				type: 'street_address',
				key: dpd_gmap_api_key
			}
		);

		var url = dpd_gmap_base_url + '?' + params;

		// Send request to Google Geocoder
		$.getJSON(
			url,
			function(data) {
				if (data.status == 'OK') {
					var coords = getCoordinatesFromJson( data );
					if (coords) {
						dpd_parcel_map.my_location = coords;
						dpd_parcel_map.my_marker.setPosition( dpd_parcel_map.my_location );
						dpd_parcel_map.map.setZoom( 15 );
						dpd_parcel_map.map.panTo( dpd_parcel_map.my_location );
						setTimeout( "dpd_parcel_map.map.setZoom(14)", 1000 );
					} else {
						console.log( 'Cannot geocode coordinates from address' );
					}
				} else {
					console.log( 'Google Maps Geocoder Error', data );
					// el.val('');
				}
			}
		);
	}

	function onSelectTerminalClick(event) {
		event.preventDefault();

		var code           = $( this ).data( 'terminal-code' );
		var terminal_title = $( this ).data( 'terminal-title' );
		var terminal_field = $( this ).data( 'method' );
		var cod            = $( this ).data( 'cod' );

		// Temporart detach AJAX interceptor
		useAjaxInterceptor( false );

		$.ajax(
			{
				url: dpd.wc_ajax_url.toString().replace( "%%endpoint%%", "choose_dpd_terminal" ),
				dataType: 'json',
				method: 'POST',
				data: {
					action: 'choose_dpd_terminal',
					terminal_field: terminal_field,
					terminal: code,
					cod: cod,
					security: dpd.ajax_nonce,
				},
				success: function(response) {
					console.log( 'Terminal id: ' + response.shipping_parcel_id );
				},
				error: function(xhr, options, error) {
					console.error( 'DPD Parcel store: ' + error );
				},
				complete: function() {
					$( document.body ).trigger( "update_checkout" );
					$( '#wc_shipping_dpd_parcels_terminal' ).val( code );
					$( '#dpd-selected-parcel' ).html( terminal_title );
					$( '#dpd-close-parcel-modal' ).trigger( 'click' );
					// Need delay to reattach AJAX interceptor
					setTimeout(
						function() {
							useAjaxInterceptor( true );
						},
						1000
					);
				}
			}
		);
	}

	function showMarkerInfo(marker) {
		var terminal = marker.dpd_terminal;

		dpd_parcel_map.marker_info = $( '#dpd-parcel-modal-info' );

		var title   = dpd_parcel_map.marker_info.find( 'h3' );
		var address = dpd_parcel_map.marker_info.find( '.info-address' );
		var hours   = dpd_parcel_map.marker_info.find( '.working-hours-wrapper' );

		var mon = dpd_parcel_map.marker_info.find( '.mon' );
		var tue = dpd_parcel_map.marker_info.find( '.tue' );
		var wed = dpd_parcel_map.marker_info.find( '.wed' );
		var thu = dpd_parcel_map.marker_info.find( '.thu' );
		var fri = dpd_parcel_map.marker_info.find( '.fri' );
		var sat = dpd_parcel_map.marker_info.find( '.sat' );
		var sun = dpd_parcel_map.marker_info.find( '.sun' );

		var phone = dpd_parcel_map.marker_info.find( '.info-phone' );
		var email = dpd_parcel_map.marker_info.find( '.info-email' );
		var btn   = dpd_parcel_map.marker_info.find( '.select-terminal' );

		title.html( terminal.company );
		address.html( terminal.street + ', ' + terminal.pcode + ', ' + terminal.city || '-' );

		if (terminal.mon == null && terminal.tue == null && terminal.wed == null && terminal.thu == null && terminal.fri == null && terminal.sat == null && terminal.sun == null) {
			hours.hide();
		}

		if (terminal.mon != null) {
			var monday = terminal.mon.split( '|' );

			mon.find( ".morning" ).html( monday[0] );
			mon.find( ".afternoon" ).html( monday[1] );
		} else {
			mon.hide();
		}

		if (terminal.tue != null) {
			var tuesday = terminal.tue.split( '|' );

			tue.find( ".morning" ).html( tuesday[0] );
			tue.find( ".afternoon" ).html( tuesday[1] );
		} else {
			tue.hide();
		}

		if (terminal.wed != null) {
			var wednesday = terminal.wed.split( '|' );

			wed.find( ".morning" ).html( wednesday[0] );
			wed.find( ".afternoon" ).html( wednesday[1] );
		} else {
			wed.hide();
		}

		if (terminal.thu != null) {
			var thursday = terminal.thu.split( '|' );

			thu.find( ".morning" ).html( thursday[0] );
			thu.find( ".afternoon" ).html( thursday[1] );
		} else {
			thu.hide();
		}

		if (terminal.fri != null) {
			var friday = terminal.fri.split( '|' );

			fri.find( ".morning" ).html( friday[0] );
			fri.find( ".afternoon" ).html( friday[1] );
		} else {
			fri.hide();
		}

		if (terminal.sat != null) {
			var saturday = terminal.sat.split( '|' );

			sat.find( ".morning" ).html( saturday[0] );
			sat.find( ".afternoon" ).html( saturday[1] );
		} else {
			sat.hide();
		}

		if (terminal.sun != null) {
			var sunday = terminal.sun.split( '|' );

			sun.find( ".morning" ).html( sunday[0] );
			sun.find( ".afternoon" ).html( sunday[1] );
		} else {
			sun.hide();
		}

		// if (terminal.sat == '00:00-00:00') {
		// sat.hide();
		// } else {
		// sat.append(terminal.sat || '-');
		// }

		// if (terminal.sun == '00:00-00:00') {
		// sun.hide();
		// } else {
		// sun.append(terminal.sun || '-');
		// }

		if (terminal.phone) {
			phone.html( '<a href="tel:' + terminal.phone + '">' + terminal.phone + '</a>' );
		} else {
			phone.html( '' );
		}

		if (terminal.email) {
			email.html( '<a href="mailto:' + terminal.email + '">' + terminal.email + '</a>' );
		} else {
			email.html( '' );
		}

		if ( ! terminal.phone && ! terminal.email) {
			email.html( '-' );
		}

		btn.data( 'terminal-code', terminal.parcelshop_id );
		btn.data( 'terminal-title', terminal.company + ', ' + terminal.street );
		btn.attr( 'data-cod', terminal.cod );

		dpd_parcel_map.marker_info.show();

		dpd_parcel_map.map.panTo( marker.getPosition() );
	}

	/**
	 * Create marker objects and show them on map
	 *
	 * @param {array} data
	 */
	function setTerminalMarkers(data) {
		// Remove existing markers if any
		if (dpd_parcel_map.markers.length > 0) {
			for (var i = 0; i < dpd_parcel_map.markers.length; i++) {
				dpd_parcel_map.markers[i].setMap( null );
			}

			dpd_parcel_map.markers = [];
		}

		var marker, item;
		// Create and load marker objects
		for (var i = 0; i < data.length; i++) {
			item = data[i];
			try {
				// Create marker
				marker = new google.maps.Marker(
					{
						position: {lat: parseFloat( item.latitude ), lng: parseFloat( item.longitude )},
						map: dpd_parcel_map.map,
						icon: dpd.theme_uri + 'point.png'
					}
				);

				// Store terminal properties in marker
				marker['dpd_terminal'] = $.extend( {}, item );

				google.maps.event.addListener(
					marker,
					'click',
					function() {
						if (this.hasOwnProperty( 'dpd_terminal' )) {
							showMarkerInfo( this );
						}
					}
				);

				// Add to markers array
				dpd_parcel_map.markers.push( marker );
			} catch (e) {
				console.log( 'Cannot create marker for terminal', item );
			}

		}

		var markerCluster = new MarkerClusterer(
			dpd_parcel_map.map,
			dpd_parcel_map.markers,
			{
				imagePath: dpd.theme_uri + 'm',
				maxZoom: 13,
				minimumClusterSize: 1
			}
		);
	}

	/**
	 * Manage AJAX interceptor
	 *
	 * @param  {boolean} enabled
	 * @return {void}
	 */
	function useAjaxInterceptor(enabled) {
		if (enabled) {
			$( document ).ajaxStop(
				function() {
					init();
				}
			);
		} else {
			$( document ).off( 'ajaxStop' );
		}
	}

	/**
	 * Get terminals from API
	 *
	 * @return {void}
	 */
	function loadTerminalMarkers() {
		// Temporart detach AJAX interceptor
		useAjaxInterceptor( false );

		$.ajax(
			{
				url: dpd.wc_ajax_url.toString().replace( "%%endpoint%%", "get_dpd_parcels" ),
				dataType: 'json',
				method: 'POST',
				data: {
					action: 'get_dpd_parcels',
					fe_ajax_nonce: dpd.fe_ajax_nonce
				},
				success: function(response) {
					setTerminalMarkers( response );
				},
				error: function(xhr, options, error) {
					console.error( 'DPD Parcel store: ' + error );
				},
				complete: function() {
					// Need delay to reattach AJAX interceptor
					setTimeout(
						function() {
							useAjaxInterceptor( true );
						},
						1000
					);
				}
			}
		);
	}

	function initMap() {
		onSearchLocationClick( null );

		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(
				function(position) {
					dpd_parcel_map.my_location = {
						lat: position.coords.latitude,
						lng: position.coords.longitude
					};
				},
				function() {
					dpd_parcel_map.my_location = {lat:54.8897105, lng: 23.9258975};
				}
			);
		} else {
			// Browser doesn't support Geolocation
			dpd_parcel_map.my_location = {lat:54.8897105, lng: 23.9258975};
		}

		dpd_parcel_map.markers = [];
		dpd_parcel_map.marker_info.hide();

		// Initialize map
		dpd_parcel_map.map = new google.maps.Map(
			document.getElementById( 'dpd-parcel-modal-map' ),
			{
				center: dpd_parcel_map.my_location,
				zoom: 15,
				maxZoom: 17,
				gestureHandling: 'greedy', // Disable "use ctrl + scroll to zoom the map" message
				disableDefaultUI: true,
				zoomControl: true,
				zoomControlOptions: {
					position: google.maps.ControlPosition.LEFT_CENTER
				},
			}
		);

		dpd_parcel_map.my_marker = new google.maps.Marker(
			{
				position: dpd_parcel_map.my_location,
				map: dpd_parcel_map.map
			}
		);

		loadTerminalMarkers();
	}

	/**
	 * Setup modal window
	 *
	 * @return {void}
	 */
	function bindModal() {

		// Open the modal on button click
		$( 'body' ).on(
			'click',
			'#dpd-show-parcel-modal',
			function(event) {
				// $('#dpd-show-parcel-modal').off('click').click(function(event) {
				event.preventDefault();
				initMap();

				$( '#dpd-parcel-modal' ).css( 'display', 'block' );
				$( 'body' ).css( 'overflow', 'hidden' );

				// $(this).parent().find('input[name="shipping_method"]').prop('checked', true)
			}
		)

		// Close the modal on X click
		$( 'body' ).on(
			'click',
			'#dpd-close-parcel-modal',
			function(event) {
				// $('#dpd-close-parcel-modal').off('click').click(function(event) {
				event.preventDefault();
				$( '#dpd-parcel-modal' ).css( 'display', 'none' );
				$( 'body' ).css( 'overflow', 'auto' );
			}
		)

		// When the user clicks anywhere outside of the modal, close it
		$( 'body' ).on(
			'click',
			'#dpd-parcel-modal',
			function(event) {
				// dpd_parcel_modal.off('click').click(function(event) {
				if (event.target == $( this ).get( 0 )) {
					$( '#dpd-parcel-modal' ).css( 'display', 'none' );
					$( 'body' ).css( 'overflow', 'auto' );
				}
			}
		)

		dpd_parcel_map.marker_info = $( '#dpd-parcel-modal-info' );
		// dpd_parcel_map.marker_info.find('.select-terminal').off('click').click(onSelectTerminalClick);
		$( 'body' ).on( 'click', '#dpd-parcel-modal-info .select-terminal', onSelectTerminalClick );

		dpd_parcel_modal.find( '.search-location' ).off( 'click' ).click( onSearchLocationClick );

		$( document ).on(
			'keyup keypress',
			'#dpd-parcel-modal input[type="text"]',
			function(e) {
				if (e.which == 13) {
					e.preventDefault();
					onSearchLocationClick();
					return false;
				}
			}
		);
	}

	/**
	 * Setup parcel modal
	 *
	 * @return {void}
	 */
	function init() {
		dpd_parcel_modal = $( '#dpd-parcel-modal' );

		// if(!$('#dpd-selected-parcel').html()) {
		// $('[name="shipping_method"]:checked').prop('checked', false)
		// }

		// Exit script if modal isn't available
		if (dpd_parcel_modal.length == 0) {
			return;
		}

		bindModal();
	}

	/**
	 * Initialize on jQuery ready
	 */
	$( document ).ready(
		function() {
			// Watch for AJAX calls to finish and bind events
			useAjaxInterceptor( true );
		}
	)
})( jQuery );
