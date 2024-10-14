<?php
/**
 * Dpd Home Delivery.
 *
 * @category Admin
 * @package  Dpd
 * @author   DPD
 */

/**
 * DPD_Home_Delivery class.
 *
 * @package    Dpd
 * @subpackage Dpd/admin
 * @author     DPD
 */
class DPD_Home_Delivery extends WC_Shipping_Method {
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
	 * Shifts field name.
	 *
	 * @var string
	 */
	protected $shifts_field_name;

	/**
	 * DpdShippingMethod constructor.
	 *
	 * @param int $instance_id Instance id.
	 */
	public function __construct( $instance_id = 0 ) {
		parent::__construct();

		$this->id                 = 'dpd_home_delivery';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'DPD home delivery', 'woo-shipping-dpd-baltic' );
		$this->method_description = __( 'DPD home delivery shipping method', 'woo-shipping-dpd-baltic' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->shifts_field_name = 'wc_shipping_' . $this->id . '_shifts';

		$this->init();
	}

	/**
	 * Init your settings
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

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Init actions and filters.
	 */
	public function init_actions_and_filters() {
		add_action( 'woocommerce_review_order_after_shipping', array( $this, 'review_order_after_shipping' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_save_order_timeshifts' ), 10, 2 );

		if ( is_admin() ) {
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'show_selected_timeshift' ), 20 );
			add_filter( 'woocommerce_admin_order_preview_get_order_details', array( $this, 'show_selected_timeshift_in_order_preview' ), 20, 2 );
		}
	}

	/**
	 * Define settings field for this shipping
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title'           => array(
				'title'       => __( 'Method title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'DPD home delivery', 'woo-shipping-dpd-baltic' ),
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
	 * Woocommerce_review_order_after_shipping action
	 * return available time shifts based on customer city
	 */
	public function review_order_after_shipping() {

		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		if ( ! empty( $chosen_shipping_methods ) && substr( $chosen_shipping_methods[0], 0, strlen( $this->id ) ) === $this->id ) {

			$shipping_city    = sanitize_title( WC()->customer->get_shipping_city() );
			$shipping_country = strtolower( WC()->customer->get_shipping_country() );
			$base_country     = wc_get_base_location()['country'] ? wc_get_base_location()['country'] : '';
			$base_country     = strtolower( $base_country );

			$countries_lt = array(
				'Vilnius',
				'Kaunas',
				'Klaipėda',
				'Šiauliai',
				'Panevežys',
				'Alytus',
				'Marijampolė',
				'Telšiai',
				'Tauragė',
			);
			$countries_lv = array(
				'Rīga',
				'Talsi',
				'Liepāja',
				'Jelgava',
				'Jēkabpils',
				'Daugavpils',
				'Rēzekne',
				'Valmiera',
				'Gulbene',
				'Cēsis',
				'Saldus',
				'Ventspils',
			);

			$countries_lt_sanitized = array_map(
				function ( $el ) {
					return sanitize_title( $el );
				},
				$countries_lt
			);

			$countries_lv_sanitized = array_map(
				function ( $el ) {
					return sanitize_title( $el );
				},
				$countries_lv
			);

			$select_data = array();

			if ( 'lt' === $base_country && 'lt' === $shipping_country && in_array( $shipping_city, $countries_lt_sanitized ) ) {
				$select_data = array( '08:00 - 18:00', '08:00 - 14:00', '14:00 - 18:00', '18:00 - 22:00' );
			}

			if ( 'lv' === $base_country && 'lv' === $shipping_country && in_array( $shipping_city, $countries_lv_sanitized ) ) {
				$select_data = array( '08:00 - 18:00', '18:00 - 22:00' );
			}

			$selected_terminal = WC()->session->get( $this->shifts_field_name );

			$template_data = array(
				'shifts'     => $select_data,
				'field_name' => $this->shifts_field_name,
				'selected'   => $selected_terminal ? $selected_terminal : '',
			);

			if ( ! empty( $select_data ) ) {
				do_action( $this->id . '_before_timeframes' );
				wc_get_template( 'checkout/time-frames.php', $template_data );
				do_action( $this->id . '_after_timeframes' );
			}
		}

	}

	/**
	 * Checkout save order timeshifts.
	 *
	 * @param int $order_id Order id.
	 */
	public function checkout_save_order_timeshifts( $order_id ) {

		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
			return;
		}
		if ( isset( $_POST[ $this->shifts_field_name ] ) ) {
			$selected_shift = wc_clean( sanitize_text_field( wp_unslash( $_POST[ $this->shifts_field_name ] ) ) );
			$selected_shift = $selected_shift ? $selected_shift : false;

			if ( $selected_shift ) {
                dpd_update_order_meta( $order_id, $this->shifts_field_name, filter_var( $selected_shift, FILTER_SANITIZE_STRING ) );
			}
		}

	}

	/**
	 * Show selected timeshift.
	 *
	 * @param int $order Order.
	 */
	public function show_selected_timeshift( $order ) {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $order->has_shipping_method( $this->id ) ) {
			echo '<p>';
			echo '<strong>Deliver between:</strong><br>';
			echo esc_html( dpd_get_order_meta( $order->get_id(), 'wc_shipping_dpd_home_delivery_shifts', true ) );
			echo '</p>';
		}

	}

	/**
	 * Show selected timeshift in order preview.
	 *
	 * @param array $order_details Order details.
	 * @param array $order Order.
	 *
	 * @return array
	 */
	public function show_selected_timeshift_in_order_preview( $order_details, $order ) {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $order->has_shipping_method( $this->id ) ) {

			$shift_between = __( 'Deliver between: ', 'woo-shipping-dpd-baltic' ) . dpd_get_order_meta( $order->get_id(), 'wc_shipping_dpd_home_delivery_shifts', true );

			if ( isset( $order_details['shipping_via'] ) ) {
				$order_details['shipping_via'] = sprintf( '%s: %s', $order->get_shipping_method(), esc_html( $shift_between ) );
			}
		}

		return $order_details;

	}
}
