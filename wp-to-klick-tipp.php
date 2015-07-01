<?php
    /*
     * Plugin Name:       Wordpress to Klick-Tipp
     * Plugin URI:
     * Description:       Action based user data sync, between Wordpress/WooCommerce and Klick-Tipp (tag based email marketing)
     * Version:           2.0.1
     * Author:            Tobias B. Conrad
     * Author URI:        http://www.woocommerce-klick-tipp.com
     * License:           GPL v3
     * Text Domain: wptkt
     * Domain Path: /languages
     *
     * Copyright (C) 2015
     *
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or
     * (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with this program.  If not, see <http://www.gnu.org/licenses/>.
     *
     */
    if (basename($_SERVER['SCRIPT_FILENAME']) == 'wp-to-klick-tipp.inc.php') { die ("Please do not access this file directly. Thanks!"); }

    if (!defined( 'WPINC')) {
        die();
    }
	error_reporting(0);
	
	global $wpdb;
	$aWPUploadDir = wp_upload_dir();

    define('WP_TO_KLICK_TIPP_DIR', plugin_dir_path(__FILE__));
    define('WP_TO_KLICK_TIPP_URL', plugin_dir_url(__FILE__));
	define('WP_TO_KLICK_TIPP_PLUGIN_NAME', __('Wordpress to Klick-Tipp','wptkt'));
    define('WP_UPLOAD_PATH', $aWPUploadDir['path']);
    define('WP_UPLOAD_URL', $aWPUploadDir['url']);
	define('WC_ACTIVE_FLAG',(class_exists('WooCommerce') ? TRUE : FALSE));
    define('WP_TO_KLICK_TIPP_VERSION', '2.0.1');
    define('WP_TO_KLICK_TIPP_DB_VERSION', '2.0.0');
    define('WP_TO_KLICK_TIPP_TABLE_LOG', $wpdb->prefix . 'wptkt_log');

    require_once(WP_TO_KLICK_TIPP_DIR . 'inc/class.wp-to-klick-tipp.php');

    $wptktApp = new WpToKlickTipp();
	$bLicenseExpiredAdminNotice = TRUE;

	register_activation_hook(__FILE__, 'wptkt_activate' );
	register_activation_hook(__FILE__, array('WpToKlickTipp', 'activate'));
	register_deactivation_hook(__FILE__, array('WpToKlickTipp', 'deactivate'));
	register_uninstall_hook(__FILE__, 'wptkt_uninstall');

    add_action('save_post', 'wptkt_save_post');
	function wptkt_save_post($ID) {
        if (get_post_type( $ID ) == 'shop_order' && get_post_meta( $ID,'_billing_email',true)) {
			$oOrder = new WC_Order($ID);
			$iUserID= $oOrder->user_id;
            require_once(plugin_dir_path( __FILE__ ) . 'inc/wpuser2kt.inc.php');
            require_once(plugin_dir_path( __FILE__ ) . 'inc/order2kt.inc.php');
        }
		return;
	}

    add_action( 'profile_update', 'wptkt_profile_update', 10, 2 );
    add_action( 'user_register', 'wptkt_profile_update', 10, 2 );
    function wptkt_profile_update($iUserID) {
        require_once(plugin_dir_path( __FILE__ ) . 'inc/wpuser2kt.inc.php');
        return $sResult;
    }
    
    add_action( 'admin_notices', function() use ($bLicenseExpiredAdminNotice) {
		$bError = FALSE;
		$sError = '<div class="error"><p><b>'.WP_TO_KLICK_TIPP_PLUGIN_NAME.':</b>&nbsp;';
		if (!get_option('wptkt_klicktipp_apikey')) {
		    $sError .= __("Until you have not set up your API credentials you cannot use ",'wptkt').'&nbsp;'.WP_TO_KLICK_TIPP_PLUGIN_NAME.'.</p>';
			$bError = TRUE; 
		}
        $iPhpVersion = preg_replace("/[^0-9]/","",substr(phpversion(),0,3));
		if ($iPhpVersion < 54) {
		    $sError .= '<p>'.__("Please install PHP version 5.4 or above on your server.",'wptkt').'</p>';
			$bError = TRUE; 
		}
		if ($bError) echo $sError.'</div>';
    });

	function wptkt_activate () {
		global $wpdb;
		$currentDbVersion = get_option( "wptkt_db_version" );
		if (($currentDbVersion != WP_TO_KLICK_TIPP_DB_VERSION) || ($wpdb->get_var("SHOW TABLES LIKE '".WP_TO_KLICK_TIPP_TABLE_LOG."'") != WP_TO_KLICK_TIPP_TABLE_LOG)) {
		    $charsetCollate = $wpdb->get_charset_collate();
		    $sql = "CREATE TABLE ".WP_TO_KLICK_TIPP_TABLE_LOG." (id mediumint(9) NOT NULL AUTO_INCREMENT,time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,module tinytext NOT NULL,text text NOT NULL,UNIQUE KEY id (id)) $charsetCollate;";
	    	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		    dbDelta( $sql );
		    update_option( 'wptkt_db_version', WP_TO_KLICK_TIPP_DB_VERSION);
			$wpdb->insert( 
			    WP_TO_KLICK_TIPP_TABLE_LOG,
    			array( 
			    'time' => current_time( 'mysql' ), 
			    'module' => 'Setup', 
			    'text' => 'New table '.WP_TO_KLICK_TIPP_DB_VERSION.' created')
	    	);
		}
        update_option('wptkt_wordpress_cron', 1);
	}
	function wptkt_uninstall() {
		global $wpdb;
        $wpdb->query( 'DROP TABLE IF EXISTS '.WP_TO_KLICK_TIPP_TABLE_LOG.';');
		delete_option('wptkt_wordpress_cron');
		delete_option('wptkt_last_export');
		delete_option('klicktiip_unsubscribe_order');
    }
?>
