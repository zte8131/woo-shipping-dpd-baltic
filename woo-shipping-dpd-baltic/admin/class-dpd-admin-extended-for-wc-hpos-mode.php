<?php
/**
 * Dpd Admin in WooCommerce HPOS Mode
 *
 * @category Admin
 * @package  Dpd
 * @author   DPD
 */

/**
 * The admin-specific functionality of the plugin adapt for callbacks of the WooCommerce Hooks if HPOS mode is Enabled
 *
 * @package    Dpd
 * @subpackage Dpd/admin
 * @author     DPD
 */
class Dpd_Admin_Extended_For_Wc_Hpos_Mode extends Dpd_Admin
{

    public function __construct($plugin_name, $version)
    {
        parent::__construct($plugin_name, $version);
    }

    /**
     * Define orders bulk actions - HPOS Mode is Enabled
     *
     * @param array $actions Actions.
     *
     * @return mixed
     */
    public function define_orders_bulk_actions_in_hpos_mode($actions)
    {
        $actions['dpd_print_parcel_label'] = __('Print DPD label', 'woo-shipping-dpd-baltic');
        $actions['dpd_cancel_shipment'] = __('Cancel DPD shipments', 'woo-shipping-dpd-baltic');

        return $actions;
    }

    /**
     * Handle bulk actions.
     *
     * @param string $redirect_to URL to redirect to.
     * @param string $action Action name.
     * @param array $ids List of ids.
     *
     * @return string
     */
    public function handle_orders_bulk_actions_in_hpos_mode($redirect_to, $action, $ids)
    {
        $ids = array_map('absint', $ids);
        $changed = 0;
        $report_action = '';
        if ('dpd_print_parcel_label' === $action) {
            $report_action = 'dpd_printed_parcel_label';
            $result = $this->do_multiple_print_parcel_label($ids);
            $changed = (null === $result) ? -1 : count($ids);
        } elseif ('dpd_cancel_shipment' === $action) {
            $report_action = 'dpd_canceled_shipment';

            foreach ($ids as $id) {
                $order = wc_get_order($id);

                if ($order) {
                    $this->do_cancel_shipment($order);
                    $changed++;
                }
            }
        }

        if ($changed) {
            $redirect_to = add_query_arg(
                array(
                    'bulk_action' => $report_action,
                    'changed' => $changed,
                    'ids' => implode(',', $ids),
                ),
                $redirect_to
            );
        }

        return esc_url_raw($redirect_to);
    }

    public function bulk_admin_notices_in_hpos_mode() {
        if ( !isset($_GET['page']) || $_GET['page'] !== 'wc-orders' || !isset( $_REQUEST['bulk_action'] ) ) { // WPCS: input var ok, CSRF ok.
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

}
