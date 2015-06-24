<?php
    /**
     * Execute plugin cron jobs
     *
     * Debug cron with the following url
     * http://[URL]/wp-cron.php?doing_cron
     * 
	 * @version 2.0.0 Free
     * @return void
     */
    function wpToKlickTippCron() {
        if (get_option('wptkt_wordpress_cron') == '1') include('cron_wordpress.php');
    }
?>