<?php
/**
 * The file that defines helper function.
 *
 * @link       https://dpd.com
 * @since      1.0.0
 *
 * @package    Dpd
 * @subpackage Dpd/includes
 */

/**
 * Add a flash notice to {prefix}options table until a full page refresh is done
 *
 * @param string $notice our notice message.
 * @param string $type This can be "info", "warning", "error" or "success", "warning" as default.
 * @param boolean $dismissible set this to TRUE to add is-dismissible functionality to your notice.
 *
 * @return void
 */
function dpd_baltic_add_flash_notice($notice = '', $type = 'warning', $dismissible = true)
{
    // Here we return the notices saved on our option, if there are not notices, then an empty array is returned.
    $notices = get_option('dpd_baltic_flash_notices', array());
    $dismissible_text = ($dismissible) ? 'is-dismissible' : '';

    // We add our new notice.
    $notices[] = array(
        'notice' => $notice,
        'type' => $type,
        'dismissible' => $dismissible_text,
    );

    // Then we update the option with our notices array.
    update_option('dpd_baltic_flash_notices', $notices);
}

/**
 * Function executed when the 'admin_notices' action is called, here we check if there are notices on
 * our database and display them, after that, we remove the option to prevent notices being displayed forever.
 *
 * @return void
 */
function dpd_baltic_display_flash_notices()
{
    $notices = get_option('dpd_baltic_flash_notices', array());

    // Iterate through our notices to be displayed and print them.
    foreach ($notices as $notice) {
        echo esc_html(printf(
            '<div class="notice notice-%1$s %2$s"><p>%3$s</p></div>',
            esc_attr($notice['type']),
            esc_attr($notice['dismissible']),
            $notice['notice']
        ));
    }

    // Now we reset our options to prevent notices being displayed forever.
    if (!empty($notices)) {
        delete_option('dpd_baltic_flash_notices');
    }
}

/**
 * Helper function to convert weight to kg.
 *
 * @param mixed $cart_weight Cart weight.
 */
function dpd_baltic_weight_in_kg($cart_weight)
{
    $shop_weight_unit = get_option('woocommerce_weight_unit');

    if ('oz' === $shop_weight_unit) {
        $divider = 35.274;
    } elseif ('lbs' === $shop_weight_unit) {
        $divider = 2.20462;
    } elseif ('g' === $shop_weight_unit) {
        $divider = 1000;
    } else {
        $divider = 1;
    }

    return $cart_weight / $divider;
}

/**
 * Helper function to get woocommerce version.
 *
 * @return string
 */
function dpd_get_woocommerce_version()
{
    if (!function_exists('get_plugins')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    $plugin_folder = get_plugins('/' . 'woocommerce');
    $plugin_file = 'woocommerce.php';

    return $plugin_folder[$plugin_file]['Version'] ?? 'NULL';
}

/**
 * DPD Check if WooCommerce HPOS Mode is Enabled or NOT
 * @return bool
 */
function dpd_check_if_enabled_WC_HPOS_Mode(): bool
{
    if (class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class)) {
        return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }
    return false;
}

/**
 * DPD Get Order Meta adapting for two WooCommerce Feature Modes
 * - Legacy
 * - HPOS
 * @param int $order_id
 * @param string $meta_key
 * @param bool $single
 * @return mixed
 */
function dpd_get_order_meta($order_id, $meta_key = '', $single = true)
{
    $enabledHPOS = dpd_check_if_enabled_WC_HPOS_Mode();
    if ($enabledHPOS) {
        dpd_debug_log('HPOS usage is enabled - dpd_get_order_meta from COT Meta');
        $order = wc_get_order($order_id);
        if ($order) {
            return $order->get_meta($meta_key, $single);
        }
    }
    dpd_debug_log('Traditional CPT-based orders are in use. - dpd_get_order_meta from CPT - PostMeta');
    return get_post_meta($order_id, $meta_key, $single);
}

/**
 * DPD Update Order Meta Data adapting for two WooCommerce Feature Modes
 * Always perform for both modes adapts for preserving Order Meta Data even if Order Context is running in Legacy or HPOS
 * @param int $order_id
 * @param string $meta_key
 * @param string $meta_value Metadata value. Must be serializable if non-scalar.
 * @return void
 */
function dpd_update_order_meta($order_id, $meta_key, $meta_value)
{
    $postMetaExisted = get_post_meta($order_id, $meta_key);
    if (!$postMetaExisted) {
        dpd_debug_log('dpd_update_order_meta - ADD metadata to CPT-based');
        add_post_meta($order_id, $meta_key, $meta_value);
    } else {
        dpd_debug_log('dpd_update_order_meta - UPDATE metadata to CPT-based');
        update_post_meta($order_id, $meta_key, $meta_value);
    }

    $order = wc_get_order($order_id);
    if ($order) {
        if (!$order->meta_exists($meta_key)) {
            dpd_debug_log('dpd_update_order_meta - ADD metadata to COT');
            $order->add_meta_data($meta_key, $meta_value);
        } else {
            dpd_debug_log('dpd_update_order_meta - UPDATE metadata to COT');
            $order->update_meta_data($meta_key, $meta_value);
        }
        $order->save();
    }
}

/**
 * Adds a debug level message.
 *
 * Detailed debug information.
 *
 * @param string $message Log message.
 * @param array $context Optional. Additional information for log handlers.
 */
function dpd_debug_log($message, $context = array())
{
    if (!dpd_enabled_logging_mode()) {
        return;
    }
    wc_get_logger()->debug($message, $context);
}

/**
 * Adds a info level message.
 *
 * Interesting events.
 * Example: DPD tracking working flows logs in, SQL logs.
 *
 * @param string $message Log message.
 * @param array $context Optional. Additional information for log handlers.
 */
function dpd_info_log($message, $context = array())
{
    if (!dpd_enabled_logging_mode()) {
        return;
    }
    wc_get_logger()->info($message, $context);
}

/**
 * Adds an error level message.
 *
 * Runtime errors that do not require immediate action but should typically be logged
 * and monitored.
 *
 * @param string $message Log message.
 * @param array $context Optional. Additional information for log handlers.
 */
function dpd_error_log($message, $context = array())
{
    if (!dpd_enabled_logging_mode()) {
        return;
    }
    wc_get_logger()->error($message, $context);
}

/**
 * Check if DPD is enabled LOGGING Mode or NOT
 * @return bool
 */
function dpd_enabled_logging_mode(): bool
{
    $dpdLoggingMode = get_option('dpd_logging_mode');
    return !empty($dpdLoggingMode) && 'yes' === $dpdLoggingMode;
}