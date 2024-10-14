<?php
/**
 * Dpd Parcels.
 *
 * @category Admin
 * @package  Dpd
 * @author   DPD
 */

/**
 * DPD_Parcels class.
 *
 * @package    Dpd
 * @subpackage Dpd/admin
 * @author     DPD
 */
class DPD_Parcels extends WC_Shipping_Method {
	/**
	 * Min amount for free shipping.
	 *
	 * @var string
	 */
	public $free_min_amount = '';

	/**
	 * Price calculation type.
	 *
	 * @var string
	 */
	public $type = 'order';

	/**
	 * Price cost rates.
	 *
	 * @var string
	 */
	public $cost_rates = '';

	/**
	 * Terminal field value.
	 *
	 * @var string
	 */
	public $terminal_field_value = '';

	/**
	 * DpdShippingMethod constructor.
	 *
	 * @param int $instance_id Instance id.
	 */
	public function __construct( $instance_id = 0 ) {
		parent::__construct();

		$this->id                 = 'dpd_parcels';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'DPD Pickup points (parcel lockers and -shops)', 'woo-shipping-dpd-baltic' );
		$this->method_description = __( 'DPD Pickup points shipping method', 'woo-shipping-dpd-baltic' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->init();
	}

	/**
	 * Init your settings.
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		$this->init_form_fields();
		$this->init_settings();

		$this->title           = $this->get_option( 'title', $this->method_title );
		$this->tax_status      = $this->get_option( 'tax_status' );
		$this->cost            = $this->get_option( 'cost' );
		$this->free_min_amount = $this->get_option( 'free_min_amount', '' );

		$this->type       = $this->get_option( 'type', 'order' );
		$this->cost_rates = $this->get_option( 'cost_rates' );

		$this->terminal_field_name = 'wc_shipping_' . $this->id . '_terminal';

		$this->i18n_selected_terminal = esc_html__( 'Selected Pickup Point:', 'woo-shipping-dpd-baltic' );
	}

	/**
	 * Init actions and filters.
	 *
	 * @return void
	 */
	public function init_actions_and_filters() {
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action( 'woocommerce_review_order_after_shipping', array( $this, 'review_order_after_shipping' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_save_order_terminal' ), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_selected_terminal' ), 10, 2 );

		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'show_selected_terminal_in_order_details' ), 20, 3 );

		if ( is_admin() ) {
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'show_selected_terminal' ), 20 );
			add_filter( 'woocommerce_admin_order_preview_get_order_details', array( $this, 'show_selected_terminal_in_order_preview' ), 20, 2 );
		}
	}

	/**
	 * Define settings field for this shipping.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title'           => array(
				'title'       => __( 'Method title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'DPD Pickup points', 'woo-shipping-dpd-baltic' ),
				'desc_tip'    => true,
			),
			'tax_status'      => array(
				'title'   => __( 'Tax status', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => array(
					'taxable' => __( 'Taxable', 'woocommerce' ),
					'none'    => _x( 'None', 'Tax status', 'woocommerce' ),
				),
			),
			'cost'            => array(
				'title'       => __( 'Cost', 'woocommerce' ),
				'type'        => 'text',
				'placeholder' => '',
				'description' => '',
				'default'     => '0',
				'desc_tip'    => true,
			),
			'free_min_amount' => array(
				'title'       => __( 'Minimum order amount for free shipping', 'woo-shipping-dpd-baltic' ),
				'type'        => 'price',
				'placeholder' => '',
				'description' => __( 'Users have to spend this amount to get free shipping.', 'woo-shipping-dpd-baltic' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'type'            => array(
				'title'   => __( 'Calculation type', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'order',
				'options' => array(
					'order'  => __( 'Per order', 'woo-shipping-dpd-baltic' ),
					'weight' => __( 'Weight based', 'woo-shipping-dpd-baltic' ),
				),
			),
			'cost_rates'      => array(
				'title'       => __( 'Rates', 'woo-shipping-dpd-baltic' ),
				'type'        => 'textarea',
				'placeholder' => '',
				'description' => __( 'Example: 5:10.00,7:12.00 Weight:Price,Weight:Price, etc...', 'woo-shipping-dpd-baltic' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Get setting form fields for instances of this shipping method within zones.
	 *
	 * @return array
	 */
	public function get_instance_form_fields() {
		if ( is_admin() ) {
			wc_enqueue_js(
				'jQuery( function( $ ) {
					function wc' . $this->id . "ShowHideRatesField( el ) {
						var form = $( el ).closest( 'form' );
						var ratesField = $( '#woocommerce_" . $this->id . "_cost_rates', form ).closest( 'tr' );
						if ( 'weight' !== $( el ).val() || '' === $( el ).val() ) {
							ratesField.hide();
						} else {
							ratesField.show();
						}
					}

					$( document.body ).on( 'change', '#woocommerce_" . $this->id . "_type', function() {
						wc" . $this->id . "ShowHideRatesField( this );
					});

					// Change while load.
					$( '#woocommerce_" . $this->id . "_type' ).change();
					$( document.body ).on( 'wc_backbone_modal_loaded', function( evt, target ) {
						if ( 'wc-modal-shipping-method-settings' === target ) {
							wc" . $this->id . "ShowHideRatesField( $( '#wc-backbone-modal-dialog #woocommerce_" . $this->id . "_type', evt.currentTarget ) );
						}
					} );
				});"
			);
		}

		return parent::get_instance_form_fields();
	}

	/**
	 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
	 *
	 * @access public
	 *
	 * @param mixed $package Package.
	 *
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		$has_met_min_amount = false;
		$cost               = $this->cost;
		$weight             = WC()->cart ? WC()->cart->get_cart_contents_weight() : 0;

		if ( WC()->cart && ! empty( $this->free_min_amount ) && $this->free_min_amount > 0 ) {
			$total = WC()->cart->get_displayed_subtotal();

			if ( WC()->cart->display_prices_including_tax() ) {
				$total = round( $total - ( WC()->cart->get_discount_total() + WC()->cart->get_discount_tax() ), wc_get_price_decimals() );
			} else {
				$total = round( $total - WC()->cart->get_discount_total(), wc_get_price_decimals() );
			}

			if ( $total >= $this->free_min_amount ) {
				$has_met_min_amount = true;
			}
		}

		if ( 'weight' === $this->type ) {
			$rates = explode( ',', $this->cost_rates );

			foreach ( $rates as $rate ) {
				$data = explode( ':', $rate );

				if ( $data[0] >= $weight ) {
					if ( isset( $data[1] ) ) {
						$cost = str_replace( ',', '.', $data[1] );
					}

					break;
				}
			}
		}

		$rate = array(
			'id'      => $this->get_rate_id(),
			'label'   => $this->title,
			'cost'    => $has_met_min_amount ? 0 : $cost,
			'package' => $package,
		);

		$this->add_rate( $rate );

		do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );
	}

	/**
	 * Is available.
	 *
	 * @param array $package Package.
	 *
	 * @return bool
	 */
	public function is_available( $package ) {
	    if ( $this->checkDoesNotFitInTerminal( WC()->cart->get_cart() ) ) {
	        return false;
        }
		$available               = $this->is_enabled();
		$total_weight_of_cart    = array();
		$dpd_parcel_distribution = get_option( 'dpd_parcel_distribution', '3' );
		$shipping_country        = WC()->customer === null ? strtolower( get_option( 'woocommerce_default_country' ) ) : strtolower( WC()->customer->get_shipping_country() );
		$shop_weight_unit        = get_option( 'woocommerce_weight_unit' );

		$countries        = array( 'lt', 'lv', 'ee', 'dk', 'be', 'fi', 'fr', 'de', 'lu', 'nl', 'es', 'se', 'ch', 'gb', 'pl', 'at', 'cz', 'si', 'sk', 'hu', 'ie' );
		$countries_baltic = array( 'lt', 'lv', 'ee' );
		$countries_pt     = 'pt';

		switch ( $shop_weight_unit ) {
			case 'oz':
				$divider = 35.274;
				break;

			case 'lbs':
				$divider = 2.20462;
				break;

			case 'g':
				$divider = 1000;
				break;

			default:
				$divider = 1;
				break;
		}

		if ( in_array( $shipping_country, $countries_baltic ) ) {
			$max_pudo_weight = 31.5 * $divider;
		} elseif ( in_array( $shipping_country, $countries ) ) {
			$max_pudo_weight = 20 * $divider;
		} elseif ( $shipping_country === $countries_pt ) {
			$max_pudo_weight = 10 * $divider;
		} else {
			return false;
		}

		switch ( $dpd_parcel_distribution ) {
			case 1:
				$total_weight_of_cart = WC()->cart === null ? 0 : WC()->cart->get_cart_contents_weight();
				if ( $total_weight_of_cart <= $max_pudo_weight ) {
					return $available;
					break;
				} else {
					return false;
					break;
				}
			case 2:
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					$temp_weight = $cart_item['data']->get_weight() * $cart_item['quantity'];
					array_push( $total_weight_of_cart, $temp_weight );
				}
				if ( !empty( $total_weight_of_cart ) && ( max( $total_weight_of_cart ) <= $max_pudo_weight ) ) {
					return $available;
					break;
				} else {
					return false;
					break;
				}
			case 3:
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					for ( $i = 0; $i < $cart_item['quantity']; $i++ ) {
						$total_weight_of_cart[] = $cart_item['data']->get_weight();
					}
				}
				if ( !empty($total_weight_of_cart) && (max( $total_weight_of_cart ) <= $max_pudo_weight) ) {
					return $available;
					break;
				} else {
					return false;
					break;
				}
			default:
				return $available;
			break;
		}

	}

    /**
     * @param $products
     * @return bool
     */
	public function checkDoesNotFitInTerminal( $products ) {
	    if ( count( $products ) ) {
            foreach ( $products as $cart_item ) {
                if ( isset( $cart_item['product_id'] ) ) {
                    $doesNotFitInTerminal = get_post_meta( $cart_item['product_id'], DPD_DOES_NOT_FIT_IN_TERMINAL, true );
                    if ( $doesNotFitInTerminal ) {
                        return true;
                    }
                }
            }
        }
	    return false;
    }

	/**
	 * Review order after shipping.
	 */
	public function review_order_after_shipping() {
        global $is_hook_executed;

		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
        dpd_debug_log("FRONT-STORE: CHECKOUT PROCESS - chosen_shipping_method:", $chosen_shipping_methods);

		if ( ! empty( $chosen_shipping_methods ) && substr( $chosen_shipping_methods[0], 0, strlen( $this->id ) ) === $this->id ) {
//            $limit = 100;
            $limit = 500;
            $page = 1;
            $offset = $limit * ($page - 1);
			$selected_terminal = WC()->session->get( $this->terminal_field_name ) ?: '';
            dpd_debug_log(sprintf("FRONT-STORE: CHECKOUT PROCESS - selected_terminal: %s", $selected_terminal));
            $selected_terminal_name = '';

			if (!empty($selected_terminal)) {
                $selected_terminal_name = $this->get_terminal_name($selected_terminal);
            }
			$has_more = $limit + 1;


            $terminals_pagination = $this->get_terminals_pagination( WC()->customer->get_shipping_country(), $has_more, $offset );

            if ( count( $terminals_pagination ) > $limit ) {
            	array_pop( $terminals_pagination );
            } else {
	            $page = -1;
            }


            if (WC()->customer->get_shipping_country()) {
                $country_new = WC()->customer->get_shipping_country();
            } else {
                $country_new = false;
            }


            $terminals_new = $this->get_terminals($country_new);

			$template_data = array(
				'terminals'  => $this->get_grouped_terminals( $terminals_pagination ),
//                'terminals'  => $this->get_grouped_terminals( $terminals_new ),
				'field_name' => $this->terminal_field_name,
				'field_id'   => $this->terminal_field_name,
				'selected'   => $selected_terminal ? $selected_terminal : '',
                'selected_terminal_name' => $selected_terminal_name,
                'load_more_page' => $page,
			);

			do_action( $this->id . '_before_terminals' );

			$google_map_api = get_option( 'dpd_google_map_key' );

			if ( !$this->checkDoesNotFitInTerminal( WC()->cart->get_cart() ) && ! $is_hook_executed ) {
                if ( '' != $google_map_api ) {
                    $template_data['selected_name'] = $this->get_terminal_name( $selected_terminal );

                    wc_get_template( 'checkout/form-shipping-dpd-terminals-with-map.php', $template_data );
                } else {
                    wc_get_template( 'checkout/form-shipping-dpd-terminals.php', $template_data );
                }

                $is_hook_executed = true;
            }

			do_action( $this->id . '_after_terminals' );
		}
	}

	/**
	 * Review order after shipping.
	 *
	 * @param int   $order_id Order id.
	 * @param array $data Data.
	 */
	public function checkout_save_order_terminal( $order_id, $data ) {
		if ( isset( $this->terminal_field_value ) ) {
            dpd_update_order_meta( $order_id, $this->terminal_field_name, $this->terminal_field_value );
			$this->save_order_terminal_field_name( $order_id );
		}
	}

    /**
     * Save order terminal field name.
     *
     * @param int $order_id Order id.
     */
    public function save_order_terminal_field_name( $order_id ) {
        $terminal_name = $this->get_terminal_name( $this->terminal_field_value );
        $order_terminal_name = $this->terminal_field_name . '_name';
        dpd_update_order_meta( $order_id, $order_terminal_name, $terminal_name );
    }

	/**
	 * Validate selected terminal.
	 *
	 * @param array $posted Posted.
	 * @param array $errors Errors.
	 */
	public function validate_selected_terminal( $posted, $errors ) {
		// Check if terminal was submitted.
		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
			return;
		}

		$this->terminal_field_value = isset( $_POST[ $this->terminal_field_name ] )
			? sanitize_text_field( wp_unslash( $_POST[ $this->terminal_field_name ] ) )
			: null;

		if ( isset( $_POST[ $this->terminal_field_name ] ) && '' == $_POST[ $this->terminal_field_name ] ) {
			// Be sure shipping method was posted.
			if ( isset( $posted['shipping_method'] ) && is_array( $posted['shipping_method'] ) ) {
				// Check if it is this shipping method.
				if ( substr( $posted['shipping_method'][0], 0, strlen( $this->id ) ) === $this->id ) {
					$errors->add( 'shipping', __( 'Please select a Pickup Point', 'woo-shipping-dpd-baltic' ) );
				}
			}
		}
	}

	/**
	 * Review order after shipping.
	 *
	 * @param mixed $order Order.
	 */
	public function show_selected_terminal( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $order->has_shipping_method( $this->id ) ) {
			$terminal_id   = $this->get_order_terminal( $order->get_id() );
			$terminal_name = dpd_get_order_meta( $order->get_id(), $this->terminal_field_name . '_name', true );
			$terminal_name = $terminal_name ?: $this->get_terminal_name( $terminal_id );

			$terminal  = '<div class="selected_terminal">';
			$terminal .= '<div><strong>' . $this->i18n_selected_terminal . '</strong></div>';
			$terminal .= esc_html( $terminal_name );
			$terminal .= '</div>';

			echo wp_kses( $terminal, wp_kses_allowed_html( 'post' ) );
		}
	}

	/**
	 * Show selected terminal in order preview.
	 *
	 * @param array $order_details Order details.
	 * @param array $order Order.
	 *
	 * @return array
	 */
	public function show_selected_terminal_in_order_preview( $order_details, $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $order->has_shipping_method( $this->id ) ) {
			$terminal_id   = $this->get_order_terminal( $order->get_id() );
			$terminal_name = $this->get_terminal_name( $terminal_id );

			if ( isset( $order_details['shipping_via'] ) ) {
				$order_details['shipping_via'] = sprintf( '%s: %s', $order->get_shipping_method(), esc_html( $terminal_name ) );
			}
		}

		return $order_details;
	}

	/**
	 * Show selected terminal in order details.
	 *
	 * @param array $total_rows Total rows.
	 * @param array $order Order.
	 * @param mixed $tax_display Tax display.
	 *
	 * @return array
	 */
	public function show_selected_terminal_in_order_details( $total_rows, $order, $tax_display ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $order->has_shipping_method( $this->id ) ) {
			$terminal_id   = $this->get_order_terminal( $order->get_id() );
			$terminal_name = $this->get_terminal_name( $terminal_id );

			// $total_rows['shipping_terminal'] = [
			// 'label' => $this->i18n_selected_terminal,
			// 'value' => $terminal_name
			// ];

			$this->array_insert(
				$total_rows,
				'shipping',
				array(
					'shipping_terminal' => array(
						'label' => $this->i18n_selected_terminal,
						'value' => $terminal_name,
					),
				)
			);
		}

		return $total_rows;
	}

	/**
	 * Show selected terminal in order details.
	 *
	 * @param array $terminals Terminals.
	 *
	 * @return array
	 */
	public function get_grouped_terminals( $terminals ) {


		$grouped_terminals = array();

		foreach ( $terminals as $terminal ) {
			if ( ! isset( $grouped_terminals[ $terminal->city ] ) && isset( $terminal->status ) && $terminal->status == '1' ) {
				$grouped_terminals[ $terminal->city ] = array();
			}

			if( isset( $terminal->status ) && $terminal->status == '1' ) {
				$grouped_terminals[ $terminal->city ][] = $terminal;
			}
		}

		ksort( $grouped_terminals );

		foreach ( $grouped_terminals as $group => $terminals ) {
			foreach ( $terminals as $terminal_key => $terminal ) {
				$grouped_terminals[ $group ][ $terminal_key ]->name = $this->get_formatted_terminal_name( $terminal );
			}
		}

		return $grouped_terminals;
	}

	/**
	 * Get terminals.
	 *VIRŠI
	 * @param boolean $country Country.
	 *
	 * @return array
	 */
	public function get_terminals( $country = false ) {
		global $wpdb;

        $countries = get_option( 'dpd_parcels_countries', array( 'LT', 'LV', 'EE' ) );

		if ( $country ) {
		    if (!in_array($country, $countries)) {
		        return [];
            }
			$terminals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE country = %s ORDER BY company", $country ) );
		} else {
			$terminals = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dpd_terminals ORDER BY company" );
		}

		return $terminals;
	}

    /**
     * Get terminals pagination.
     *
     * @param boolean $country Country.
     *
     * @return array
     */
    public function get_terminals_pagination( $country = false , $items_per_page = 10, $offset = 0 ) {
        global $wpdb;
        $countries = get_option( 'dpd_parcels_countries', array( 'LT', 'LV', 'EE' ) );

        dpd_debug_log(sprintf("FRONT-STORE: CHECKOUT PROCESS - get_terminals_pagination for country: %s", $country));
        dpd_debug_log("FRONT-STORE: CHECKOUT PROCESS - countries in settings: ", $countries);

        if ( $country ) {

            if (!in_array($country, $countries)) {
                dpd_debug_log("FRONT-STORE: CHECKOUT PROCESS - no selected country is matching with countries in settings");
                return [];
            }


//            $terminals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE country = %s AND status = 1 ORDER BY company LIMIT %d OFFSET %d", $country, 300, 300 ) );

            $terminals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE country = %s AND status = 1 ORDER BY company LIMIT %d OFFSET %d", $country, $items_per_page, $offset ) );
            dpd_debug_log(sprintf("FRONT-STORE: CHECKOUT PROCESS - total terminals match with selected country: %d", is_countable($terminals) ? count( $terminals ) : -1));
        } else {
            $terminals = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE status = 1 ORDER BY company LIMIT %d OFFSET %d", $items_per_page, $offset));
            dpd_debug_log(sprintf("FRONT-STORE: CHECKOUT PROCESS - total terminals are NOT matching with selected country: %d", is_countable($terminals) ? count( $terminals ) : -1));

        }

        return $terminals;
    }

	/**
	 * Get order terminal.
	 *
	 * @param int $order_id Order id.
	 *
	 * @return object
	 */
	public function get_order_terminal( $order_id ) {
		return dpd_get_order_meta( $order_id, $this->terminal_field_name, true );
	}

	/**
	 * Get terminal name.
	 *
	 * @param int $terminal_id Terminal id.
	 *
	 * @return mixed
	 */
	public function get_terminal_name( $terminal_id ) {
		$terminals = $this->get_terminals();

		foreach ( $terminals as $terminal ) {
			if ( $terminal->parcelshop_id == $terminal_id ) {
				return $this->get_formatted_terminal_name( $terminal );

				break;
			}
		}

		return false;
	}

	/**
	 * Get formatted terminal name.
	 *
	 * @param object $terminal Terminal.
	 *
	 * @return string
	 */
	public function get_formatted_terminal_name( $terminal ) {
		return $terminal->company . ', ' . $terminal->street;
	}

	/**
	 * Array insert.
	 *
	 * @param array $array Array.
	 * @param mixed $position Position.
	 * @param mixed $insert Insert.
	 */
	private function array_insert( &$array, $position, $insert ) {
		if ( is_int( $position ) ) {
			array_splice( $array, $position, 0, $insert );
		} else {
			$pos   = array_search( $position, array_keys( $array ) );
			$array = array_merge(
				array_slice( $array, 0, $pos + 1 ),
				$insert,
				array_slice( $array, $pos + 1 )
			);
		}
	}
}
