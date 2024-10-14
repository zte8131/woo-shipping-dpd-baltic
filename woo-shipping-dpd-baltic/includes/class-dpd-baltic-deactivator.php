<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://dpd.com
 * @since      1.0.0
 *
 * @package    Dpd
 * @subpackage Dpd/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Dpd
 * @subpackage Dpd/includes
 * @author     DPD
 */
class Dpd_Baltic_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		self::delete_tables();
		self::delete_cron_jobs();
	}

	/**
	 * Delete tables.
	 *
	 * @return void
	 */
	private static function delete_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$tables = array(
			// "{$wpdb->prefix}dpd_barcodes",
			// "{$wpdb->prefix}dpd_manifests",
				"{$wpdb->prefix}dpd_terminals",
		);

		foreach ( $tables as $table ) {
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %s', $table ) );
		}
	}

	/**
	 * Delete cron jobs.
	 *
	 * @return void
	 */
	private static function delete_cron_jobs() {
		wp_clear_scheduled_hook( 'dpd_parcels_updater' );
	}
}
