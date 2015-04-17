<?php
    /*
     * Plugin Name:       Wordpress to Klick-Tipp
     * Plugin URI:
     * Description:       Sync user data between Wordpress / WooCommerce and Klick-Tipp (tag based e-mail marketing)
     * Version:           1.1
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
     *
     * Requirements for this Plugin:
     * - PHP curl Library
     */

    if (!defined( 'WPINC')) {
        die();
    }

    define('WP_TO_KLICK_TIPP_VERSION', '1.1');
    define('WP_TO_KLICK_TIPP_DIR', plugin_dir_path(__FILE__));
    define('WP_TO_KLICK_TIPP_URL', plugin_dir_url(__FILE__));

    // Include 'WordPress To Klick Tipp' Application
    require_once(plugin_dir_path( __FILE__ ) . 'inc/class.wp-to-klick-tipp.php');
    $wptktApp = new WpToKlickTipp();

    // Register activation / deactivation hooks
    register_activation_hook(__FILE__, array('WpToKlickTipp', 'activate'));
    register_deactivation_hook(__FILE__, array('WpToKlickTipp', 'deactivate'));
?>