<?php
/**
 * Dpd Admin.
 *
 * @category Admin
 * @package  Dpd
 * @author   DPD
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Dpd
 * @subpackage Dpd/admin
 * @author     DPD
 */
class Dpd_Admin {

    public const HOME             = 'dpd_home_delivery';
    public const HOME_SAT         = 'dpd_sat_home_delivery';
    public const PARCELS          = 'dpd_parcels';
    public const PARCELS_SAME_DAY = 'dpd_sameday_parcels';
    public const SAME_DAY         = 'dpd_sameday_delivery';

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * The version static of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version static of this plugin.
     */
    private static $version_static;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     *
     * @param      string $plugin_name The name of this plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name    = $plugin_name;
        $this->version        = $version;
        self::$version_static = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/dpd-admin.css', array(), $this->version, 'all' );
        wp_enqueue_style( 'thickbox' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'thickbox' );
        wp_enqueue_script( 'repeater', plugin_dir_url( __FILE__ ) . 'js/jquery.repeater.min.js', array(), $this->version, true );
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/dpd-admin-dist.js',
            array(
                'jquery',
                'jquery-ui-datepicker',
                'repeater',
            ),
            $this->version,
            true
        );
        wp_localize_script(
            $this->plugin_name,
            'wc_dpd_baltic',
            array(
                'i18n'             => array(
                    'request_courier' => __( 'Request DPD courier', 'woo-shipping-dpd-baltic' ),
                    'close_manifest'  => __( 'Close DPD manifest', 'woo-shipping-dpd-baltic' ),
                ),
                'admin_ajax_nonce' => wp_create_nonce( 'admin-nonce' ),
            )
        );
    }

    /**
     * Get settings pages.
     *
     * @param array $settings Settings.
     *
     * @return mixed
     */
    public function get_settings_pages( $settings ) {
        $settings[] = include 'settings/class-dpd-settings-general.php';

        return $settings;
    }

    /**
     * Http client.
     *
     * @param string $endpoint Endpoint.
     * @param array  $params Params.
     *
     * @return mixed
     */
    public static function http_client( $endpoint, $params = array() ) {
        $service_provider = get_option( 'dpd_api_service_provider' );
        $test_mode        = get_option( 'dpd_test_mode' );

        switch ( $service_provider ) {
            case 'lt':
                $service_url      = 'https://integracijos.dpd.lt/';
                $test_service_url = 'https://lt.integration.dpd.eo.pl/';
                break;
            case 'lv':
                $service_url      = 'https://integration.dpd.lv/';
                $test_service_url = 'https://lv.integration.dpd.eo.pl/';
                break;
            case 'ee':
                $service_url      = 'https://integration.dpd.ee/';
                $test_service_url = 'https://ee.integration.dpd.eo.pl/';
                break;
            default:
                $service_url      = 'https://integracijos.dpd.lt/';
                $test_service_url = 'https://lt.integration.dpd.eo.pl/';
        }

        $service_url      .= 'ws-mapper-rest/';
        $test_service_url .= 'ws-mapper-rest/';

        $dpd_service_url = ! empty( $test_mode ) && 'yes' === $test_mode ? $test_service_url : $service_url;
        $dpd_username    = get_option( 'dpd_api_username' );
        $dpd_pass        = get_option( 'dpd_api_password' );

        $params['PluginVersion'] = self::$version_static;
        $params['EshopVersion']  = 'WordPress ' . get_bloginfo( 'version' ) . ', WooCommerce ' . WC()->version;

        if ( $dpd_service_url && $dpd_username && $dpd_pass ) {
            $post_url = $dpd_service_url . $endpoint . '?username=' . $dpd_username . '&password=' . $dpd_pass . '&' . http_build_query( $params );
            $post_args = array(
                'method'      => 'POST',
                'timeout'     => 60,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body'        => array(),
                'cookies'     => array(),
                'sslverify'   => false,
            );
            dpd_debug_log(sprintf("http-client - url: %s and payload", $post_url), $post_args);

            $response = wp_remote_post($post_url, $post_args);

            if ( is_wp_error( $response ) ) {
                dpd_debug_log(sprintf("http-client - response with error: %s", $response->get_error_message()));
                return $response->get_error_message();
            } else {
                $body = wp_remote_retrieve_body( $response );
                if ( 'parcelPrint_' === $endpoint || 'parcelManifestPrint_' === $endpoint || 'crImport_' === $endpoint ) {
                    return $body;
                }

                if ( 'pickupOrderSave_' === $endpoint ) {
                    if ( strcmp( substr( $body, 3, 4 ), 'DONE' ) == 0 ) {
                        return 'DONE|';
                    } else {
                        return $body;
                    }
                }
                return json_decode( $body );
            }
        }
    }

    /**
     * Update all parcels list.
     */
    public static function update_all_parcels_list() {
        global $wpdb;
        dpd_debug_log("Start update_all_parcels_list.................");
        $countries = get_option( 'dpd_parcels_countries', array( 'LT', 'LV', 'EE' ) );
        dpd_debug_log("Countries: ", $countries);

        if ( ! empty( $countries ) ) {
            $i    = 0;
            $time = time();

            foreach ( $countries as $country ) {
                wp_schedule_single_event( $time + ( $i * 15 ), 'dpd_parcels_country_update', array( $country ) );

                $i++;
            }
        }
    }

    /**
     * Get all parcels list.
     */
    public static function get_all_parcels_list() {
        global $wpdb;

        $countries = get_option( 'dpd_parcels_countries', array( 'LT', 'LV', 'EE' ) );

        if ( ! empty( $countries ) ) {
            $i    = 0;
            $time = time();

            foreach ( $countries as $country ) {
                wp_schedule_single_event( $time + ( $i * 120 ), 'dpd_parcels_country_update', array( $country ) );

                $i++;
            }
        }
    }

    /**
     * Country parcels list.
     *
     * @param string $country Country.
     */
    public static function country_parcels_list( $country ) {
        if ( (int) ini_get( 'max_execution_time' ) < 60 && in_array( $country, array( 'DE', 'FR' ) ) ) {
            dpd_debug_log(sprintf("get_parcels_list WITHOUT opening_hours by country: %s", $country));
            $data = self::get_parcels_list( $country, false );
        } else {
            dpd_debug_log(sprintf("get_parcels_list WITH opening_hours by country: %s", $country));
            $data = self::get_parcels_list( $country );
        }

        if ( $data ) {
            $totalFetchedParcelsByCountry = is_countable($data) ? count($data) : -1;
            dpd_debug_log(sprintf("http-client - total fetched parcels: %d", $totalFetchedParcelsByCountry));
            dpd_debug_log(sprintf("proceed to update_parcels_list for country: %s into database", $country));
            self::update_parcels_list( $data, $country );
        }
    }

    public function dpdAddShippingCustomField( ) {
        $currentPost = get_post();
        $checked = '';
        if ( $currentPost ) {
            $dpdFitInTerminal = get_post_meta( $currentPost->ID, DPD_DOES_NOT_FIT_IN_TERMINAL, true );
            if ( $dpdFitInTerminal ) {
                $checked = 'checked';
            }
        }
        ?>
        <div class="options_group">
            <p class="form-field shipping_class_field">
                <input type="checkbox" name="<?= DPD_DOES_NOT_FIT_IN_TERMINAL; ?>" id="<?= DPD_DOES_NOT_FIT_IN_TERMINAL; ?>" value="1" <?= $checked; ?>>
                <label for="<?= DPD_DOES_NOT_FIT_IN_TERMINAL; ?>"><?= __( "Doesn't fit in terminal", "woo-shipping-dpd-baltic" ); ?></label>
            </p>
        </div>
        <?php
    }

    /**
     * @param $product
     */
    public function dpdCustomSaveShippingCustomField( $product ) {
        if ( array_key_exists(DPD_DOES_NOT_FIT_IN_TERMINAL, $_POST ) ) {
            $dpdFitInTerminal = 1;
        } else {
            $dpdFitInTerminal = 0;
        }
        update_post_meta(
            $product->get_id(),
            DPD_DOES_NOT_FIT_IN_TERMINAL,
            $dpdFitInTerminal
        );
    }

    /**
     * Order actions metabox dpd.
     *
     * @param int $order_id Order ID.
     */
    public function order_actions_metabox_dpd( $order_id ) {
        global $woocommerce;

        $order = wc_get_order( $order_id );

        $warehouses     = $this->get_option_like( 'warehouses' );
        $warehouses_arr = array();

        foreach ( $warehouses as $warehouse ) {
            $warehouses_arr[ $warehouse['option_name'] ] = $warehouse['option_value']['name'];
        }

        echo '<li class="wide">';

        $dpd_custom_label_count = dpd_get_order_meta( $order_id, '_dpd_custom_label_count', true );
        $custom_attributes = [];
        if ($this->get_order_barcode($order_id)) {
            $custom_attributes['disabled'] = true;
        }

        woocommerce_wp_text_input(
            array(
                'id'          => '_dpd_custom_label_count',
                'label'       => __( 'Custom label count: ', 'woo-shipping-dpd-baltic' ),
                'type'        => 'number',
                'value'       => $dpd_custom_label_count,
                'custom_attributes'       => $custom_attributes
            )
        );

        if ( ! $this->get_order_barcode( $order_id ) ) {
            if ( ( $order->has_shipping_method( self::HOME ) || $order->has_shipping_method( self::HOME_SAT ) || $order->has_shipping_method( self::SAME_DAY ) ) ) {
                if ( get_option( 'dpd_rod_service' ) === 'yes' ) {
                    woocommerce_wp_checkbox(
                        array(
                            'id'          => 'dpd_shipping_return',
                            'label'       => '',
                            'placeholder' => '',
                            'description' => __( 'Activate document return service?', 'woo-shipping-dpd-baltic' ),
                            'cbvalue'     => 'yes',
                        )
                    );

                    woocommerce_wp_text_input(
                        array(
                            'id'          => 'dpd_shipping_note',
                            'label'       => __( 'Document reference number', 'woo-shipping-dpd-baltic' ) . ' *',
                            'placeholder' => '',
                            'description' => '',
                        )
                    );

                    $js = "
							jQuery('input#dpd_shipping_return').change(function() {
								if ( jQuery(this).prop('checked') ) {
									jQuery('p.dpd_shipping_note_field').show();
								} else {
									jQuery('p.dpd_shipping_note_field').hide();
								}
							}).change();";

                    if ( function_exists( 'wc_enqueue_js' ) ) {
                        wc_enqueue_js( $js );
                    } else {
                        $woocommerce->add_inline_js( $js );
                    }
                }
            }
        }

        if ( $order->is_paid() && ! ( $order->has_shipping_method( self::PARCELS ) || $order->has_shipping_method( self::PARCELS_SAME_DAY ) ) && $this->get_order_barcode( $order_id ) ) {
            woocommerce_wp_select(
                array(
                    'id'          => 'dpd_warehouse',
                    'label'       => __( 'Select warehouse:', 'woo-shipping-dpd-baltic' ),
                    'placeholder' => '',
                    'description' => '',
                    'options'     => $warehouses_arr,
                )
            );
        }

        echo '</li>';
    }

    /**
     * Save order actions meta box.
     *
     * @param int   $post_id Post ID.
     * @param array $post Post.
     */
    public function save_order_actions_meta_box( $post_id, $post ) {
        $wp_nonce     = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
        $nonce_action = 'update-post_' . $post_id;

        if ( ! wp_verify_nonce( $wp_nonce, $nonce_action ) ) {
            return;
        }

        if ( ! $this->get_order_barcode( $post_id ) ) {
            $order = wc_get_order( $post_id );

            if ( isset( $_POST['_dpd_custom_label_count'] ) ) {
                dpd_update_order_meta( $post_id, '_dpd_custom_label_count', wc_clean( sanitize_text_field( $_POST['_dpd_custom_label_count'] ) ) );
            }

            if ( ( $order->has_shipping_method( self::HOME ) || $order->has_shipping_method( self::HOME_SAT ) || $order->has_shipping_method( self::SAME_DAY ) ) && get_option( 'dpd_rod_service' ) === 'yes' ) {
                if ( isset( $_POST['dpd_shipping_return'] ) && 'yes' === $_POST['dpd_shipping_return'] ) {
                    dpd_update_order_meta( $post_id, 'dpd_shipping_return', 'yes' );

                    if ( isset( $_POST['dpd_shipping_note'] ) ) {
                        dpd_update_order_meta( $post_id, 'dpd_shipping_note', wc_clean( sanitize_text_field( wp_unslash( $_POST['dpd_shipping_note'] ) ) ) );
                    }
                } else {
                    dpd_update_order_meta( $post_id, 'dpd_shipping_return', 'no' );
                    dpd_update_order_meta( $post_id, 'dpd_shipping_note', '' );
                }
            }
        }
    }

    /**
     * Callback for woocommerce_order_actions
     *
     * @param array $actions Actions.
     *
     * @return mixed
     */
    public function add_order_actions( $actions ) {
        global $theorder;

        if ( ! $theorder->is_paid() || ! ( $theorder->has_shipping_method( self::HOME ) || $theorder->has_shipping_method( self::HOME_SAT ) || $theorder->has_shipping_method( self::PARCELS ) || $theorder->has_shipping_method( self::PARCELS_SAME_DAY ) || $theorder->has_shipping_method( self::SAME_DAY ) ) ) {
            return $actions;
        }

        $actions['dpd_print_parcel_label'] = __( 'Print DPD label', 'woo-shipping-dpd-baltic' );

        if ( $this->get_order_barcode( $theorder->get_id() ) ) {
            $actions['dpd_cancel_shipment'] = __( 'Cancel DPD shipment', 'woo-shipping-dpd-baltic' );
            $actions['dpd_parcel_status']   = __( 'Get last parcel status', 'woo-shipping-dpd-baltic' );

            if ( ! ( $theorder->has_shipping_method( self::PARCELS ) || $theorder->has_shipping_method( self::PARCELS_SAME_DAY ) ) ) {
                $actions['dpd_collection_request'] = __( 'Collection request to return from customer', 'woo-shipping-dpd-baltic' );
            }
        }

        return $actions;
    }

    /**
     * Define orders bulk actions.
     *
     * @param array $actions Actions.
     *
     * @return mixed
     */
    public function define_orders_bulk_actions( $actions ) {
        $actions['dpd_print_parcel_label'] = __( 'Print DPD label', 'woo-shipping-dpd-baltic' );
        $actions['dpd_cancel_shipment']    = __( 'Cancel DPD shipments', 'woo-shipping-dpd-baltic' );

        return $actions;
    }

    /**
     * Handle bulk actions.
     *
     * @param  string $redirect_to URL to redirect to.
     * @param  string $action Action name.
     * @param  array  $ids List of ids.
     *
     * @return string
     */
    public function handle_orders_bulk_actions( $redirect_to, $action, $ids ) {
        $ids     = array_map( 'absint', $ids );
        $changed = 0;
        $report_action = '';
        if ( 'dpd_print_parcel_label' === $action ) {
            $report_action = 'dpd_printed_parcel_label';
            $result        = $this->do_multiple_print_parcel_label( $ids );
            $changed       = ( null === $result ) ? -1 : count( $ids );
        } elseif ( 'dpd_cancel_shipment' === $action ) {
            $report_action = 'dpd_canceled_shipment';

            foreach ( $ids as $id ) {
                $order = wc_get_order( $id );

                if ( $order ) {
                    $this->do_cancel_shipment( $order );
                    $changed ++;
                }
            }
        }

        if ( $changed ) {
            $redirect_to = add_query_arg(
                array(
                    'post_type'   => 'shop_order',
                    'bulk_action' => $report_action,
                    'changed'     => $changed,
                    'ids'         => implode( ',', $ids ),
                ),
                $redirect_to
            );
        }

        return esc_url_raw( $redirect_to );
    }

    /**
     * Bulk admin notices.
     *
     * @return mixed
     */
    public function bulk_admin_notices() {
        global $post_type, $pagenow;

        // Bail out if not on shop order list page.
        if ( 'edit.php' !== $pagenow || 'shop_order' !== $post_type || ! isset( $_REQUEST['bulk_action'] ) ) { // WPCS: input var ok, CSRF ok.
            return;
        }

        $number      = isset( $_REQUEST['changed'] ) ? intval( sanitize_text_field( wp_unslash( $_REQUEST['changed'] ) ) ) : 0; // WPCS: input var ok, CSRF ok.
        $bulk_action = wc_clean( sanitize_text_field( wp_unslash( $_REQUEST['bulk_action'] ) ) );
        $message     = '';

        if ( 'dpd_printed_parcel_label' === $bulk_action ) { // WPCS: input var ok, CSRF ok.
            if ( -1 == $number ) {
                $message = __( 'Cannot print DPD labels for these orders, because some of orders parcel is not found.', 'woo-shipping-dpd-baltic' );
            } else {
                /* translators: %d: number order */
                $message = sprintf( _n( 'DPD label printed for %d order.', 'DPD labels printed for %d orders.', $number, 'woo-shipping-dpd-baltic' ), number_format_i18n( $number ) );
            }
        }

        if ( 'dpd_canceled_shipment' === $bulk_action ) { // WPCS: input var ok, CSRF ok.
            /* translators: %d: number order */
            $message = sprintf( _n( 'DPD shipment cancelled for %d order.', 'DPD shipments cancelled for %d orders.', $number, 'woo-shipping-dpd-baltic' ), number_format_i18n( $number ) );
        }

        if ( ! empty( $message ) ) {
            echo '<div class="updated"><p>' . wp_kses(
                    $message,
                    array(
                        'a' => array(
                            'href'  => array(),
                            'title' => array(),
                        ),
                    )
                ) . '</p></div>';
        }
    }

    /**
     * Callback for woocommerce_order_action_dpd_print_parcel_label.
     *
     * @param \WC_Order $order Order.
     */
    public function do_print_parcel_label( $order ) {
        $shipments        = $this->order_shipment_creation( array( $order ) );
        $tracking_numbers = '';

        foreach ( $shipments as $order_id => $data ) {
            if ( 'ok' == $data['status'] ) {
                foreach ( $data['barcodes'] as $barcode ) {
                    $tracking_numbers .= $barcode->dpd_barcode . '|';
                }
            }
        }

        if ( $tracking_numbers ) {
            $result = $this->print_order_parcel_label( $tracking_numbers );

            if ( null == $result ) {
                $order->add_order_note( __( 'Cannot print DPD label: Parcel not found', 'woo-shipping-dpd-baltic' ) );
            }
        }
    }

    /**
     * Do multiple print parcel label.
     *
     * @param  array $ids List of ids.
     */
    public function do_multiple_print_parcel_label( $ids ) {
        $tracking_numbers = '';

        foreach ( $ids as $id ) {
            $order = wc_get_order( $id );

            if ( ! $this->get_order_barcode( $id ) ) {
                if ( isset( $_POST['_dpd_custom_label_count'] ) ) {
                    dpd_update_order_meta( $id, '_dpd_custom_label_count', wc_clean( sanitize_text_field( $_POST['_dpd_custom_label_count'] ) ) );
                }
            }

            if ( $order ) {
                $shipments = $this->order_shipment_creation( array( $order ) );

                foreach ( $shipments as $order_id => $data ) {
                    if ( 'ok' === $data['status'] ) {
                        foreach ( $data['barcodes'] as $barcode ) {
                            $tracking_numbers .= $barcode->dpd_barcode . '|';
                        }
                    }
                }
            }
        }

        if ( $tracking_numbers ) {
            $this->print_order_parcel_label( $tracking_numbers );
        }

        return null;
    }

    /**
     * Do cancel shipment.
     *
     * @param array $order Order.
     */
    public function do_cancel_shipment( $order ) {
        $order_barcodes   = $this->get_order_barcode( $order->get_id() );
        $tracking_numbers = '';

        foreach ( $order_barcodes as $barcode ) {
            $tracking_numbers .= $barcode->dpd_barcode . '|';
        }

        if ( $tracking_numbers ) {
            $response = self::http_client(
                'parcelDelete_',
                array(
                    'parcels' => $tracking_numbers,
                )
            );

            if ( $response && 'ok' === $response->status ) {
                $this->delete_order_barcode( $order->get_id() );

                $order->add_order_note( __( 'The DPD shipment was cancelled for this order!', 'woo-shipping-dpd-baltic' ) );
            } elseif ( $response && 'err' === $response->status ) {
                $order->add_order_note( $response->errlog );
            }
        }
    }

    /**
     * Do get parcel status.
     *
     * @param array $order Order.
     */
    public function do_get_parcel_status( $order ) {
        $order_barcodes = $this->get_order_barcode( $order->get_id() );

        if ( $order_barcodes ) {
            foreach ( $order_barcodes as $barcode ) {
                $response = self::http_client(
                    'parcelStatus_',
                    array(
                        'parcel_number' => $barcode->dpd_barcode,
                    )
                );

                if ( $response && 'ok' === $response->status ) {
                    if ( '' != $response->parcel_status ) {
                        if ( 'Pickup scan' === $response->parcel_status ) {
                            $status = __( 'Parcel has been picked up', 'woo-shipping-dpd-baltic' );
                        } elseif ( 'HUB-scan' === $response->parcel_status ) {
                            $status = __( 'Parcel is at parcel delivery centre', 'woo-shipping-dpd-baltic' );
                        } elseif ( 'Out for delivery' === $response->parcel_status ) {
                            $status = __( 'Parcel is out for delivery', 'woo-shipping-dpd-baltic' );
                        } elseif ( 'Infoscan' === $response->parcel_status ) {
                            $status = __( 'Additional information added', 'woo-shipping-dpd-baltic' );
                        } elseif ( 'Delivered' === $response->parcel_status ) {
                            $status = __( 'Parcel is successfully delivered', 'woo-shipping-dpd-baltic' );
                        } elseif ( 'Delivery obstacle' === $response->parcel_status ) {
                            $status = __( 'Delivery obstacle', 'woo-shipping-dpd-baltic' );
                        } else {
                            $status = __( 'The parcel is not scanned by DPD yet', 'woo-shipping-dpd-baltic' );
                        }

                        $order->add_order_note( $barcode->dpd_barcode . ' ' . $status . '. <a href="https://tracking.dpd.de/status/en_US/parcel/' . $barcode->dpd_barcode . '" target="_blank">' . __( 'Track parcel', 'woo-shipping-dpd-baltic' ) . '</a>' );
                        do_action( 'woo_shipping_dpd_baltic/tracking_code', $barcode->dpd_barcode );
                    } else {
                        $order->add_order_note( $barcode->dpd_barcode . ' ' . __( 'The parcel is not scanned by DPD yet', 'woo-shipping-dpd-baltic' ) . '. <a href="https://tracking.dpd.de/status/en_US/parcel/' . $barcode->dpd_barcode . '" target="_blank">' . __( 'Track parcel', 'woo-shipping-dpd-baltic' ) . '</a>' );
                    }
                } elseif ( $response && 'err' === $response->status ) {
                    $order->add_order_note( $response->errlog );
                }
            }
        } else {
            $order->add_order_note( 'Parcel number not found' );
        }
    }

    /**
     * Order shipment creation.
     *
     * @param array $orders Orders.
     */
    private function order_shipment_creation( $orders = array() ) {
        global $wpdb;

        $tracking_barcodes = array();

        if ( get_option( 'dpd_return_labels' ) === 'yes' ) {
            $pickup_parcel_type     = 'PS-RETURN';
            $pickup_cod_parcel_type = 'PS-COD-RETURN';

            $pickup_same_parcel_type     = '274-RETURN';
            $pickup_same_cod_parcel_type = '274-COD-RETURN';

            $courier_parcel_type     = 'D-B2C-RETURN';
            $courier_cod_parcel_type = 'D-B2C-COD-RETURN';

            $courier_parcel_rod_type     = 'D-B2C-DOCRET-RETURN';
            $courier_cod_parcel_rod_type = 'D-COD-B2C-DOCRET-RETURN';

            // Saturday services.
            $courier_sat_parcel_type     = 'D-B2C-SAT-RETURN';
            $courier_sat_cod_parcel_type = 'D-B2C-SAT-COD-RETURN';

            $courier_sat_parcel_rod_type     = 'D-B2C-SAT-DOCRET-RETURN'; // @TODO: is this right? D-B2C-DOCRET-SAT-RETURN
            $courier_cod_sat_parcel_rod_type = 'D-COD-B2C-SAT-DOCRET-RETURN'; // @TODO: is this right? D-COD-B2C-DOCRET-SAT-RETURN

            $courier_same_parcel_type     = 'SD-RETURN';
            $courier_same_cod_parcel_type = 'SD-COD-RETURN';
        } else {
            $pickup_parcel_type     = 'PS';
            $pickup_cod_parcel_type = 'PS-COD';

            $pickup_same_parcel_type     = '274';
            $pickup_same_cod_parcel_type = '274-COD';

            $courier_parcel_type     = 'D-B2C';
            $courier_cod_parcel_type = 'D-B2C-COD';

            $courier_parcel_rod_type     = 'D-B2C-DOCRET';
            $courier_cod_parcel_rod_type = 'D-COD-B2C-DOCRET';

            // Saturday services.
            $courier_sat_parcel_type     = 'D-B2C-SAT';
            $courier_sat_cod_parcel_type = 'D-B2C-SAT-COD';

            $courier_sat_parcel_rod_type     = 'D-B2C-SAT-DOCRET'; // @TODO: is this right? D-B2C-DOCRET-SAT
            $courier_cod_sat_parcel_rod_type = 'D-COD-B2C-SAT-DOCRET'; // @TODO: is this right? D-COD-B2C-DOCRET-SAT

            $courier_same_parcel_type     = 'SD';
            $courier_same_cod_parcel_type = 'SD-COD';

            // If in options selected country is lituanian these need to change.
            if ( get_option( 'dpd_api_service_provider' ) === 'lt' ) {
                $courier_same_parcel_type     = 'SDB2C';
                $courier_same_cod_parcel_type = 'SDB2C-COD';
            }
        }

        foreach ( $orders as $order ) {
            $order_id = $order->get_id();
            $products = $order->get_items();

            // Fixing params for DPD.
            $name1  = $this->custom_length( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(), 40 ); // required 1, max length 40.
            $name2  = $this->custom_length( $order->get_shipping_company(), 40 ); // required 1, max length 40.
            $street = $this->custom_length( $order->get_shipping_address_1(), 40 ); // required 1, max length 40.
            $city   = $this->custom_length( $order->get_shipping_city(), 40 ); // required 1, max length 40.

            $country_code = $order->get_shipping_country();
            if ( strtoupper( $country_code ) == 'LT' || strtoupper( $country_code ) == 'LV' || strtoupper( $country_code ) == 'EE' ) {
                $pcode = preg_replace( '/[^0-9,.]/', '', $order->get_shipping_postcode() );
            } else {
                $pcode = $order->get_shipping_postcode();
            }

            $dial_code_helper = new Dpd_Baltic_Dial_Code_Helper();
            $billing_phone = $order->get_billing_phone();
            $shipping_phone = $order->get_shipping_phone() ?: $billing_phone;

            $correct_phone    = $dial_code_helper->separate_phone_number_from_country_code( $shipping_phone, $country_code );
            $phone            = $correct_phone['dial_code'] . $correct_phone['phone_number'];

            $email           = $order->get_billing_email();
            $order_comment   = $this->custom_length( $order->get_customer_note(), 40 ); // required 0, max length 40.
            $shipping_labels = dpd_get_order_meta( $order_id, 'dpd_shipping_labels', true );
            $num_of_parcel   = $shipping_labels ? $shipping_labels : 0;

            // If documents should be return.
            $shipping_return = dpd_get_order_meta( $order_id, 'dpd_shipping_return', true );
            $shipping_note   = dpd_get_order_meta( $order_id, 'dpd_shipping_note', true );

            if ( $order->has_shipping_method( self::HOME ) || $order->has_shipping_method( self::HOME_SAT ) || $order->has_shipping_method( self::PARCELS ) || $order->has_shipping_method( self::PARCELS_SAME_DAY ) || $order->has_shipping_method( self::SAME_DAY ) ) {
                $order_barcode = $this->get_order_barcode( $order_id );

                if ( ! $order_barcode ) {
                    $shop_weight_unit         = get_option( 'woocommerce_weight_unit' );
                    $product_weight           = 0;
                    $total_order_quantity     = 0;
                    $total_different_products = 0;

                    if ( 'oz' === $shop_weight_unit ) {
                        $divider = 35.274;
                    } elseif ( 'lbs' === $shop_weight_unit ) {
                        $divider = 2.20462;
                    } elseif ( 'g' === $shop_weight_unit ) {
                        $divider = 1000;
                    } else {
                        $divider = 1;
                    }

                    /*
                     Old weight code
                    foreach ( $products as $product ) {
                        $product_data              = $product->get_product();
                        $product_weight           += ($product_data->get_weight() / $divider) * $product->get_quantity();
                        $total_order_quantity     += $product->get_quantity();
                        $total_different_products += 1;
                    }
                    */
                    // New weight code for release.
                    foreach ( $products as $product ) {
                        $product_data = $product->get_product();
                        $weight       = $product_data->get_weight();
                        if ( $weight >= 0 ) {
                            $product_weight += ( $weight / $divider ) * $product->get_quantity();
                        } else {
                            $product_weight += ( 1.0 / $divider ) * $product->get_quantity();
                        }
                        $total_order_quantity += $product->get_quantity();
                        ++$total_different_products;
                    }
                    // How many labels print.
                    $labels_setting = get_option( 'dpd_parcel_distribution' );

                    if ( 0 == $num_of_parcel ) { // was 1
                        // All products in same parcel.
                        if ( 1 == $labels_setting ) {
                            $num_of_parcel = 1;
                            // Each product in seperate shipment.
                        } elseif ( 2 == $labels_setting ) {
                            $num_of_parcel = $total_different_products;
                            // Each product quantity as separate parcel.
                        } elseif ( 3 == $labels_setting ) {
                            $num_of_parcel = $total_order_quantity;
                        } else {
                            $num_of_parcel = 1;
                        }
                    } else {
                        $num_of_parcel = 1;
                    }

                    /** custom dpd label count */
                    $dpd_custom_label_count = dpd_get_order_meta( $order_id, '_dpd_custom_label_count', true );
                    if ( $dpd_custom_label_count ) {
                        $num_of_parcel = $dpd_custom_label_count;
                    }

                    $params = array(
                        'name1'            => $name1,
                        'name2'            => $name2,
                        'street'           => $street,
                        'city'             => $city,
                        'country'          => $country_code,
                        'pcode'            => $pcode,
                        'num_of_parcel'    => $num_of_parcel,
                        'weight'           => round( $product_weight / $num_of_parcel, 3 ),
                        'phone'            => $phone,
                        'idm_sms_number'   => $phone,
                        'email'            => $email,
                        'order_number'     => 'DPD #' . $order->get_order_number(),
                        'order_number3'    => 'WC' . WC_VERSION . '|' . DPD_NAME_VERSION,
                        'fetchGsPUDOpoint' => 1,
                    );

                    // Home delivery.
                    if ( $order->has_shipping_method( self::HOME ) ) {
                        $params['remark'] = $order_comment;

                        // If ROD services used.
                        if ( 'yes' === $shipping_return ) {
                            $params['parcel_type']     = $courier_parcel_rod_type;
                            $params['dnote_reference'] = $shipping_note;
                        } else {
                            $params['parcel_type'] = $courier_parcel_type;
                        }

                        // If order is COD.
                        if ( $order->get_payment_method() == 'cod' ) {
                            $params['cod_amount'] = number_format( $order->get_total(), 2, '.', '' );

                            if ( 1 == $shipping_return ) {
                                $params['parcel_type'] = $courier_cod_parcel_rod_type;
                            } else {
                                $params['parcel_type'] = $courier_cod_parcel_type;
                            }
                        }

                        // Time frame.
                        $shipping_timeframe = dpd_get_order_meta( $order_id, 'wc_shipping_' . self::HOME . '_shifts', true );

                        if ( $shipping_timeframe && ! empty( $shipping_timeframe ) ) {
                            $shipping_timeframe = explode( ' - ', $shipping_timeframe );

                            $params['timeframe_from'] = $shipping_timeframe[0];
                            $params['timeframe_to']   = $shipping_timeframe[1];
                        }
                    }

                    // Home delivery saturday.
                    if ( $order->has_shipping_method( self::HOME_SAT ) ) {
                        $params['remark'] = $order_comment;

                        // If ROD services used.
                        if ( 'yes' === $shipping_return ) {
                            $params['parcel_type']     = $courier_sat_parcel_rod_type;
                            $params['dnote_reference'] = $shipping_note;
                        } else {
                            $params['parcel_type'] = $courier_sat_parcel_type;
                        }

                        // If order is COD.
                        if ( $order->get_payment_method() == 'cod' ) {
                            $params['cod_amount'] = number_format( $order->get_total(), 2, '.', '' );

                            if ( 1 == $shipping_return ) {
                                $params['parcel_type'] = $courier_cod_sat_parcel_rod_type;
                            } else {
                                $params['parcel_type'] = $courier_sat_cod_parcel_type;
                            }
                        }
                    }

                    // Same day delivery.
                    if ( $order->has_shipping_method( self::SAME_DAY ) ) {
                        $params['remark'] = $order_comment;

                        $params['parcel_type'] = $courier_same_parcel_type;

                        // If order is COD.
                        if ( $order->get_payment_method() == 'cod' ) {
                            $params['cod_amount'] = number_format( $order->get_total(), 2, '.', '' );

                            $params['parcel_type'] = $courier_same_cod_parcel_type;
                        }
                    }

                    // Parcelshop services.
                    if ( $order->has_shipping_method( self::PARCELS ) ) {
                        $parcel_shop_id = dpd_get_order_meta( $order_id, 'wc_shipping_' . self::PARCELS . '_terminal', true );
                        $terminal       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE parcelshop_id = %s", $parcel_shop_id ) );

                        $params['city']          = $terminal->city;
                        $params['country']       = $terminal->country;
                        $params['pcode']         = $terminal->pcode;
                        $params['street']        = $terminal->street;
                        $params['parcel_type']   = $pickup_parcel_type;
                        $params['parcelshop_id'] = $parcel_shop_id;

                        // If order is COD.
                        if ( $order->get_payment_method() == 'cod' ) {
                            $params['cod_amount'] = number_format( $order->get_total(), 2, '.', '' );

                            $params['parcel_type'] = $pickup_cod_parcel_type;
                        }
                    }

                    // Parcelshop same day services.
                    if ( $order->has_shipping_method( self::PARCELS_SAME_DAY ) ) {
                        $parcel_shop_id = dpd_get_order_meta( $order_id, 'wc_shipping_' . self::PARCELS_SAME_DAY . '_terminal', true );
                        $terminal       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE parcelshop_id = %s", $parcel_shop_id ) );

                        $params['city']          = $terminal->city;
                        $params['country']       = $terminal->country;
                        $params['pcode']         = $terminal->pcode;
                        $params['street']        = $terminal->street;
                        $params['parcel_type']   = $pickup_same_parcel_type;
                        $params['parcelshop_id'] = $parcel_shop_id;

                        // If order is COD.
                        if ( $order->get_payment_method() == 'cod' ) {
                            $params['cod_amount'] = number_format( $order->get_total(), 2, '.', '' );

                            $params['parcel_type'] = $pickup_same_cod_parcel_type;
                        }
                    }

                    $response = self::http_client( 'createShipment_', $params );

                    if ( $response && 'ok' == $response->status ) {
                        $tracking_barcodes[ $order_id ]['status']   = 'ok';
                        $tracking_barcodes[ $order_id ]['barcodes'] = $response->pl_number;

                        if ( $response->pl_number ) {
                            $service_provider = get_option( 'dpd_api_service_provider' );
                            switch ( $service_provider ) {
                                case 'lt':
                                    $country       = 'lt';
                                    $lang_settings = 'lt_lt';
                                    break;
                                case 'lv':
                                    $country       = 'lv';
                                    $lang_settings = 'lv_lv';
                                    break;
                                case 'ee':
                                    $country       = 'ee';
                                    $lang_settings = 'et_et';
                                    break;
                                default:
                                    $country       = 'lt';
                                    $lang_settings = 'en';
                                    break;
                            }

                            $barcodes = '';

                            foreach ( $response->pl_number as $number ) {
                                $this->set_order_barcode( $order_id, $number, $order );
                                if ( end( $response->pl_number ) == $number ) {
                                    $barcodes .= "<a href='https://www.dpdgroup.com/" . $country . '/mydpd/my-parcels/track?lang=' . $lang_settings . '&parcelNumber=' . $number . "'>$number</a>";
                                } else {
                                    $barcodes .= "<a href='https://www.dpdgroup.com/" . $country . '/mydpd/my-parcels/track?lang=' . $lang_settings . '&parcelNumber=' . $number . "'>$number</a>" . ', ';
                                }
                            }
                            $this->send_barcode_codes( $order, $barcodes );

                            $tracking_barcodes[ $order_id ]['status']   = 'ok';
                            $tracking_barcodes[ $order_id ]['barcodes'] = $this->get_order_barcode( $order_id );
                        }
                    } elseif ( $response && 'err' === $response->status ) {
                        $tracking_barcodes[ $order_id ]['status'] = 'err';
                        $tracking_barcodes[ $order_id ]['errlog'] = $response->errlog;

                        $order->add_order_note( $response->errlog );
                    }
                } else {
                    $tracking_barcodes[ $order_id ]['status']   = 'ok';
                    $tracking_barcodes[ $order_id ]['barcodes'] = $order_barcode;
                }
            } else {
                $tracking_barcodes[ $order_id ]['status'] = 'err';
                $tracking_barcodes[ $order_id ]['errlog'] = __( 'Shipping method is not DPD', 'woo-shipping-dpd-baltic' );
            }
        }

        return $tracking_barcodes;
    }

    /**
     * Print order parcel label.
     *
     * @param string|null $tracking_number Tracking Number.
     *
     * @return mixed
     */
    private function print_order_parcel_label( $tracking_number = null ) {
        $label_size = get_option( 'dpd_label_size' );

        $response = self::http_client(
            'parcelPrint_',
            array(
                'parcels'     => $tracking_number,
                'printType'   => 'PDF',
                'printFormat' => $label_size ? $label_size : 'A4',
            )
        );

        $json_response = json_decode( $response );

        if ( $json_response && 'err' == $json_response->status ) {
            return null;
        } else {
            $this->get_labels_output( $response );
        }
    }

    /**
     * Get labels output.
     *
     * @param string $pdf PDF.
     * @param string $file_name File name.
     */
    private function get_labels_output( $pdf, $file_name = 'dpdLabels' ) {
        $name = $file_name . '-' . gmdate( 'Y-m-d' ) . '.pdf';

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/pdf' );
        header( 'Content-Disposition: attachment; filename="' . $name . '"' );
        header( 'Content-Transfer-Encoding: binary' );
        header( 'Connection: Keep-Alive' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Pragma: public' );

        echo base64_decode( esc_textarea( base64_encode( $pdf ) ) );

        die;
    }

    /**
     * Get order barcode.
     *
     * @param int $order_id Order ID.
     *
     * @return mixed
     */
    private function get_order_barcode( $order_id ) {
        global $wpdb;

        if ( $order_id ) {
            $sql_string = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_barcodes WHERE order_id = %d ", $order_id ); // WPCS: unprepared SQL OK.
            return $wpdb->get_results( $sql_string ); // WPCS: unprepared SQL OK.
        }

        return null;
    }

    /**
     * Send barcode codes.
     *
     * @param object $order Order.
     * @param array  $barcodes Barcode.
     */
    private function send_barcode_codes( $order, $barcodes ) {
        /* translators: %s: tracking number */
        $message = sprintf( __( 'DPD Tracking number: %s', 'woo-shipping-dpd-baltic' ), $barcodes );
        $order->add_order_note( $message, true, true );
    }

    /**
     * Set order barcode.
     *
     * @param int    $order_id Order ID.
     * @param string $barcode Barcode.
     * @param string $order Order.
     *
     * @return mixed
     */
    private function set_order_barcode( $order_id, $barcode, $order ) {
        global $wpdb;

        if ( $order_id && $barcode ) {
            $wpdb->insert(
                $wpdb->prefix . 'dpd_barcodes',
                array(
                    'order_id'    => $order_id,
                    'dpd_barcode' => $barcode,
                )
            );

            // $message = sprintf( __( 'DPD Tracking number: %s', 'woo-shipping-dpd-baltic' ), $barcode );
            // $order->add_order_note( $message, true, true );
        }
    }

    /**
     * Delete order barcode.
     *
     * @param int $order_id Order ID.
     *
     * @return mixed
     */
    private function delete_order_barcode( $order_id ) {
        global $wpdb;

        $wpdb->delete(
            $wpdb->prefix . 'dpd_barcodes',
            array(
                'order_id' => $order_id,
            )
        );
    }

    /**
     * Get parcels list.
     *
     * @param string $country Country.
     * @param string $opening_hours Opening Hours.
     *
     * @return mixed
     */
    private static function get_parcels_list( $country = 'LT', $opening_hours = true ) {
        $parcel_data = array();

        $parcels     = self::http_client(
            'parcelShopSearch_',
            array(
                'country'              => $country,
                'fetchGsPUDOpoint'     => 1,
                'retrieveOpeningHours' => $opening_hours ? 1 : 0,
            )
        );

        $cod_pudo = array();
        $lenght   = 3;

        switch ( $country ) {
            case 'LT':
                $cod_pudo = array( 'LT9' );
                $lenght   = 3;
                break;

            case 'LV':
                $cod_pudo = array( 'LV9' );
                $lenght   = 3;
                break;

            case 'EE':
                $cod_pudo = array( 'EE90', 'EE10' );
                $lenght   = 4;
                break;
        }

        if ( $parcels && 'ok' == $parcels->status ) {
            foreach ( $parcels->parcelshops as $parcelshop ) {
                $parcel_id     = substr( $parcelshop->parcelshop_id, 0, $lenght );
                $cod_available = 0;

                if ( in_array( $parcel_id, $cod_pudo ) ) {
                    $cod_available = 1;
                }

                $data = array(
                    'parcelshop_id' => $parcelshop->parcelshop_id,
                    'company'       => $parcelshop->company,
                    'country'       => $parcelshop->country,
                    'city'          => $parcelshop->city,
                    'pcode'         => $parcelshop->pcode,
                    'street'        => $parcelshop->street,
                    'email'         => $parcelshop->email,
                    'phone'         => $parcelshop->phone,
                    'distance'      => $parcelshop->distance,
                    'longitude'     => $parcelshop->longitude,
                    'latitude'      => $parcelshop->latitude,
                    'cod'           => $cod_available,
                );

                // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                if ( isset( $parcelshop->openingHours ) ) {
                    foreach ( $parcelshop->openingHours as $day ) {
                        $morning   = $day->openMorning . '-' . $day->closeMorning;
                        $afternoon = $day->openAfternoon . '-' . $day->closeAfternoon;

                        $data[ strtolower( $day->weekday ) ] = $morning . '|' . $afternoon;
                    }
                }
                // phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

                $parcel_data[] = $data;
            }
        }

        return $parcel_data;
    }

    /**
     * Update parcels list.
     *
     * @param array $data Data.
     * @param string $country
     */
    private static function update_parcels_list( $data = array(), $country = '' ) {
        global $wpdb;
        $wpdb->show_errors();

        if ( !empty( $country ) ) {
            $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}dpd_terminals SET status = 0 WHERE country = '%s'", $country ) );
        }
        foreach ( $data as $parcelshop ) {
            $results = $wpdb->get_results(
                $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}dpd_terminals WHERE parcelshop_id=%s", $parcelshop['parcelshop_id'] )
            );
            $parcelshop['status'] = 1;
            if ( ! empty( $results ) ) {
                $wpdb->update( $wpdb->prefix . 'dpd_terminals', $parcelshop, array( 'parcelshop_id' => $parcelshop['parcelshop_id'] ) );
            } else {
                $wpdb->insert( $wpdb->prefix . 'dpd_terminals', $parcelshop );
            }
        }
        $totalParcelsByCountry = $wpdb->get_results(
            $wpdb->prepare( "SELECT count(id) as 'total_item' FROM {$wpdb->prefix}dpd_terminals WHERE country=%s", $country )
            , ARRAY_A);
        dpd_debug_log(sprintf("Total Items are there in database - table - dpd_terminals: %s", !empty($totalParcelsByCountry) ? $totalParcelsByCountry[0]['total_item'] : "N/A"));

    }

    /**
     * Custom length.
     *
     * @param string $string String.
     * @param int    $length Length.
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

    /**
     * Renders werehouses repeater.
     */
    public function settings_dpd_warehouses() {
        $warehouses    = $this->get_option_like( 'warehouses' );
        $countries_obj = new WC_Countries();
        $countries     = $countries_obj->__get( 'countries' );

        ob_start();
        require_once plugin_dir_path( __FILE__ ) . 'partials/dpd-admin-warehouses-display.php';
        $output       = ob_get_clean();
        $allowed_html =
            array(
                'div'    => array(
                    'data-repeater-item' => array(),
                    'data-repeater-list' => array(),
                    'class'              => array(),
                    'style'              => array(),
                ),
                'input'  => array(
                    'type'                 => array(),
                    'id'                   => array(),
                    'name'                 => array(),
                    'value'                => array(),
                    'class'                => array(),
                    'placeholder'          => array(),
                    'required'             => array(),
                    'data-repeater-delete' => array(),
                    'data-repeater-create' => array(),
                ),
                'select' => array(
                    'type'  => array(),
                    'id'    => array(),
                    'name'  => array(),
                    'value' => array(),
                ),
                'option' => array(
                    'value'    => array(),
                    'selected' => array(),
                ),
                'button' => array(
                    'type'                 => array(),
                    'id'                   => array(),
                    'name'                 => array(),
                    'value'                => array(),
                    'data-repeater-delete' => array(),
                    'data-optionkey'       => array(),
                ),
            );
        echo wp_kses( $output, $allowed_html );
    }

    /**
     * Settings dpd collect.
     */
    public function settings_dpd_collect() {
        $fields_prefix = 'dpd_collect';
        $countries_obj = new WC_Countries();
        $countries     = $countries_obj->__get( 'countries' );
        $dayofweek     = current_time( 'w' );
        $current_time  = current_time( 'H:i:s' );

        if ( 6 == $dayofweek ) {
            // If its saturday.
            $date = gmdate( 'Y-m-d', strtotime( '+ 2 days', strtotime( $current_time ) ) );
        } elseif ( 7 == $dayofweek ) {
            // If its sunday.
            $date = gmdate( 'Y-m-d', strtotime( '+ 1 day', strtotime( $current_time ) ) );
        } elseif ( 5 == $dayofweek ) {
            // If its friday.
            $date = gmdate( 'Y-m-d', strtotime( '+ 3 days', strtotime( $current_time ) ) );
        } else {
            $date = gmdate( 'Y-m-d', strtotime( '+ 1 days', strtotime( $current_time ) ) );
        }

        ob_start();
        require_once plugin_dir_path( __FILE__ ) . 'partials/dpd-admin-collect-display.php';
        $output = ob_get_clean();

        $allowed_html = array_merge(
            wp_kses_allowed_html( 'post' ),
            array(
                'input'  => array(
                    'type'        => array(),
                    'id'          => array(),
                    'name'        => array(),
                    'value'       => array(),
                    'maxlength'   => array(),
                    'class'       => array(),
                    'placeholder' => array(),
                    'required'    => array(),
                ),
                'select' => array(
                    'type'     => array(),
                    'id'       => array(),
                    'name'     => array(),
                    'value'    => array(),
                    'class'    => array(),
                    'required' => array(),
                ),
                'option' => array(
                    'value'    => array(),
                    'selected' => array(),
                ),
                'button' => array(
                    'type'  => array(),
                    'id'    => array(),
                    'name'  => array(),
                    'value' => array(),
                    'class' => array(),
                ),
            )
        );
        echo wp_kses( $output, $allowed_html );
    }

    /**
     * Renders manifests table
     */
    public function settings_dpd_manifests() {
        global $wpdb;

        $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dpd_manifests ORDER BY id DESC" );

        ob_start();
        require_once plugin_dir_path( __FILE__ ) . 'partials/dpd-admin-manifests-display.php';
        $output = ob_get_clean();

        $html_styles = array(
            'a' => array(
                'type'  => array(),
                'class' => array(),
                'href'  => array(),
            ),
        );

        $allowed_html = array_merge( wp_kses_allowed_html( 'post' ), $html_styles );
        echo wp_kses( $output, $allowed_html );
    }

    /**
     * Download manifest.
     *
     * @return boolean|void
     */
    public function download_manifest() {
        if ( ! isset( $_GET['admin_ajax_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['admin_ajax_nonce'] ) ), 'admin-nonce' ) ) {
            return false;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        global $wpdb;

        $manifest_id_to_download = isset( $_GET['download_manifest'] ) ? filter_var( sanitize_key( wp_unslash( $_GET['download_manifest'] ) ), FILTER_SANITIZE_NUMBER_INT ) : false;

        if ( $manifest_id_to_download ) {
            $results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_manifests WHERE id=%d", $manifest_id_to_download ) );
            $name    = 'manifest_' . str_replace( '-', '_', $results->date ) . '.pdf';

            $base64_decode_pdf = base64_decode( esc_textarea( $results->pdf ) );
            $convert_to_object = json_decode( $base64_decode_pdf ) ?: null;

            if ( ! empty($convert_to_object) && $convert_to_object->status = 'err' ) {
                dpd_baltic_add_flash_notice( $convert_to_object->errlog, 'error', true );
            } else {
                header( 'Content-Description: File Transfer' );
                header( 'Content-Type: application/pdf' );
                header( 'Content-Disposition: attachment; filename="' . $name . '"' );
                header( 'Content-Transfer-Encoding: binary' );
                header( 'Connection: Keep-Alive' );
                header( 'Expires: 0' );
                header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
                header( 'Pragma: public' );

                ob_clean();
                flush();

                echo $base64_decode_pdf;
            }
        }
    }

    /**
     * Get option like.
     *
     * @param string $segment Segment.
     *
     * @return boolean|void
     */
    public function get_option_like( $segment ) {
        global $wpdb;

        $data = array();

        $results = $wpdb->get_results( $wpdb->prepare( "SELECT option_id, option_name, option_value FROM {$wpdb->prefix}options WHERE option_name LIKE %s", $wpdb->esc_like( $segment ) . '%' ) );

        foreach ( $results as $result ) {
            $data[] = array(
                'option_id'    => $result->option_id,
                'option_name'  => $result->option_name,
                'option_value' => maybe_unserialize( $result->option_value ),
            );
        }

        return $data;
    }

    /**
     * Delete warehouse.
     */
    public function delete_warehouse() {
        if ( ! isset( $_POST['admin_ajax_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['admin_ajax_nonce'] ) ), 'admin-nonce' ) ) {
            wp_die();
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }

        global $wpdb;

        if ( isset( $_POST['option_id'] ) ) {
            $option_id = filter_var( sanitize_text_field( wp_unslash( $_POST['option_id'] ) ), FILTER_SANITIZE_NUMBER_INT );

            if ( is_numeric( $option_id ) && wp_doing_ajax() ) {
                $table_name = $wpdb->prefix . 'options';
                $wpdb->query( $wpdb->prepare( "DELETE FROM `$table_name` WHERE option_id = %d ", $option_id ) ); // WPCS: unprepared SQL OK.
            }
        }

        die();
    }

    /**
     * Courier popup.
     */
    public function courier_popup() {
        $current_time = current_time( 'H:i:s' );

        // Pick up from.
        $customer_time = gmdate( 'G', strtotime( gmdate( 'H:i:s', strtotime( '+18 minutes', strtotime( current_time( 'H:i:s' ) ) ) ) ) );
        $pickup_until  = '18:00';

        if ( $customer_time >= 7 && $customer_time < 15 ) {
            // Pick up times.
            $pickup_from = gmdate( 'H:i', strtotime( '+18 minutes', strtotime( $current_time ) ) );
        } else {
            $pickup_from = '10:00';
        }

        $dayofweek    = current_time( 'w' );
        $time_cut_off = strtotime( '15:00:00' );

        if ( 6 == $dayofweek ) {
            // If its saturday.
            $date = gmdate( 'Y-m-d', strtotime( '+ 2 days', strtotime( $current_time ) ) );
        } elseif ( 7 == $dayofweek ) {
            // If its sunday.
            $date = gmdate( 'Y-m-d', strtotime( '+ 1 day', strtotime( $current_time ) ) );
        } elseif ( 5 == $dayofweek ) {
            // If its more or equal 15, request go for tommorow.
            if ( strtotime( $current_time ) >= $time_cut_off || gmdate( 'H:m:s', strtotime( $pickup_from ) ) >= $time_cut_off ) {
                $date = gmdate( 'Y-m-d', strtotime( '+ 3 days', strtotime( $current_time ) ) );
            } else {
                $date = current_time( 'Y-m-d' );
            }
        } else {
            if ( strtotime( $current_time ) >= $time_cut_off || gmdate( 'H:m:s', strtotime( $pickup_from ) ) >= $time_cut_off ) {
                $date = gmdate( 'Y-m-d', strtotime( '+ 1 days', strtotime( $current_time ) ) );
            } else {
                $date = current_time( 'Y-m-d' );
            }
        }

        $warehouses      = $this->get_option_like( 'warehouse' );
        $warehouses_html = '';

        foreach ( $warehouses as $warehouse ) {
            $warehouses_html .= '<option value="' . $warehouse['option_name'] . '">' . $warehouse['option_value']['name'] . '</option>';
        }
        // @codingStandardsIgnoreStart
        echo '
			<div id="request-dpd-courier" style="display:none;">
				<div class="panel woocommerce_options_panel">
					<form action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" method="get">
						<input type="hidden" name="action" value="dpd_request_courier">
						' . wp_nonce_field( 'dpd-request-courier' ) . '
						<div class="options_group">
							<p class="form-field">
								<label for="dpd_warehouse">' . __( 'Select warehouse', 'woo-shipping-dpd-baltic' ) . ' *</label>
								<select id="dpd_warehouse" name="dpd_warehouse" class="select short" required style="width: 100%;">
									' . html_entity_decode( esc_attr( $warehouses_html ) ) . '
								</select>
							</p>
							<p class="form-field">
								<label for="dpd_note">' . esc_html__( 'Comment for courier', 'woo-shipping-dpd-baltic' ) . '</label>
								<textarea name="dpd_note" id="dpd_note" rows="2" cols="20" style="width: 100%;"></textarea>
							</p>
							<p class="form-field">
								<label for="dpd_pickup_date">' . esc_html__( 'Pickup date', 'woo-shipping-dpd-baltic' ) . ' *</label>
								<input type="text" name="dpd_pickup_date" id="dpd_pickup_date" class="dpd_datepicker" value="' . esc_attr( $date ) . '" required style="width: 100%;">
							</p>
							<p class="form-field">
								<label for="dpd_pickup_from">' . esc_html__( 'Pickup time from', 'woo-shipping-dpd-baltic' ) . ' *</label>
								<input type="text" name="dpd_pickup_from" id="dpd_pickup_from" value="' . esc_attr( $pickup_from ) . '" required style="width: 100%;">
							</p>
							<p class="form-field">
								<label for="dpd_pickup_until">' . esc_html__( 'Pickup time until', 'woo-shipping-dpd-baltic' ) . ' *</label>
								<input type="text" name="dpd_pickup_until" id="dpd_pickup_until" value="' . esc_attr( $pickup_until ) . '" required style="width: 100%;">
							</p>
							<p class="form-field">
								<label for="dpd_parcels">' . esc_html__( 'Count of parcels', 'woo-shipping-dpd-baltic' ) . ' *</label>
								<input type="number" name="dpd_count_parcels" id="dpd_count_parcels" value="1" min="1" step="1" required style="width: 100%;">
							</p>
							<p class="form-field">
								<label for="dpd_pallets">' . esc_html__( 'Count of pallets', 'woo-shipping-dpd-baltic' ) . ' *</label>
								<input type="number" name="dpd_pallets" id="dpd_pallets" value="0" min="0" step="1" required style="width: 100%;">
							</p>
							<p class="form-field">
								<label for="dpd_weight">' . esc_html__( 'Total weight', 'woo-shipping-dpd-baltic' ) . ' (kg) *</label>
								<input type="number" name="dpd_weight" id="dpd_weight" value="0.1" min="0.1" step="any" required style="width: 100%;">
							</p>
						</div>
						<div class="options_group">
							<p>
								<button type="submit" class="button button-primary">' . esc_html__( 'Request courier pickup', 'woo-shipping-dpd-baltic' ) . '</button>
							</p>
						</div>
					</form>
				</div>
			</div>
			<script type="text/javascript">
				jQuery(document).ready(function(){
					var today = new Date();
					var tomorrow = new Date();
					tomorrow.setDate(today.getDate()+1);
					
					jQuery(".dpd_datepicker").datepicker({
						dateFormat : "yy-mm-dd",
						firstDay: 1,
						minDate: today,
						beforeShowDay: jQuery.datepicker.noWeekends
					});
					
					jQuery(".dpd_datepicker_upcoming").datepicker({
						dateFormat : "yy-mm-dd",
						firstDay: 1,
						minDate: tomorrow,
						beforeShowDay: jQuery.datepicker.noWeekends
					});
				});
			</script>
		';
        // @codingStandardsIgnoreEnd
    }

    /**
     * Manifest popup.
     */
    public function manifest_popup() {
        // @codingStandardsIgnoreStart
        echo '
			<div id="close-dpd-manifest" style="display:none;">
				<div class="panel woocommerce_options_panel">
					<form action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" method="get">
						<input type="hidden" name="action" value="dpd_close_manifest">
						' . wp_nonce_field( 'dpd-close-manifest' ) . '
						<div class="options_group">
							<p>' . esc_attr( __( 'Do you really want to close today\'s manifest?', 'woo-shipping-dpd-baltic' ) ) . '</p>
						</div>
						<div class="options_group">
							<p>
								<button type="submit" class="button button-primary">' . esc_html__( 'Close manifest', 'woo-shipping-dpd-baltic' ) . '</button>
							</p>
						</div>
					</form>
				</div>
			</div>
		';
        // @codingStandardsIgnoreEnd
    }

    /**
     * Declaring extension (in)compatibility HPOS.
     */
    public function dpdDeclaringExtensionCompatibilityHPOS() {
        $woocommerce_version = dpd_get_woocommerce_version();

        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            if($woocommerce_version >= DPD_COMPATIBILITY_MINIMUM_HPOS_WOOCOMMERCE_VERSION){
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', 'woo-shipping-dpd-baltic/dpd.php', true );
            } else {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', 'woo-shipping-dpd-baltic/dpd.php', false );
            }
        }
    }
}
