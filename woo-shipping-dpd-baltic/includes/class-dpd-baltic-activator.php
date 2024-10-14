<?php
/**
 * Dpd Baltic Activator File Doc.
 *
 * @category Dpd
 * @package  Activator
 * @author   DPD
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Dpd
 * @subpackage Dpd/includes
 * @author     DPD
 */
class Dpd_Baltic_Activator {

    const IS_CHECK_COMPATIBILITY_VERSION = false;

	/**
	 * Activate.
	 *
	 * @return void
	 */
	public static function activate() {
		$is_compatible_versions = self::compatible_version_checker();

		if ($is_compatible_versions) {
			self::create_tables();
			self::update_tables();
			self::create_cron_jobs();
		} else {
			$failed_message = sprintf(
				DPD_PLUGIN_NAME . ' could not be activated. The WordPress and WooCommerce versions are not compatible with this plugin. Our plugin supports WordPress versions to %s and WooCommerce version to %s',
				DPD_COMPATIBILITY_WP_VERSION,
				DPD_COMPATIBILITY_WOOCOMMERCE_VERSION
			);

			wp_die(__($failed_message, 'woo-shipping-dpd-baltic'));
		}
	}

	/**
	 * Update.
	 *
	 * @return void
	 */
	public static function update() {
		self::update_tables();
	}

	/**
	 * Create tables.
	 *
	 * @return void
	 */
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables[] = "
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dpd_barcodes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NOT NULL,
  dpd_barcode varchar(50) NOT NULL,
  PRIMARY KEY  (id),
  KEY  (order_id),
  UNIQUE KEY dpd_barcode (dpd_barcode)
) $collate;
		";

		$tables[] = "
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dpd_manifests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pdf LONGTEXT NOT NULL,
  date DATE NOT NULL,
  PRIMARY KEY  (id),
  KEY  (date)
) $collate;
		";

		$tables[] = "
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dpd_terminals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  parcelshop_id varchar(20) NOT NULL DEFAULT '',
  company varchar(40) NOT NULL DEFAULT '',
  country varchar(2) NOT NULL DEFAULT '',
  city varchar(40) NOT NULL DEFAULT '',
  pcode varchar(5) NOT NULL DEFAULT '',
  street varchar(40) NOT NULL DEFAULT '',
  email varchar(30) NULL,
  phone varchar(30) NULL,
  mon text NOT NULL,
  tue text NOT NULL,
  wed text NOT NULL,
  thu text NOT NULL,
  fri text NOT NULL,
  sat text NOT NULL,
  sun text NOT NULL,
  distance int(30) NOT NULL DEFAULT 0,
  longitude float NULL,
  latitude float NULL,
  cod tinyint(1) NOT NULL DEFAULT 0,
  status int NOT NULL DEFAULT 1,
  PRIMARY KEY  (id),
  UNIQUE KEY parcelshop_id (parcelshop_id)
) $collate;
		";

		foreach ( $tables as $table ) {
			dbDelta( $table );
		}
	}

	/**
	 * Update tables.
	 *
	 * @return void
	 */
	private static function update_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$installed_version = get_option( 'wc_shipping_dpd_baltic_db_version' );

		if ( ! defined( 'IFRAME_REQUEST' ) && DPD_NAME_VERSION != $installed_version ) {
			if ( version_compare( $installed_version, '1.1.0', '<' ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `pcode` varchar(25) NOT NULL default ''" );
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `mon` text NULL default NULL" );
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `tue` text NULL default NULL" );
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `wed` text NULL default NULL" );
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `thu` text NULL default NULL" );
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `fri` text NULL default NULL" );
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `sat` text NULL default NULL" );
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `sun` text NULL default NULL" );
			}
		}

		if ( ! defined( 'IFRAME_REQUEST' ) && DPD_NAME_VERSION != $installed_version ) {
			if ( version_compare( $installed_version, DPD_NAME_VERSION, '<' ) ) {
				self::terminalsAddStatusColumn();
			}
		}

        if(isset ($_GET['check-dpd-terminals-table'])){
            global $wpdb;
            $table = $wpdb->prefix . 'dpd_terminals';
            $results = $wpdb->get_results("SELECT * FROM $table LIMIT 1", ARRAY_A);
            print_r(json_decode(json_encode($results),true));
            wp_die("Debugging - check-dpd-terminals-table");
        }

        if(isset ($_GET['force-dpd-tables-update'])){
            self::terminalsAddStatusColumn();
            wp_die("DPD database tables have been updated successfully! Please come back to your dashboard and continue your work.");
        }

		if ( DPD_NAME_VERSION != $installed_version ) {
			delete_option( 'wc_shipping_dpd_baltic_db_version' );
			add_option( 'wc_shipping_dpd_baltic_db_version', DPD_NAME_VERSION );
		}
	}

	/**
	 * Create cron jobs.
	 *
	 * @return void
	 */
	private static function create_cron_jobs() {
		$time = time() + 1;
		wp_schedule_single_event( $time, 'dpd_parcels_receiver' );
		wp_clear_scheduled_hook( 'dpd_parcels_updater' );
		wp_schedule_event( $time + 165, 'daily', 'dpd_parcels_updater' );
	}

	/**
	 * Compatible version checker.
	 *
	 * @return bool
	 */
	public static function compatible_version_checker() {
	    if (! self::IS_CHECK_COMPATIBILITY_VERSION) {
	        return true;
        }

		if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))
			&& self::check_compatibility_with_wordpress_version()
			&& self::check_compatibility_with_woocommerce_version()
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check compatibility with wordpress version.
	 *
	 * @return bool
	 */
	public static function check_compatibility_with_wordpress_version() {
		return version_compare(get_bloginfo('version'), DPD_COMPATIBILITY_WP_VERSION, '<=');
	}

	/**
	 * Check compatibility with wordpress version.
	 *
	 * @return bool
	 */
	public static function check_compatibility_with_woocommerce_version() {
		return version_compare(dpd_get_woocommerce_version(), DPD_COMPATIBILITY_WOOCOMMERCE_VERSION, '<=');
	}

	public static function terminalsAddStatusColumn() {
		global $wpdb;
		$table = $wpdb->prefix . 'dpd_terminals';
		$row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$table}' AND column_name = 'status'"  );

		if(empty($row)){
			$wpdb->query("ALTER TABLE {$table} ADD status INT NULL DEFAULT 1");
		}
	}
}
