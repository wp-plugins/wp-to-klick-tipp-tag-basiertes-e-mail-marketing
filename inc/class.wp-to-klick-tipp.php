<?php
    /**
     * Class WpToKlickTipp
     *
     * @version 2.0.0 Free
     * @author Tobias B. Conrad <tobiasconrad@leupus.de>, Timo König <dev@timokoenig.de>
     */
    class WpToKlickTipp {

        /**
         * Constructor
         */
        public function __construct() {
            $this->loadDependencies();
            $this->addAction();

            if (is_admin()) {
                $this->addAdminActions();
            }

            // set cron
            $cronManager = new WpToKlickTippCronManager();
            $cronManager->setScheduleHook();
        }

        /**
         * Load dependencies for this class
         *
         * @access private
         * @return void
         */
        private function loadDependencies() {
            require_once(WP_TO_KLICK_TIPP_DIR . 'vendor/klicktipp.api.inc.php');
            require_once(WP_TO_KLICK_TIPP_DIR . 'inc/cron/cron.php');
            require_once(WP_TO_KLICK_TIPP_DIR . 'inc/class.wp-to-klick-tipp-admin.php');
            require_once(WP_TO_KLICK_TIPP_DIR . 'inc/class.wp-to-klick-tipp-cron-manager.php');
            require_once(WP_TO_KLICK_TIPP_DIR . 'inc/class.ioncube-tester.php');
        }

        /**
         *
         */
        private function addAction() {
	    add_action('plugins_loaded', array($this, 'setLocalization'));
        }

        /**
         * Set the localization text domain for the plugin
         *
         * @access public
         * @return void
         */
        public function setLocalization() {
            load_plugin_textdomain('wptkt', false, dirname(plugin_basename(__FILE__)) . '/../languages/');
        }

        /**
         * Add some other admin stuff
         *
         * @access private
         * @return void
         */
        private function addAdminActions() {
            $admin = new WpToKlickTippAdmin();
            $admin->addMenu();
        }

        /**
         * Deactivate the plugin
         *
         * @access public
         * @return void
         */
        public function deactivate() {
            $cronManager = new WpToKlickTippCronManager();
            $cronManager->clearScheduleHook();
        }
    }
?>