<?php
/**
 * Ajax File Doc.
 *
 * @category Dpd
 * @package  Ajax
 * @author   DPD
 */

/**
 * Dpd_Baltic_Ajax class.
 *
 * @package    Dpd
 * @subpackage Dpd/includes
 * @author     DPD
 */
class Dpd_Baltic_Ajax {

	/**
	 * Get ajax terminals.
	 *
	 * @return void
	 */
	public function get_ajax_terminals() {

		if ( ! isset( $_POST['fe_ajax_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['fe_ajax_nonce'] ) ), 'fe-nonce' ) ) {
			wp_die();
		}

		$data = $this->get_terminals( WC()->customer->get_shipping_country() );

		wp_send_json( $data );
	}

    public function get_ajax_terminals_new() {
        if ( ! isset( $_POST['fe_ajax_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['fe_ajax_nonce'] ) ), 'fe-nonce' ) ) {
            wp_die();
        }

        $data = $this->get_terminals( WC()->customer->get_shipping_country() );

        wp_send_json( $data );
    }

	/**
	 * Ajax save session terminal.
	 *
	 * @return void
	 */
	public function ajax_save_session_terminal() {
		check_ajax_referer( 'save-terminal', 'security' );

		if ( isset( $_POST['terminal_field'] ) && isset( $_POST['terminal'] ) ) {
			$terminal_field = filter_var( wp_unslash( $_POST['terminal_field'] ), FILTER_SANITIZE_STRING );
			$terminal       = filter_var( wp_unslash( $_POST['terminal'] ), FILTER_SANITIZE_STRING );

			WC()->session->set( wc_clean( $terminal_field ), wc_clean( $terminal ) );
		}

		if ( isset( $_REQUEST['cod'] ) ) {
			$cod = filter_var( wp_unslash( $_REQUEST['cod'] ), FILTER_SANITIZE_NUMBER_INT );

			if ( is_numeric( $cod ) ) {
				WC()->session->set( 'cod_for_parcel', $cod );
			}
		}

		if ( isset( $_POST['terminal_field'] ) ) {
			// Function wc_clean() sanitized.
			wp_send_json(
				array(
					'shipping_parcel_id' => WC()->session->get( wc_clean( wp_unslash( $_POST['terminal_field'] ) ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				)
			);
		}

		wp_die();
	}

	/**
	 * Checkout save session fields.
	 *
	 * @param mixed $post_data Post data.
	 *
	 * @return void
	 */
	public function checkout_save_session_fields( $post_data ) {
		parse_str( $post_data, $posted );

		$google_map_api = get_option( 'dpd_google_map_key' );

		if ( '' == $google_map_api ) {
			if ( isset( $posted['wc_shipping_dpd_parcels_terminal'] ) && ! empty( $posted['wc_shipping_dpd_parcels_terminal'] ) ) {
				WC()->session->set( 'wc_shipping_dpd_parcels_terminal', $posted['wc_shipping_dpd_parcels_terminal'] );
			}

			if ( isset( $posted['wc_shipping_dpd_sameday_parcels_terminal'] ) && ! empty( $posted['wc_shipping_dpd_sameday_parcels_terminal'] ) ) {
				WC()->session->set( 'wc_shipping_dpd_sameday_parcels_terminal', $posted['wc_shipping_dpd_sameday_parcels_terminal'] );
			}
		}

		if ( isset( $posted['wc_shipping_dpd_home_delivery_shifts'] ) && ! empty( $posted['wc_shipping_dpd_home_delivery_shifts'] ) ) {
			WC()->session->set( 'wc_shipping_dpd_home_delivery_shifts', $posted['wc_shipping_dpd_home_delivery_shifts'] );
		}
	}

	/**
	 * Dpd request courier.
	 *
	 * @return void
	 */
	public function dpd_request_courier() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'dpd-request-courier' ) ) {
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );
			exit;
		}

		if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'dpd-request-courier' ) ) {
			$order_nr = get_option( 'dpd_request_order_nr' );

			if ( '' === $order_nr ) {
				update_option( 'dpd_request_order_nr', 1 );

				$order_nr = get_option( 'dpd_request_order_nr' );
			} else {
				update_option( 'dpd_request_order_nr', (int) $order_nr + 1 );

				$order_nr = get_option( 'dpd_request_order_nr' );
			}

			// Get info about warehouse.
			if ( isset( $_GET['dpd_warehouse'] ) ) {
				$sanitize_dpd_warehouse = sanitize_key( wp_unslash( $_GET['dpd_warehouse'] ) );
				$warehouse_info         = maybe_unserialize( get_option( $sanitize_dpd_warehouse ) );
			}

			$response = '';

			if ( isset( $warehouse_info ) && $warehouse_info ) {
				$payer_id           = get_option( 'dpd_api_username' );
				$sender_address     = $this->custom_length( $warehouse_info['address'], 100 );
				$sender_city        = $this->custom_length( $warehouse_info['city'], 100 );
				$sender_country     = $warehouse_info['country'];
				$sender_postal_code = preg_replace( '/[^0-9,.]/', '', $warehouse_info['postcode'] );
				$sender_contact     = $this->custom_length( $warehouse_info['contact_person'], 100 );

				$pallets_count = isset( $_GET['dpd_pallets'] ) ? intval( sanitize_text_field( wp_unslash( $_GET['dpd_pallets'] ) ) ) : 0;
				$parcels_count = isset( $_GET['dpd_count_parcels'] ) ? intval( sanitize_text_field( wp_unslash( $_GET['dpd_count_parcels'] ) ) ) : 0;

				if ( isset( $_GET['dpd_weight'] ) ) {
					$weight_total = floatval( $_GET['dpd_weight'] ) ? floatval( sanitize_text_field( wp_unslash( $_GET['dpd_weight'] ) ) ) : 0.1;
				} else {
					$weight_total = 0.1;
				}

				// Correct phone.
				$dial_code_helper = new Dpd_Baltic_Dial_Code_Helper();
				$correct_phone    = $dial_code_helper->separate_phone_number_from_country_code( $warehouse_info['phone'], $sender_country );
				$phone            = $correct_phone['dial_code'] . $correct_phone['phone_number'];

				// Working hours.
				$dayofweek = current_time( 'w' );

				$pickup_from  = isset( $_GET['dpd_pickup_from'] ) ? sanitize_text_field( wp_unslash( $_GET['dpd_pickup_from'] ) ) . ':00' : '10:00:00';
				$pickup_until = isset( $_GET['dpd_pickup_until'] ) ? sanitize_text_field( wp_unslash( $_GET['dpd_pickup_until'] ) ) . ':00' : '17:30:00';

				$time_cut_off = strtotime( '15:00:00' );
				$current_time = current_time( 'H:i:s' );

				if ( 6 === $dayofweek ) {
					// If its saturday.
					$date = gmdate( 'Y-m-d', strtotime( '+ 2 days', strtotime( $current_time ) ) );
				} elseif ( 7 === $dayofweek ) {
					// If its sunday.
					$date = gmdate( 'Y-m-d', strtotime( '+ 1 day', strtotime( $current_time ) ) );
				} elseif ( 5 === $dayofweek ) {
					// If its more or equal 15, request go for tommorow.
					if ( strtotime( $current_time ) >= $time_cut_off || $pickup_from >= $time_cut_off ) {
						$date = gmdate( 'Y-m-d', strtotime( '+ 3 days', strtotime( $current_time ) ) );
					} else {
						$date = current_time( 'Y-m-d' );
					}
				} else {
					if ( strtotime( $current_time ) >= $time_cut_off || $pickup_from >= $time_cut_off ) {
						$date = gmdate( 'Y-m-d', strtotime( '+ 1 days', strtotime( $current_time ) ) );
					} else {
						$date = current_time( 'Y-m-d' );
					}
				}

				if ( isset( $_GET['dpd_pickup_date'] ) && strtotime( sanitize_text_field( wp_unslash( $_GET['dpd_pickup_date'] ) ) ) > strtotime( $date ) ) {
					$date = sanitize_text_field( wp_unslash( $_GET['dpd_pickup_date'] ) );
				}

				$until = $date . ' ' . $pickup_until;
				$from  = $date . ' ' . $pickup_from;

				// Comment.
				$comment = isset( $_GET['dpd_note'] ) ? $this->custom_length( sanitize_text_field( wp_unslash( $_GET['dpd_note'] ) ), 100 ) : '';

				$response = Dpd_Admin::http_client(
					'pickupOrderSave_',
					array(
						'orderNr'          => $order_nr,
						'payerId'          => $payer_id,
						'senderAddress'    => $sender_address,
						'senderCity'       => $sender_city,
						'senderCountry'    => $sender_country,
						'senderPostalCode' => $sender_postal_code,
						'senderContact'    => $sender_contact,
						'senderPhone'      => $phone,
						'senderWorkUntil'  => $until,
						'pickupTime'       => $from,
						'weight'           => $weight_total,
						'parcelsCount'     => $parcels_count,
						'palletsCount'     => $pallets_count,
						'nonStandard'      => $comment, // Comment for courier.
					)
				);

				if ( 'DONE|' === $response ) {
					$response .= __( 'Courier will arrive from:', 'woo-shipping-dpd-baltic' ) . ' ' . $from;
					$response .= ' ' . __( 'until', 'woo-shipping-dpd-baltic' ) . ' ' . $pickup_until;
				}

				dpd_baltic_add_flash_notice( $response, 'info', true );
			} else {
				dpd_baltic_add_flash_notice( 'Warehouse not found!', 'error', true );
			}
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );
		exit;
	}

	/**
	 * Dpd close manifest.
	 *
	 * @return void
	 */
	public function dpd_close_manifest() {
		global $wpdb;

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'dpd-close-manifest' ) ) {
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );
			exit;
		}

		if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'dpd-close-manifest' ) ) {
			$service_provider  = get_option( 'dpd_api_service_provider' );
			$test_mode         = get_option( 'dpd_test_mode' );
			$service_test_mode = ! empty( $test_mode ) && 'yes' === $test_mode ? true : false;

			$dates = array(
				gmdate( 'Y-m-d' ),
				gmdate( 'Y-m-d', strtotime( '+ 1 day' ) ),
				gmdate( 'Y-m-d', strtotime( '+ 2 days' ) ),
				gmdate( 'Y-m-d', strtotime( '+ 3 days' ) ),
			);

			$i = 0;
			foreach ( $dates as $date ) {
				$i ++;

				$response = Dpd_Admin::http_client(
					'parcelManifestPrint_',
					array(
						'type' => 'manifest',
						'date' => $date,
					)
				);

				$message = sprintf(
					/* translators: %s: dpd manifest is closed */
					__( 'DPD manifest is closed for today\'s orders that were made up to now. DPD doesn\'t require you to print the manifest. If you would like to print the manifest anyway, go <a href="%s">here</a>.', 'woo-shipping-dpd-baltic' ),
					esc_url(
						add_query_arg(
							array(
								'page'    => 'wc-settings',
								'tab'     => 'dpd',
								'section' => 'manifests',
							),
							admin_url( 'admin.php' )
						)
					)
				);

				if ( 'lt' === $service_provider && ! $service_test_mode ) {
					$decode_response = json_decode( $response ) ?: null;

					if ( $decode_response && !empty( $decode_response->status ) && 'err' === $decode_response->status ) {
						if ( 1 == $i ) {
							dpd_baltic_add_flash_notice( $decode_response->errlog, 'error', true );
						}

						continue;
					} else {
						if ( ! empty( $decode_response ) && ! empty( $decode_response->pdf ) ) {
							$wpdb->insert(
								$wpdb->prefix . 'dpd_manifests',
                                array(
                                    'pdf'  => base64_encode( $decode_response->pdf ),
                                    'date' => $date,
                                )
							);

							if ( 1 == $i ) {
								dpd_baltic_add_flash_notice( $message, 'success', true );
							}
						} else if ( ! empty( $response ) ) {
                            $wpdb->insert(
                                $wpdb->prefix . 'dpd_manifests',
                                array(
                                    'pdf'  => base64_encode( $response ),
                                    'date' => $date,
                                )
                            );

                            if ( 1 == $i ) {
                                dpd_baltic_add_flash_notice( $message, 'success', true );
                            }
                        }
					}
				} else {
					$response = @json_decode( $response );

					if ( $response && 'ok' === $response->status ) {
						if ( $response->pdf && null !== $response->pdf ) {
							$wpdb->insert(
								$wpdb->prefix . 'dpd_manifests',
								array(
									'pdf'  => $response->pdf,
									'date' => $date,
								)
							);

							if ( 1 == $i ) {
								dpd_baltic_add_flash_notice( $message, 'success', true );
							}
						}
					} else {
						if ( 1 == $i ) {
							dpd_baltic_add_flash_notice( $response->errlog, 'error', true );
						}

						continue;
					}
				}
			}
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );
		exit;
	}

	/**
	 * Dpd order collection request.
	 *
	 * @param array   $data Data.
	 * @param boolean $die Die.
	 * @param boolean $post Post.
	 *
	 * @return mixed
	 */
	public function dpd_order_collection_request( $data = array(), $die = true, $post = true ) {
		$wp_nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		if ( ! empty( $wp_nonce ) ) {
			if ( ! isset( $_POST['post_ID'] ) ) {
				return;
			}

			$nonce_action = 'update-post_' . sanitize_text_field( wp_unslash( $_POST['post_ID'] ) );

			if ( ! wp_verify_nonce( $wp_nonce, $nonce_action ) ) {
				return;
			}
		} else {
			if ( ! isset( $_POST['admin_ajax_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['admin_ajax_nonce'] ) ), 'admin-nonce' ) ) {
				wp_die();
			}
		}

		$location = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-settings&tab=dpd&section=collect' );

		if ( current_user_can( 'edit_shop_orders' ) ) {
			$dpd_collect_parcels_number = $data['dpd_collect_parcels_number'] ?? 0;
			$dpd_collect_pallets_number = $data['dpd_collect_pallets_number'] ?? 0;
			$dpd_collect_total_weight   = $data['dpd_collect_total_weight'] ?? 0;
			$dpd_collect_pickup_date    = $data['dpd_collect_pickup_date'] ?? '';
			$cname                      = $data['dpd_collect_sender_name'] ?? '';
			$cstreet                    = $data['dpd_collect_sender_street_address'] ?? '';
			$cpostal                    = $data['dpd_collect_sender_postcode'] ?? '';
			$ccity                      = $data['dpd_collect_sender_city'] ?? '';
			$ccountry                   = $data['dpd_collect_sender_country'] ?? '';
			$cphone                     = $data['dpd_collect_sender_contact_phone_number'] ?? '';
			$cemail                     = $data['dpd_collect_sender_contact_email'] ?? '';
			$rstreet                    = $data['dpd_collect_recipient_street_address'] ?? '';
			$rpostal                    = $data['dpd_collect_recipient_postcode'] ?? '';
			$rcity                      = $data['dpd_collect_recipient_city'] ?? '';
			$rcountry                   = $data['dpd_collect_recipient_country'] ?? '';
			$rphone                     = $data['dpd_collect_recipient_contact_phone_number'] ?? '';
			$rname                      = $data['dpd_collect_recipient_name'] ?? '';
			$remail                     = $data['dpd_collect_recipient_contact_email'] ?? '';
			$info2                      = $data['dpd_collect_additional_information'] ?? '';

			if ( isset( $_POST ) && $post ) {
				$dpd_collect_parcels_number = isset( $_POST['dpd_collect_parcels_number'] )
					? sanitize_text_field( wp_unslash( $_POST['dpd_collect_parcels_number'] ) )
					: 0;

				$dpd_collect_pallets_number = isset( $_POST['dpd_collect_pallets_number'] )
					? sanitize_text_field( wp_unslash( $_POST['dpd_collect_pallets_number'] ) )
					: 0;

				$dpd_collect_total_weight = isset( $_POST['dpd_collect_total_weight'] )
					? sanitize_text_field( wp_unslash( $_POST['dpd_collect_total_weight'] ) )
					: 0;

				$dpd_collect_pickup_date = isset( $_POST['dpd_collect_pickup_date'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_pickup_date'] ) )
					: 0;

				$cname = isset( $_POST['dpd_collect_sender_name'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_sender_name'] ) )
					: '';

				$cstreet = isset( $_POST['dpd_collect_sender_street_address'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_sender_street_address'] ) )
					: '';

				$cpostal = isset( $_POST['dpd_collect_sender_postcode'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_sender_postcode'] ) )
					: '';

				$ccity = isset( $_POST['dpd_collect_sender_city'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_sender_city'] ) )
					: '';

				$ccountry = isset( $_POST['dpd_collect_sender_country'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_sender_country'] ) )
					: '';

				$cphone = isset( $_POST['dpd_collect_sender_contact_phone_number'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_sender_contact_phone_number'] ) )
					: '';

				$cemail = isset( $_POST['dpd_collect_sender_contact_email'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_sender_contact_email'] ) )
					: '';

				$rstreet = isset( $_POST['dpd_collect_recipient_street_address'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_recipient_street_address'] ) )
					: '';

				$rpostal = isset( $_POST['dpd_collect_recipient_postcode'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_recipient_postcode'] ) )
					: '';

				$rcity = isset( $_POST['dpd_collect_recipient_city'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_recipient_city'] ) )
					: '';

				$rcountry = isset( $_POST['dpd_collect_recipient_country'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_recipient_country'] ) )
					: '';

				$rphone = isset( $_POST['dpd_collect_recipient_contact_phone_number'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_recipient_contact_phone_number'] ) )
					: '';

				$rname = isset( $_POST['dpd_collect_recipient_name'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_recipient_name'] ) )
					: '';

				$remail = isset( $_POST['dpd_collect_recipient_contact_email'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_recipient_contact_email'] ) )
					: '';

				$info2 = isset( $_POST['dpd_collect_additional_information'] )
					? sanitize_key( wp_unslash( $_POST['dpd_collect_additional_information'] ) )
					: '';
			}

			$total_amount = absint( $dpd_collect_parcels_number ) + absint( $dpd_collect_pallets_number );

			if ( isset( $dpd_collect_total_weight ) && $dpd_collect_total_weight > 0 ) {
				$weight = '#kg' . round( ( $dpd_collect_total_weight ), 2 );
			} else {
				$weight = '#kg20';
			}

			if ( $total_amount > 0 ) {
				$parcels_amount = absint( $dpd_collect_parcels_number );
				$pallets_amount = absint( $dpd_collect_pallets_number );

				$parcels_no = '#' . $parcels_amount . 'cl';
				$pallets_no = '#' . $pallets_amount . 'pl';

				$cname0 = substr( $cname, 0, 35 );
				$cname1 = substr( $cname, 35, 35 );
				$cname2 = substr( $cname, 70, 35 );
				$cname3 = substr( $cname, 105, 35 );

				$rname0 = substr( $rname, 0, 35 );
				$rname1 = substr( $rname, 35, 35 );

				$pickup_date = isset( $dpd_collect_pickup_date ) ? '#' . substr( $dpd_collect_pickup_date, 5 ) : '#' . gmdate( 'm-d', strtotime( '+ 1 day', strtotime( current_time( 'Y-m-d' ) ) ) );
				$info1       = $parcels_no . $pallets_no . $pickup_date . $weight;

				$params = array(
					'cstreet'  => $cstreet,
					'ccountry' => strtoupper( $ccountry ),
					'cpostal'  => $cpostal,
					'ccity'    => $ccity,
					'info1'    => $info1,
					'rstreet'  => $rstreet,
					'rpostal'  => $rpostal,
					'rcountry' => strtoupper( $rcountry ),
					'rcity'    => $rcity,
				);

				if ( isset( $cphone ) && $cphone ) {
					$params['cphone'] = $cphone;
				}

				if ( isset( $cemail ) && $cemail ) {
					$params['cemail'] = $cemail;
				}

				if ( isset( $rphone ) && $rphone ) {
					$params['rphone'] = $rphone;
				}

				if ( isset( $remail ) && $remail ) {
					$params['remail'] = $remail;
				}

				if ( isset( $info2 ) && $info2 ) {
					$params['info2'] = $info2;
				}

				if ( isset( $cname0 ) && $cname0 ) {
					$params['cname'] = $cname0;
				}

				if ( isset( $cname1 ) && $cname1 ) {
					$params['cname1'] = $cname1;
				}

				if ( isset( $cname2 ) && $cname2 ) {
					$params['cname2'] = $cname2;
				}

				if ( isset( $cname3 ) && $cname3 ) {
					$params['cname3'] = $cname3;
				}

				if ( isset( $rname0 ) && $rname0 ) {
					$params['rname'] = $rname0;
				}

				if ( isset( $rname1 ) && $rname1 ) {
					$params['rname2'] = $rname1;
				}

				$response = Dpd_Admin::http_client( 'crImport_', $params );

				if ( strpos( $response, '201' ) !== false ) {
					dpd_baltic_add_flash_notice( __( 'Your request was sent to DPD and your parcels will be collected. For more info call DPD.', 'woo-shipping-dpd-baltic' ), 'success', true );
				} else {
					dpd_baltic_add_flash_notice( $response, 'error', true );
				}
			}

			if ( $die ) {
				die( wp_kses( $location, array() ) );
			} else {
				return;
			}
		}

		if ( $die ) {
			die( wp_kses( $location, array() ) );
		} else {
			return;
		}
	}

	/**
	 * Dpd order reverse collection request.
	 *
	 * @param array $order Order.
	 *
	 * @return void
	 */
	public function dpd_order_reverse_collection_request( $order ) {
		$wp_nonce     = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		$nonce_action = 'update-post_' . $order->get_id();

		if ( ! wp_verify_nonce( $wp_nonce, $nonce_action ) ) {
			return;
		}

		$country_code = $order->get_shipping_country();
		if ( strtoupper( $country_code ) == 'LT' || strtoupper( $country_code ) == 'LV' || strtoupper( $country_code ) == 'EE' ) {
			$pcode = preg_replace( '/[^0-9,.]/', '', $order->get_shipping_postcode() );
		} else {
			$pcode = $order->get_shipping_postcode();
		}

		$dial_code_helper = new Dpd_Baltic_Dial_Code_Helper();
		$correct_phone    = $dial_code_helper->separate_phone_number_from_country_code( $order->get_billing_phone(), $country_code );

		$data['dpd_collect_sender_name']                 = $this->custom_length( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(), 140 );
		$data['dpd_collect_sender_street_address']       = $this->custom_length( $order->get_shipping_address_1(), 35 );
		$data['dpd_collect_sender_postcode']             = $pcode;
		$data['dpd_collect_sender_city']                 = $this->custom_length( $order->get_shipping_city(), 25 );
		$data['dpd_collect_sender_contact_phone_number'] = $correct_phone['dial_code'] . $correct_phone['phone_number'];
		$data['dpd_collect_sender_contact_email']        = $order->get_billing_email();
		$data['dpd_collect_sender_country']              = $country_code;
		$data['dpd_collect_parcels_number']              = '1';
		$data['dpd_collect_pallets_number']              = '0';

		$data['dpd_collect_total_weight'] = 0; // @TODO: get product weight
		$data['dpd_collect_pickup_date']  = gmdate( 'Y-m-d', strtotime( '+ 1 day', strtotime( current_time( 'Y-m-d' ) ) ) );

		$data['dpd_collect_additional_information'] = 'product return';

		// Nonce already verification by admin.
		if ( isset( $_POST['dpd_warehouse'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
			// Function wc_clean() sanitized.
			$warehouse_info  = maybe_unserialize( get_option( wc_clean( wp_unslash( $_POST['dpd_warehouse'] ) ) ) ); // phpcs:ignore
			$correct_phone_r = $dial_code_helper->separate_phone_number_from_country_code( $warehouse_info['phone'], $warehouse_info['country'] );

			$data['dpd_collect_recipient_name']                 = $this->custom_length( $warehouse_info['contact_person'], 70 );
			$data['dpd_collect_recipient_street_address']       = $this->custom_length( $warehouse_info['address'], 35 );
			$data['dpd_collect_recipient_postcode']             = preg_replace( '/[^0-9,.]/', '', $warehouse_info['postcode'] );
			$data['dpd_collect_recipient_city']                 = $this->custom_length( $warehouse_info['city'], 25 );
			$data['dpd_collect_recipient_country']              = $warehouse_info['country'];
			$data['dpd_collect_recipient_contact_phone_number'] = $correct_phone_r['dial_code'] . $correct_phone_r['phone_number'];
			$data['dpd_collect_recipient_contact_email']        = $this->custom_length( get_bloginfo( 'admin_email' ), 30 );
		}

		$this->dpd_order_collection_request( $data, false, false );
	}

	/**
	 * Get terminals.
	 *
	 * @param boolean $country Country.
	 *
	 * @return array
	 */
	private function get_terminals( $country = false ) {
		global $wpdb;

		if ( $country ) {
			$terminals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE country = %s ORDER BY city", $country ) );
		} else {
			$terminals = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dpd_terminals ORDER BY city" );
		}

		return $terminals;
	}

	/**
	 * Custom length.
	 *
	 * @param string $string String.
	 * @param string $length Length.
	 *
	 * @return string
	 */
	private function custom_length( $string, $length ) {
		if ( strlen( $string ) <= $length ) {
			return $string;
		} else {
			return substr( $string, 0, $length );
		}
	}
}
