<?php
    /**
     * Class WpToKlickTipp
     *
     * @version 1.0.0
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
            require_once(WP_TO_KLICK_TIPP_DIR . 'vendor/klicktipp.api.php');
            require_once(WP_TO_KLICK_TIPP_DIR . 'inc/cron/cron.php');
            require_once(WP_TO_KLICK_TIPP_DIR . 'inc/class.wp-to-klick-tipp-admin.php');
            require_once(WP_TO_KLICK_TIPP_DIR . 'inc/class.wp-to-klick-tipp-cron-manager.php');
        }
        
        /**
         *
         */
        private function addAction() {
            add_action('plugins_loaded', array('WpToKlickTipp', 'setLocalization'));
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
         * Activate the plugin
         *
         * @access public
         * @return void
         */
        public function activate() {
            // moved this into the constructor because the cron always disappeard
            //$cronManager = new WpToKlickTippCronManager();
            //$cronManager->setScheduleHook();
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