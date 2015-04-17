<?php
    /**
     * Class WpToKlickTippAdmin
     *
     * @version 1.0.1
     * @author Tobias B. Conrad <tobiasconrad@leupus.de>, Timo K�nig <dev@timokoenig.de>
     */
    class WpToKlickTippAdmin {

        /**
         * @var object $instance
         */
        private static $instance = null;

        /**
         * @var string $error
         */
        private $error;

        /**
         * @var string $message
         */
        private $message;

        /**
         * @var string $licenseUrl The URL for the license check
         */
        private $licenseUrl = 'http://www.woocommerce-klick-tipp.com/klicktip-capi/check_license_wp.php';


        /**
         * Get an instance of this class
         *
         * @static
         * @access public
         * @return object
         */
        public static function getInstance() {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Add admin menu action
         *
         * @access public
         * @return void
         */
        public function addMenu() {
    	    add_action('admin_menu', array($this, 'addMenuConfig'));
        }

        /**
         * Add admin menu page
         *
         * @access public
         * @return void
         */
        public function addMenuConfig() {
            add_menu_page(__("WP To Klick-Tipp", 'wptkt'), __("WP To Klick-Tipp", 'wptkt'), "administrator", "wptkt", array($this, 'run'));
        }

        /**
         * Add admin files
         *
         * @access public
         * @return void
         */
        public function addFiles() {
            // css files
            wp_register_style('wptkt-general-css', WP_TO_KLICK_TIPP_URL . 'assets/css/custom-style.css');
            wp_enqueue_style('wptkt-general-css');

            // js files
            wp_enqueue_script('jquery');
            wp_enqueue_script('wptkt-general', WP_TO_KLICK_TIPP_URL . 'assets/js/general.js', array(), '1.0.0', true);
        }

        /**
         * Run the admin backend
         *
         * @access public
         * @return void
         */
        public function run() {
            $wptktAdmin = self::getInstance();

            // add css/js files
            $wptktAdmin->addFiles();

            $wptktAdmin->handleForms();

            include(WP_TO_KLICK_TIPP_DIR . 'view/admin.phtml');
        }

        /**
         * Get Errors
         *
         * @access public
         * @return string
         */
        public function getError() {
            return $this->error;
        }

        /*
         * Get Messages
         *
         * @access public
         * @return string
         */
        public function getMessage() {
            return $this->message;
        }

        /**
         * Handle the submitted forms
         *
         * @access private
         * @return void
         */
        private function handleForms() {
            if (isset($_GET['action'])) {
                // Reset cron time
                if ($_GET['action'] == 'reset-sync') {
                    update_option('wptkt_last_export', '');
                    $this->message = __('Time is reset, next cron will update all orders to Klick-Tipp', 'wptkt');
                }

                 // run cron manually
                if ($_GET['action'] == 'trigger-cron') {
                    include('cron/cron_wordpress.php');
		    // cron-woocommerce if plugin is activated
		    if (function_exists('woocommerce_get_page_id')) {
			include('cron/cron_woocommerce.php');
		    }
                    $this->message = __('You just run the cron manually.', 'wptkt');
                }

                // Set Klick-Tipp license
                if ($_GET['action'] == 'save-license') {
                    $licenseEmail = trim($_POST['license-email']);
                    $licenseKey = trim($_POST['license-key']);

                    if ($licenseEmail == '') {
                        $this->error = __('Please enter your email address.', 'wptkt');
                    } else if ($licenseKey == '') {
                        $this->error = __('Please enter your license key.', 'wptkt');
                    } else {
                        $datastring = 'license_email=' . $licenseEmail . '&license_key=' . $licenseKey . '&site_url=' . site_url();

                        if ($this->checkLicense($datastring)) {
                            update_option('wptkt_license_email', $licenseEmail);
                            update_option('wptkt_license_key', $licenseKey);
                            $this->message = __('Saved Successfully', 'wptkt');
                        } else {
                            $this->error = __('Wrong API Credentials', 'wptkt');
                        }
                    }
                }

                // Set Klick-Tipp account data
                if ($_GET['action'] == 'save-account') {

                    // save klick-tipp api
                    if (isset($_POST['account-api'])) {
                        $apiValue = trim($_POST['account-api']);
                        $apiValues = $this->getApiSelect();
                        if (array_key_exists($apiValue, $apiValues)) {
                            update_option('wptkt_klicktipp_api', $apiValue);
                        }
                    }

                    $klicktippUsername = trim($_POST['account-username']);
                    $klicktippPassword = trim($_POST['account-password']);
                    if ($klicktippUsername == '') {
                        $this->error = __('Please enter your Klick-Tipp username.', 'wptkt');
                    } else if ($klicktippPassword == '') {
                        $this->error = __('Please enter your Klick-Tipp password.', 'wptkt');
                    } else {
                        $correctCreds = $this->connectToKlickTipp($klicktippUsername, $klicktippPassword);
                        if ($correctCreds) {
                            update_option('wptkt_klicktipp_username', $klicktippUsername);
                            update_option('wptkt_klicktipp_password', $klicktippPassword);
                            $this->message = __('Klick-Tipp Account Saved Successfully', 'wptkt');
                        } else {
                            $this->error = __('Wrong Klick-Tipp Credentials', 'wptkt');
                        }
                    }
                }

                // Save Cron Settings
                if ($_GET['action'] == 'save-cron-setting') {

                    // activate wordpress cron
                    if (isset($_POST['wptkt_wordpress_cron']) && $_POST['wptkt_wordpress_cron'] == 1) {
                        update_option('wptkt_wordpress_cron', 1);
                    } else {
                        update_option('wptkt_wordpress_cron', 0);
                    }

                    $this->message = __('Cron Settings Saved Successfully', 'wptkt');
                }

                // Set Double-Opt-In-Process-Id
                if ($_GET['action'] == 'save-role-setting') {

                    $roles = $this->getUserRoles();

                    foreach ($roles AS $slug => $role) {
                        $roleEnabled = trim($_POST[$slug . '_enabled']);
                        if (isset($roleEnabled) && $roleEnabled == 1) {
                            update_option('wptkt_role_' . $slug, 1);
                        } else {
                            update_option('wptkt_role_' . $slug, 0);
                        }
                        $roleProcessId = trim($_POST[$slug . '_id']);
                        if (isset($roleProcessId)) {
                            update_option('wptkt_role_' . $slug . '_id', $roleProcessId);
                        } else {
                            update_option('wptkt_role_' . $slug . '_id', 0);
                        }
                    }
                }
            }
        }

        /**
         * Check if the user has as active premium license
         *
         * @access private
         * @param string $data The post data which should be send to the server
         * @return boolean
         */
        private function checkLicense($data) {
            $ch = curl_init($this->licenseUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_error($ch);

            $jsonObj = json_decode($response);
            if (is_object($jsonObj)) {
                if ($jsonObj->check == 1) {
                    return true;
                }
            }
            return false;
        }

        /**
         * Check if a connection to klick-tipp is established
         *
         * @access public
         * @return boolean
         */
        public function isApiConnected() {
            $klicktip_username = get_option('wptkt_klicktipp_username');
            $klicktip_password = get_option('wptkt_klicktipp_password');

            return $this->connectToKlickTipp($klicktip_username, $klicktip_password);
        }

        /**
         * Connect to the Klick-Tipp API
         *
         * @access private
         * @param string $username
         * @param string $password
         * @return boolean
         */
        private function connectToKlickTipp($username, $password) {
            $apiValue = get_option('wptkt_klicktipp_api');
            if ($apiValue == 'br') {
                $apiUrl = 'http://api.klickmail.com.br';
            } else {
                $apiUrl = 'http://api.klick-tipp.com';
            }

            $connector = new KlicktippConnector($apiUrl);
            return $connector->login($username, $password);
        }

        /**
         * Get plugin navigation
         *
         * @access public
         * @return array
         */
        public function getNavigation() {
            $nav = array(
                'license' => (object) array(
                    'class' => 'nav-tab',
                    'href' => 'admin.php?page=wptkt&mod=license',
                    'name' => __('License', 'wptkt')
                ),
                'account' => (object) array(
                    'class' => 'nav-tab',
                    'href' => 'admin.php?page=wptkt&mod=account',
                    'name' => __('Klick-Tipp Account', 'wptkt')
                ),
                'cron-setting' => (object) array(
                    'class' => 'nav-tab',
                    'href' => 'admin.php?page=wptkt&mod=cron-setting',
                    'name' => __('Cron Settings', 'wptkt')
                ),
                'role-setting' => (object) array(
                    'class' => 'nav-tab',
                    'href' => 'admin.php?page=wptkt&mod=role-setting',
                    'name' => __('Role Settings', 'wptkt')
                ),
            );

            // set active class
            $mod = $_GET['mod'];
            if (isset($mod)) {
                if ($mod == 'license') {
                    $nav['license']->class = 'nav-tab nav-tab-active';
                } else if ($mod == 'account') {
                    $nav['account']->class = 'nav-tab nav-tab-active';
                } else if ($mod == 'cron-setting') {
                    $nav['cron-setting']->class = 'nav-tab nav-tab-active';
                } else if ($mod == 'role-setting') {
                    $nav['role-setting']->class = 'nav-tab nav-tab-active';
                }
            } else {
                $nav['license']->class = 'nav-tab nav-tab-active';
            }

            return $nav;
        }

        /**
         * Get the time of the last sync to Klick-Tipp
         *
         * @access public
         * @return string
         */
        public function getLastSync() {
            $last_updated_date = trim(get_option('wptkt_last_export'));
            if ($last_updated_date == '') {
                return __("Not transferred any data to Klick-Tipp.", 'wptkt');
            } else {
                /* get gmt offset */
                $gmt_offset	=	get_option('gmt_offset');
                if ($gmt_offset!='') {
                    $gmt_offset = get_option('gmt_offset');
                    $explode_time = explode('.', $gmt_offset);
                    $matched = strpos($explode_time[0], "-");

                    if (trim($matched) === '') {
                        $min_sign = '+';
                    } else {
                        $min_sign = '-';
                    }

                    if (!empty($explode_time[1])) {
                        if ($explode_time[1] == '5') {
                            $min = '30';
                        } elseif ($explode_time[1] == '75') {
                            $min = '45';
                        } else {
                            $min = '0';
                        }
                    } else {
                        $min = '0';
                    }

                    return date("Y-m-d H:i:s",strtotime($explode_time[0]." hours ".$min_sign.$min." min",$last_updated_date));

                } else {
                    return date("Y-m-d H:i:s",$last_updated_date);
                }
            }
        }

        /**
         * Get the Klick-Tipp License Version
         *
         * @access public
         * @return string
         */
        public function getVersion() {
            $version = __('Free Version, <a target="_blank" href="http://woocommerce-klick-tipp.com">Upgrade to Pro to get full data sync</a>', 'wptkt');

            if ($this->isPremium()) {
                $version = __('Premium Version', 'wptkt');
            }

            return $version;
        }

        /**
         * Check if the user has a premium license
         *
         * @access public
         * @return boolean
         */
        public function isPremium() {
            $licenseEmail = get_option('wptkt_license_email');
            $licenseKey = get_option('wptkt_license_key');
            $datastring = 'license_email=' . $licenseEmail . '&license_key=' . $licenseKey . '&site_url=' . site_url();
            if ($this->checkLicense($datastring)) {
                return true;
            }
            return false;
        }

        /**
         * Get user roles from wordpress
         *
         * @access public
         * @return array
         */
        public function getUserRoles() {
            if ($this->isPremium()) {
                $roles = get_editable_roles();
            } else {
                $roles = array(
                    'subscriber' => array('name' => 'Subscriber'),
                    'customer' => array('name' => 'Customer')
                );
            }
            return $roles;
        }

        /**
         * Get api select array
         *
         * @access public
         * @return array
         */
        public function getApiSelect() {
            $apiValues = array(
                'de' => (object) array(
                    'name' => __('Germany', 'wptkt')
                ),
                'br' => (object) array(
                    'name' => __('Brasil', 'wptkt')
                )
            );

            $apiValue = get_option('wptkt_klicktipp_api','');
            if (array_key_exists($apiValue, $apiValues)) {
                $apiValues[$apiValue]->selected = 'selected="selected"';
            }

            return $apiValues;
        }
    }
?>