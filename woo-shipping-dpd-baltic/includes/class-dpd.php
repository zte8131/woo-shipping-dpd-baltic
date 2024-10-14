<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://dpd.com
 * @since      1.0.0
 *
 * @package    Dpd
 * @subpackage Dpd/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Dpd
 * @subpackage Dpd/includes
 * @author     DPD
 */
class Dpd
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Dpd_Baltic_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('DPD_NAME_VERSION')) {
            $this->version = DPD_NAME_VERSION;
        } else {
            $this->version = '1.1.0';
        }
        $this->plugin_name = 'woo-shipping-dpd-baltic';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Dpd_Baltic_Loader. Orchestrates the hooks of the plugin.
     * - Dpd_Baltic_I18n. Defines internationalization functionality.
     * - Dpd_Admin. Defines all hooks for the admin area.
     * - Dpd_Baltic_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-dpd-baltic-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-dpd-baltic-i18n.php';

        /**
         * Helpers.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/helpers.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-dpd-admin.php';

        /**
         * The class responsible for defining all actions that occur in the admin area - adapting for WooCommerce HPOS mode is enabled
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-dpd-admin-extended-for-wc-hpos-mode.php';


        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-dpd-baltic-public.php';

        /**
         * Plugin AJAX methods.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-dpd-baltic-ajax.php';

        /**
         * Dial code helper.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-dpd-baltic-dial-code-helper.php';

        $this->loader = new Dpd_Baltic_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Dpd_Baltic_I18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Dpd_Baltic_I18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Dpd_Admin_Extended_For_Wc_Hpos_Mode($this->get_plugin_name(), $this->get_version());
        $plugin_ajax = new Dpd_Baltic_Ajax();

        $this->loader->add_action('woocommerce_get_settings_pages', $plugin_admin, 'get_settings_pages');

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        $this->loader->add_action('woocommerce_shipping_init', $this, 'dpd_shipping_methods');
        $this->loader->add_filter('woocommerce_shipping_methods', $this, 'add_dpd_shipping_methods');
        // Add free shipping.
        $this->loader->add_filter('woocommerce_package_rates', $this, 'dpd_has_free_shipping', 20, 2);

        $this->loader->add_action('woocommerce_email', $this, 'load_shipping_method', 1, 1);

        $this->loader->add_action('dpd_parcels_receiver', $plugin_admin, 'get_all_parcels_list');
        $this->loader->add_action('dpd_parcels_updater', $plugin_admin, 'update_all_parcels_list');
        $this->loader->add_action('dpd_parcels_country_update', $plugin_admin, 'country_parcels_list', 10, 1);

        // Custom order actions.
        $this->loader->add_action('woocommerce_order_actions_start', $plugin_admin, 'order_actions_metabox_dpd', 10, 1);
        $this->loader->add_action('woocommerce_process_shop_order_meta', $plugin_admin, 'save_order_actions_meta_box', 0, 2);
        $this->loader->add_action('woocommerce_order_actions', $plugin_admin, 'add_order_actions');
        $this->loader->add_action('woocommerce_order_action_dpd_print_parcel_label', $plugin_admin, 'do_print_parcel_label');
        $this->loader->add_action('woocommerce_order_action_dpd_parcel_status', $plugin_admin, 'do_get_parcel_status');
        $this->loader->add_action('woocommerce_order_action_dpd_cancel_shipment', $plugin_admin, 'do_cancel_shipment');
        $this->loader->add_action('woocommerce_order_action_dpd_collection_request', $plugin_ajax, 'dpd_order_reverse_collection_request');

        // Renders warehouses settings.
        $this->loader->add_action('woocommerce_settings_dpd_warehouses', $plugin_admin, 'settings_dpd_warehouses');

        // Renders manifests table.
        $this->loader->add_action('woocommerce_settings_dpd_manifests', $plugin_admin, 'settings_dpd_manifests');
        // Download manifest action.
        $this->loader->add_action('init', $plugin_admin, 'download_manifest');

        // Renders collect form.
        $this->loader->add_action('woocommerce_settings_dpd_collect', $plugin_admin, 'settings_dpd_collect');

        // Remove selected warehouse.
        $this->loader->add_action('wp_ajax_delete_warehouse', $plugin_admin, 'delete_warehouse');

        // Bulk order actions HOOKs adapting for HPOS mode
        $this->loader->add_filter('bulk_actions-woocommerce_page_wc-orders', $plugin_admin, 'define_orders_bulk_actions_in_hpos_mode', 10);
        $this->loader->add_filter('handle_bulk_actions-woocommerce_page_wc-orders', $plugin_admin, 'handle_orders_bulk_actions_in_hpos_mode', 10, 3);

        // Bulk order actions HOOKs adapting for Legacy mode
        $this->loader->add_filter('bulk_actions-edit-shop_order', $plugin_admin, 'define_orders_bulk_actions', 10);
        $this->loader->add_filter('handle_bulk_actions-edit-shop_order', $plugin_admin, 'handle_orders_bulk_actions', 10, 3);
        $this->loader->add_filter('admin_notices', $plugin_admin, 'bulk_admin_notices_in_hpos_mode');

        // Request courier pop-up.
        $this->loader->add_action('wp_ajax_dpd_request_courier', $plugin_ajax, 'dpd_request_courier');
        $this->loader->add_action('wp_ajax_dpd_close_manifest', $plugin_ajax, 'dpd_close_manifest');
        $this->loader->add_filter('admin_notices', $plugin_admin, 'bulk_admin_notices');

        // Request order collection.
        $this->loader->add_action('wp_ajax_dpd_order_collection_request', $plugin_ajax, 'dpd_order_collection_request');

        $this->loader->add_action('admin_footer', $plugin_admin, 'courier_popup', 100);
        $this->loader->add_action('admin_footer', $plugin_admin, 'manifest_popup', 110);

        //  Custom product shipping class
        $this->loader->add_action('woocommerce_product_options_shipping_product_data', $plugin_admin, 'dpdAddShippingCustomField', 10);
        $this->loader->add_action('woocommerce_admin_process_product_object', $plugin_admin, 'dpdCustomSaveShippingCustomField', 10, 1);

        // Declaring extension (in)compatibility HPOS
        $this->loader->add_action('before_woocommerce_init', $plugin_admin, 'dpdDeclaringExtensionCompatibilityHPOS');

        add_action('admin_notices', 'dpd_baltic_display_flash_notices', 12);

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Dpd_Baltic_Public($this->get_plugin_name(), $this->get_version());
        $plugin_ajax = new Dpd_Baltic_Ajax();

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        $this->loader->add_filter('woocommerce_locate_template', $plugin_public, 'locate_template', 20, 3);
        $this->loader->add_filter('woocommerce_locate_core_template', $plugin_public, 'locate_template', 20, 3);

        $this->loader->add_action('woocommerce_shipping_init', $this, 'dpd_shipping_methods');
        $this->loader->add_filter('woocommerce_shipping_methods', $this, 'add_dpd_shipping_methods');
        // Add free shipping.
        $this->loader->add_filter('woocommerce_package_rates', $this, 'dpd_has_free_shipping', 20, 2);

        $this->loader->add_action('woocommerce_email', $this, 'load_shipping_method', 1, 1);

        $this->loader->add_action('woocommerce_checkout_update_order_review', $plugin_ajax, 'checkout_save_session_fields', 10, 1);

        $this->loader->add_action('wp_ajax_set_checkout_session', $plugin_public, 'set_checkout_session');
        $this->loader->add_action('wp_ajax_nopriv_set_checkout_session', $plugin_public, 'set_checkout_session');

        // AJAX methods.
        $this->loader->add_action('wc_ajax_get_dpd_parcels', $plugin_ajax, 'get_ajax_terminals');
        $this->loader->add_action('wc_ajax_nopriv_get_dpd_parcels', $plugin_ajax, 'get_ajax_terminals');

        $this->loader->add_action('wc_ajax_choose_dpd_terminal', $plugin_ajax, 'ajax_save_session_terminal');
        $this->loader->add_action('wc_ajax_nopriv_choose_dpd_terminal', $plugin_ajax, 'ajax_save_session_terminal');

        // Available payment methods.
        $this->loader->add_filter('woocommerce_available_payment_gateways', $plugin_public, 'available_payment_gateways', 10, 1);

        // COD fee.
        $this->loader->add_action('woocommerce_cart_calculate_fees', $plugin_public, 'add_cod_fee', 10, 2);

        $this->loader->add_action('wp_ajax_load_more_items', $this, 'load_more_pudos');
        $this->loader->add_action('wp_ajax_nopriv_load_more_items', $this, 'load_more_pudos');

        // Checkout blocks
        $this->loader->add_action('wp_ajax_load_additional_block', $this, 'get_ajax_terminals_new');
        $this->loader->add_action('wp_ajax_nopriv_load_additional_block', $this, 'get_ajax_terminals_new');

        $this->loader->add_action('wp_ajax_nopriv_dpd_checkout_get_pickup_points_blocks', $this, 'checkout_get_pickup_points_blocks_new');
        $this->loader->add_action('wp_ajax_dpd_checkout_get_pickup_points_blocks', $this, 'checkout_get_pickup_points_blocks_new');

        $this->loader->add_action('wp_ajax_nopriv_dpd_store_pickup_selection', $this, 'dpd_save_pickup_location');
        $this->loader->add_action('wp_ajax_dpd_store_pickup_selection', $this, 'dpd_save_pickup_location');

        $this->loader->add_action('woocommerce_store_api_checkout_update_order_from_request', $this, 'function_test');

        $this->loader->add_action('wp_ajax_nopriv_get_data', $this, 'get_terminal_classic_blocks');
        $this->loader->add_action('wp_ajax_get_data', $this, 'get_terminal_classic_blocks');

        $this->loader->add_action('wp_ajax_search_pudo', $this, 'get_ajax_search_pudo');
        $this->loader->add_action('wp_ajax_nopriv_search_pudo', $this, 'get_ajax_search_pudo');

    }

    public function get_ajax_search_pudo()
    {
        $search_value = $_REQUEST['search_value'];

        $chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

        $limit = 100;
        $page = 1;
        $offset = $limit * ($page - 1);

        global $wpdb;

        $countries = get_option( 'dpd_parcels_countries', array( 'LT', 'LV', 'EE' ) );

        $items_per_page = $limit + 1;


        $country = WC()->customer->get_shipping_country();
        if ( $country ) {
            if (!in_array($country, $countries)) {
                dpd_debug_log("FRONT-STORE: CHECKOUT PROCESS - no selected country is matching with countries in settings");
                return [];
            }

            $terminals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE country = %s AND (company like %s or city LIKE %s or street like %s or pcode like %s) AND status = 1 ORDER BY company LIMIT %d OFFSET %d", $country, '%' . $wpdb->esc_like( $search_value ) . '%', $wpdb->esc_like( $search_value ) . '%', '%' . $wpdb->esc_like( $search_value ) . '%', '%' . $wpdb->esc_like( $search_value ) . '%', $items_per_page, $offset ) );
            dpd_debug_log(sprintf("FRONT-STORE: CHECKOUT PROCESS - total terminals match with selected country: %d", is_countable($terminals) ? count( $terminals ) : -1));
        } else {
            $terminals = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE status = 1 AND (company like %s or city LIKE %s or street like %s or pcode like %s) ORDER BY company LIMIT %d OFFSET %d", '%' . $wpdb->esc_like( $search_value ) . '%', $wpdb->esc_like( $search_value ) . '%', '%' . $wpdb->esc_like( $search_value ) . '%', '%' . $wpdb->esc_like( $search_value ) . '%', $items_per_page, $offset));
            dpd_debug_log(sprintf("FRONT-STORE: CHECKOUT PROCESS - total terminals are NOT matching with selected country: %d", is_countable($terminals) ? count( $terminals ) : -1));

        }

        $terminals_pagination = $terminals;

        if ( count( $terminals_pagination ) > $limit ) {
            array_pop( $terminals_pagination );
        } else {
            $page = -1;
        }

        $grouped_terminals = array();

        foreach ( $terminals_pagination as $terminal ) {

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
                $terminal_formatted_name = $terminal->company . ', ' . $terminal->street;
                $grouped_terminals[ $group ][ $terminal_key ]->name = $terminal_formatted_name;
            }
        }

        $terminal_field_name = 'wc_shipping_dpd_parcels_terminal';

        $html = '';
        $html .= '<input type="text" class="js--pudo-search" value="' . $search_value .'" style="width:100%; padding: 1rem;" placeholder="' . esc_html( __( 'Search', 'woo-shipping-dpd-baltic' ) )  .'">';

        if (!count($grouped_terminals)) {
            $html .= '<li class="pudo" data-value="">' . esc_html( __( 'The Pickup Point is empty', 'woo-shipping-dpd-baltic' ) ) . '</li>';
        }else {
            foreach ( $grouped_terminals as $group_name => $locations ) {
                $html .= '<li class="group-pudo">' . esc_html( $group_name ) . '</li>';
                foreach ($locations as $location ) {
                    if ($location->status == '1') {
                        $html .= '<li class="pudo" data-cod="' . esc_attr( $location->cod ) . '" data-value="' . esc_html( $location->parcelshop_id ) .'">' . esc_html( $location->name ) . '</li>';
                    }
                }
            }
            if ($page != -1) {
                $class='';
            }else {
                $class='hidden';
            }
            $html .= '<div id="load-more-btn" class="' . $class .'" load-more-page="' . $page .'"><span class="load-more button">Load More</span></div>';
        }

        echo $html;

        wp_die();
    }
    public function get_terminal_classic_blocks() {
        $search_text = $_GET['q'];


        global $wpdb;

        if ( WC()->customer->get_shipping_country()) {
            $country = WC()->customer->get_shipping_country();
        }elseif(WC()->customer->get_billing_country()){
            $country = WC()->customer->get_billing_country();
        }else {
            $country = false;
        }



        if ( $country ) {

//            $terminals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE country = %s WHERE city LIKE '%warsz' ORDER BY city", $country ) );
            if ($search_text) {
//                $terminals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE country = %s AND city LIKE %s OR company like %s ORDER BY city", $country, $wpdb->esc_like( $search_text ) . '%', $wpdb->esc_like( $search_text ) . '%') );
                $terminals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE country = %s AND (company like %s or city LIKE %s or street like %s or pcode like %s)  ORDER BY city", $country, '%' . $wpdb->esc_like( $search_text ) . '%', $wpdb->esc_like( $search_text ) . '%', '%' . $wpdb->esc_like( $search_text ) . '%', '%' . $wpdb->esc_like( $search_text ) . '%') );
            }else {
                $terminals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE country = %s ORDER BY city", $country ) );
            }

        } else {
            if ($search_text) {
                $terminals = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE company LIKE %s or city LIKE %s or street like %s or pcode like %s ORDER BY city", '%' . $wpdb->esc_like( $search_text ) . '%', $wpdb->esc_like( $search_text ) . '%', '%' . $wpdb->esc_like( $search_text ) . '%', '%' . $wpdb->esc_like( $search_text ) . '%'  );
            }else {
                $terminals = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dpd_terminals ORDER BY city" );
            }

        }

        echo json_encode( $terminals );
        die;

    }
    public function function_test($order) {

        $value = WC()->session->get( 'value');

        $chosen_shipping_method = WC()->session->get( 'chosen_shipping_methods' )[0];

        $word = "parcels";
//        $word = "home_delivery";
//        $word2 = "sat_home_delivery";
//        $word3 = "same_day_delivery";



        if (strpos($chosen_shipping_method, $word) > 0) {
            if ($value == NULL) {
                wc_add_notice( __("Please select the pickup location", 'woo-shipping-dpd-baltic'), 'error' );
            }
        }

        global $wpdb;

        $terminal = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE parcelshop_id = %s", $value ) );

        $terminal = json_decode(json_encode($terminal), true);



        $terminal_name = $terminal[0]['company'] . ',' . $terminal[0]['street'];

        $order->update_meta_data( 'wc_shipping_dpd_parcels_terminal', $value );
        $order->update_meta_data( 'wc_shipping_dpd_parcels_terminal_name', $terminal_name );
        $order->save();

        WC()->session->__unset( 'value' );
    }

    public function dpd_save_pickup_location()
    {
        $value = $_REQUEST['value'];

        WC()->session->set( 'value', $value );
    }

    /**
     * Load more items.
     *
     */
    public function checkout_get_pickup_points_blocks_new()
    {
        global $wpdb;

        if ( WC()->customer->get_shipping_country()) {
            $country = WC()->customer->get_shipping_country();
        }else {
            $country = false;
        }

        if ( $country ) {
            $terminals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE country = %s ORDER BY city", $country ) );
        } else {
            $terminals = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dpd_terminals ORDER BY city" );
        }

        $optionsForSelect2 = [];

        $optionsForSelect2[] = [

            'id'          => '',

            'first_line'  => __('Please select the pickup location', 'woo-shipping-dpd-baltic'),

            'second_line' => '',

            'text'        => __('Please select the pickup location', 'woo-shipping-dpd-baltic'),

        ];

        $terminals = json_decode(json_encode($terminals), true);

        foreach ($terminals as $key => $terminal) {

            $preparedItem        = [

                'id'          => $terminal['parcelshop_id'],

                'first_line'  => $terminal['company'],

                'second_line' => sprintf("%s, %s, %s", $terminal['street'], $terminal['city'],

                    $terminal['pcode']),

                'text'        => sprintf("%s, %s, %s, %s", $terminal['company'], $terminal['street'],

                    $terminal['city'], $terminal['country'].'-'.$terminal['pcode']),

                'location'    => $terminal,

            ];

            $optionsForSelect2[] = $preparedItem;
        }

        echo json_encode([

            'all'           => $optionsForSelect2,

        ]);



        wp_die();


    }
    public function get_ajax_terminals_new(){

        $html = '';

        $html .= '<div id="dpd-wc-pickup-point-shipping-block" style="display: none; padding-top: 0.5em;">';

        $html .= '<div class="form-row form-row-wide">';

        $html .= '<strong class="mp-please-select-location">';

        $html .=  __( 'Pickup location', 'woo-shipping-dpd-baltic' );

        $html .= '</strong>';

        $html .= '<select id="dpd-wc-pickup-point-shipping-select-block" name="dpd_location_identifier" style="width:100%">';

        $html .= '<option value="' . __('Please select the pickup location','woo-shipping-dpd-baltic') .'"></option>';

        $html .= '</select>';

        $html .= '</div>';

        $html .= '</div>';

        echo $html;

        wp_die();

    }
    public function load_more_pudos()
    {
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $limit = 500;
//        $limit = 100;


        $pudos = $this->get_additional_pudo($limit, $page);


        $has_more = false;
        if (count($pudos) > $limit) {
            $has_more = true;
            array_pop($pudos);
        }

        wp_send_json_success([
            'listPudos' => $pudos,
            'hasMore' => $has_more
        ]);
    }

    /**
     * Get additional pudo.
     *
     * @param $limit
     * @param $page
     * @return array
     */
    public function get_additional_pudo($limit, $page)
    {
        $offset = $limit * ($page - 1);

        if (!class_exists('DPD_Parcels')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-dpd-parcels.php';
        }

        $dpd_parcels = new DPD_Parcels();
        $limit += 1;

        return $dpd_parcels->get_terminals_pagination(WC()->customer->get_shipping_country(), $limit, $offset);
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Dpd_Baltic_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Dpd shipping methods.
     *
     * @return void
     * @since 1.0.0
     */
    public function dpd_shipping_methods()
    {
        if (!class_exists('DPD_Home_Delivery')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-dpd-home-delivery.php';
        }

        if (!class_exists('DPD_Same_Day_Delivery')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-dpd-same-day-delivery.php';
        }

        if (!class_exists('DPD_Home_Delivery_Sat')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-dpd-home-delivery-sat.php';
        }

        if (!class_exists('DPD_Parcels')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-dpd-parcels.php';
        }

        if (!class_exists('DPD_Same_Day_Parcels')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-dpd-same-day-parcels.php';
        }

        $dpd_home_delivery = new DPD_Home_Delivery();
        $dpd_home_delivery->init_actions_and_filters();

        $dpd_same_day_delivery = new DPD_Same_Day_Delivery();
        $dpd_same_day_delivery->init_actions_and_filters();

        $dpd_same_day_delivery_sat = new DPD_Home_Delivery_Sat();
        $dpd_same_day_delivery_sat->init_actions_and_filters();

        $dpd_parcels = new DPD_Parcels();
        $dpd_parcels->init_actions_and_filters();

        $dpd_same_day_parcels = new DPD_Same_Day_Parcels();
        $dpd_same_day_parcels->init_actions_and_filters();
    }

    /**
     * Sita funkcija vykdomas tiek back tiek front
     * jei esam admine tiesiog grazinam metodus kaip yra
     * jei fronte darom logiak
     *
     * @param array $methods Methods.
     *
     * @return mixed
     */
    public function add_dpd_shipping_methods($methods)
    {
        $methods['dpd_home_delivery'] = 'DPD_Home_Delivery';
        $methods['dpd_sameday_delivery'] = 'DPD_Same_Day_Delivery';
        $methods['dpd_sat_home_delivery'] = 'DPD_Home_Delivery_Sat';
        $methods['dpd_parcels'] = 'DPD_Parcels';
        $methods['dpd_sameday_parcels'] = 'DPD_Same_Day_Parcels';

        return $methods;
    }

    /**
     * Dpd has free shipping.
     *
     * @param array $rates Package.
     * @param array $package Rates.
     */
    public function dpd_has_free_shipping($rates, $package)
    {

        $has_free_shipping = false;

        $applied_coupons = WC()->cart->get_applied_coupons();
        foreach ($applied_coupons as $coupon_code) {
            $coupon = new WC_Coupon($coupon_code);
            if ($coupon->get_free_shipping()) {
                $has_free_shipping = true;
                break;
            }
        }

        foreach ($rates as $rate_key => $rate) {
            if ($has_free_shipping) {
                if ('free_shipping' === $rate->method_id) {
                    unset($rates[$rate_key]);
                } elseif ('dpd_home_delivery' === $rate->method_id || 'dpd_parcels' === $rate->method_id) {
                    // $rates[$rate_key]->label .= ': ' . __('0.00', 'woocommerce');

                    $rates[$rate_key]->cost = 0;

                    $taxes = array();
                    foreach ($rates[$rate_key]->taxes as $key => $tax) {
                        if ($rates[$rate_key]->taxes[$key] > 0) {
                            $taxes[$key] = 0;
                        }
                    }
                    $rates[$rate_key]->taxes = $taxes;
                }
            }
        }
        return $rates;
    }

    /**
     * Load shipping method.
     *
     * @param array $order_id Order id.
     */
    public function load_shipping_method($order_id)
    {
        WC()->shipping();
    }
}
