<?php
/**
 * Dpd Home Delivery Sat.
 *
 * @category Admin
 * @package  Dpd
 * @author   DPD
 */

/**
 * DPD_Home_Delivery_Sat class.
 *
 * @package    Dpd
 * @subpackage Dpd/admin
 * @author     DPD
 */
class DPD_Home_Delivery_Sat extends WC_Shipping_Method {
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
	 * DpdShippingMethod constructor.
	 *
	 * @param int $instance_id Instance id.
	 */
	public function __construct( $instance_id = 0 ) {
		parent::__construct();

		$this->id                 = 'dpd_sat_home_delivery';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'DPD home delivery on Saturday', 'woo-shipping-dpd-baltic' );
		$this->method_description = __( 'DPD home delivery on Saturday shipping method', 'woo-shipping-dpd-baltic' );
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
	}

	/**
	 * Init actions and filters.
	 */
	public function init_actions_and_filters() {
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Define settings field for this shipping.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title'             => array(
				'title'       => __( 'Method title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'DPD home delivery on Saturdays', 'woo-shipping-dpd-baltic' ),
				'desc_tip'    => true,
			),
			'tax_status'        => array(
				'title'   => __( 'Tax status', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => array(
					'taxable' => __( 'Taxable', 'woocommerce' ),
					'none'    => _x( 'None', 'Tax status', 'woocommerce' ),
				),
			),
			'cost'              => array(
				'title'       => __( 'Cost', 'woocommerce' ),
				'type'        => 'text',
				'placeholder' => '',
				'description' => '',
				'default'     => '0',
				'desc_tip'    => true,
			),
			'free_min_amount'   => array(
				'title'       => __( 'Minimum order amount for free shipping', 'woo-shipping-dpd-baltic' ),
				'type'        => 'price',
				'placeholder' => '',
				'description' => __( 'Users have to spend this amount to get free shipping.', 'woo-shipping-dpd-baltic' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'type'              => array(
				'title'   => __( 'Calculation type', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'order',
				'options' => array(
					'order'  => __( 'Per order', 'woo-shipping-dpd-baltic' ),
					'weight' => __( 'Weight based', 'woo-shipping-dpd-baltic' ),
				),
			),
			'cost_rates'        => array(
				'title'       => __( 'Rates', 'woo-shipping-dpd-baltic' ),
				'type'        => 'textarea',
				'placeholder' => '',
				'description' => __( 'Example: 5:10.00,7:12.00 Weight:Price,Weight:Price, etc...', 'woo-shipping-dpd-baltic' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			array(
				'title' => 'Monday',
				'type'  => 'title',
			),
			'monday'            => array(
				'title'   => __( 'Enabled', 'woo-shipping-dpd-baltic' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Yes', 'woo-shipping-dpd-baltic' ),
			),
			'monday_am_from'    => array(
				'title'   => __( 'Morning from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '00:00',
			),
			'monday_am_to'      => array(
				'title'   => __( 'Morning to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '09:30',
			),
			'monday_pm_from'    => array(
				'title'   => __( 'Noon from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '15:00',
			),
			'monday_pm_to'      => array(
				'title'   => __( 'Noon to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '23:59',
			),
			array(
				'title' => 'Tuesday',
				'type'  => 'title',
			),
			'tuesday'           => array(
				'title'   => __( 'Enabled', 'woo-shipping-dpd-baltic' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Yes', 'woo-shipping-dpd-baltic' ),
			),
			'tuesday_am_from'   => array(
				'title'   => __( 'Morning from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '00:00',
			),
			'tuesday_am_to'     => array(
				'title'   => __( 'Morning to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '09:30',
			),
			'tuesday_pm_from'   => array(
				'title'   => __( 'Noon from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '15:00',
			),
			'tuesday_pm_to'     => array(
				'title'   => __( 'Noon to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '23:59',
			),
			array(
				'title' => 'Wednesday',
				'type'  => 'title',
			),
			'wednesday'         => array(
				'title'   => __( 'Enabled', 'woo-shipping-dpd-baltic' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Yes', 'woo-shipping-dpd-baltic' ),
			),
			'wednesday_am_from' => array(
				'title'   => __( 'Morning from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '00:00',
			),
			'wednesday_am_to'   => array(
				'title'   => __( 'Morning to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '09:30',
			),
			'wednesday_pm_from' => array(
				'title'   => __( 'Noon from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '15:00',
			),
			'wednesday_pm_to'   => array(
				'title'   => __( 'Noon to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '23:59',
			),
			array(
				'title' => 'Thursday',
				'type'  => 'title',
			),
			'thursday'          => array(
				'title'   => __( 'Enabled', 'woo-shipping-dpd-baltic' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Yes', 'woo-shipping-dpd-baltic' ),
			),
			'thursday_am_from'  => array(
				'title'   => __( 'Morning from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '00:00',
			),
			'thursday_am_to'    => array(
				'title'   => __( 'Morning to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '09:30',
			),
			'thursday_pm_from'  => array(
				'title'   => __( 'Noon from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '15:00',
			),
			'thursday_pm_to'    => array(
				'title'   => __( 'Noon to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '23:59',
			),
			array(
				'title' => 'Friday',
				'type'  => 'title',
			),
			'friday'            => array(
				'title'   => __( 'Enabled', 'woo-shipping-dpd-baltic' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Yes', 'woo-shipping-dpd-baltic' ),
			),
			'friday_am_from'    => array(
				'title'   => __( 'Morning from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '00:00',
			),
			'friday_am_to'      => array(
				'title'   => __( 'Morning to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '09:30',
			),
			'friday_pm_from'    => array(
				'title'   => __( 'Noon from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '15:00',
			),
			'friday_pm_to'      => array(
				'title'   => __( 'Noon to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '23:59',
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

		$available                      = false;
		$cart_weight                    = WC()->cart === null ? 0 : WC()->cart->get_cart_contents_weight();
		$shipping_country               = WC()->customer === null ? strtolower( get_option( 'woocommerce_default_country' ) ) : strtolower( WC()->customer->get_shipping_country() );
		$current_day_name               = strtolower( current_time( 'l' ) );
		$current_time                   = current_time( 'H:i' );
		$option_day_name                = $this->get_option( $current_day_name );
		$current_day_enabled            = ! empty( $option_day_name ) && 'yes' === $option_day_name;
		$am_from                        = $this->get_option( $current_day_name . '_am_from', '00:00' );
		$am_to                          = $this->get_option( $current_day_name . '_am_to', '09:30' );
		$pm_from                        = $this->get_option( $current_day_name . '_pm_from', '15:00' );
		$pm_to                          = $this->get_option( $current_day_name . '_pm_to', '23:59' );
		$global_avail                   = parent::is_available( $package );
		$dpd_sat_home_delivery_limit_ee = 31.5;

		$cart_weight_kg = dpd_baltic_weight_in_kg( $cart_weight );

		if ( 'ee' === $shipping_country && $cart_weight_kg > $dpd_sat_home_delivery_limit_ee ) {
			return false;
		}

		if ( ! in_array( $shipping_country, array( 'lt', 'lv', 'ee' ) ) ) {
			return false;
		}

		$am_avail = ( ( $current_time > $am_from ) && ( $current_time < $am_to ) );
		$pm_avail = ( ( $current_time > $pm_from ) && ( $current_time < $pm_to ) );

		if ( $current_day_enabled && $am_avail && $global_avail ) {
			$available = true;
		}

		if ( $current_day_enabled && $pm_avail && $global_avail ) {
			$available = true;
		}

		return $available;
	}
}
