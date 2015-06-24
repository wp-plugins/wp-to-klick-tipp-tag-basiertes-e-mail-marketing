<?php
    /**
     * Class WpToKlickTippAdmin
     *
     * @version 2.0.0 Free
     * @author Tobias B. Conrad <tobiasconrad@leupus.de>
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
        public $message;

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
                // Set Klick-Tipp license
                if ($_GET['action'] == 'save-license') {
					if(isset($_POST['license-delete'])) {
						// delete license key
						delete_option('wptkt_license_email');
						delete_option('wptkt_license_key');
						$this->message = __('License key deleted successfully', 'wptkt');
						$this->writeLog('Licensing',__('WPtoKT license key deleted', 'wptkt'));
					} else {
						// double check and save license key
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
								$this->message = __('Saved successfully', 'wptkt');
								$this->writeLog('Licensing',__('Credentials (email and/or key) changed', 'wptkt'));
							} else {
								$this->error = __('Wrong API Credentials', 'wptkt');
								$this->writeLog('Licensing',__('Wrong API Credentials', 'wptkt').' ('.$licenseEmail.', '.$licenseKey.', '.site_url().')');
							}
						}
					} // end if isset($_POST['license-delete'])
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
                    $klicktippAPIkey = trim($_POST['account-apikey']);
                    if ($klicktippUsername == '') {
                        $this->error = __('Please enter your Klick-Tipp username.', 'wptkt');
                    } else if ($klicktippPassword == '') {
                        $this->error = __('Please enter your Klick-Tipp password.', 'wptkt');
                    } else if ($klicktippAPIkey == '' || strlen($klicktippAPIkey) <> 14 ) {
                        $this->error = __('Please check your Klick-Tipp API key.', 'wptkt');
                    } else {
                        $correctCreds = $this->connectToKlickTipp($klicktippUsername, $klicktippPassword);
                        if ($correctCreds) {
                            update_option('wptkt_klicktipp_username', $klicktippUsername);
                            update_option('wptkt_klicktipp_password', $klicktippPassword);
                            update_option('wptkt_klicktipp_apikey', $klicktippAPIkey);
                            $this->message = __('Klick-Tipp Account Saved Successfully', 'wptkt');
					        $this->writeLog('Klick-Tipp',__('Klick-Tipp Account Saved Successfully', 'wptkt'));
                        } else {
                            $this->error = __('Wrong Klick-Tipp Credentials', 'wptkt');
					        $this->writeLog('Klick-Tipp',__('Wrong Klick-Tipp Credentials', 'wptkt'));
                        }
                    }
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

                // Delete all entries from Log table
                if ($_GET['action'] == 'flush-log-db') {
                    $this->clearLog();
                }
            }
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
			if(!$username) $username = get_option('wptkt_klicktipp_username');
			if(!$password) $password = get_option('wptkt_klicktipp_password');
			
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
                'export' => (object) array(
                    'class' => 'nav-tab',
                    'href' => 'admin.php?page=wptkt&mod=export',
                    'name' => __('CSV Export', 'wptkt')
				),
                'role-setting' => (object) array(
                    'class' => 'nav-tab',
                    'href' => 'admin.php?page=wptkt&mod=role-setting',
                    'name' => __('Role Settings', 'wptkt')
                ),
                'log-view' => (object) array(
                    'class' => 'nav-tab',
                    'href' => 'admin.php?page=wptkt&mod=log-view',
                    'name' => __('Log View', 'wptkt')
                ),
                'requirement' => (object) array(
                    'class' => 'nav-tab',
                    'href' => 'admin.php?page=wptkt&mod=requirement',
                    'name' => __('Requirement', 'wptkt')
                )
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
                } else if ($mod == 'export') {
                    $nav['export']->class = 'nav-tab nav-tab-active';
                } else if ($mod == 'log-view') {
                    $nav['log-view']->class = 'nav-tab nav-tab-active';
                } else if ($mod == 'role-setting') {
                    $nav['role-setting']->class = 'nav-tab nav-tab-active';
                } else if ($mod == 'requirement') {
                    $nav['requirement']->class = 'nav-tab nav-tab-active';
                }
            } else {
                $nav['license']->class = 'nav-tab nav-tab-active';
            }

            return $nav;
        }

        /**
         * Get the WPtoKT License Version
         *
         * @access public
         * @return string
         */
        public function getVersion() {
            return __('Free Version, <a target="_blank" href="http://woocommerce-klick-tipp.com">Upgrade to Pro to get full data sync</a>', 'wptkt');
        }

        /**
         * Get user roles from wordpress
         *
         * @access public
         * @return array
         */
        public function getUserRoles() {
            return array(
                'subscriber' => array('name' => 'Subscriber'),
                'customer' => array('name' => 'Customer')
            );
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

        /**
         * Write Log Entry
         *
         * @access public
         * @param string $sModule
         * @param string $sText
         * @return boolean
         */
        public function writeLog($sModule,$sText) {
		    global $wpdb;
		
		    $wpdb->insert( 
		        WP_TO_KLICK_TIPP_TABLE_LOG,
    		    array( 
			        'time' => current_time( 'mysql' ), 
			        'module' => $sModule, 
			        'text' => $sText
        		)
     	    );

            return true;
        }

        /**
         * Return Log Entry
         *
         * @access public
         * @param int $iLines
         * @return array
         */
        public function getLog($iLines=100) {
            global $wpdb;
			
			return $wpdb->get_results('SELECT * FROM '.WP_TO_KLICK_TIPP_TABLE_LOG.' ORDER BY `id` DESC LIMIT '.$iLines.';', ARRAY_A);
        }

        /**
         * Clear Log Table
         *
         * @access private
         * @return boolean
         */
        private function clearLog() {
            global $wpdb;
			$wpdb->get_results('TRUNCATE '.WP_TO_KLICK_TIPP_TABLE_LOG);
			$this->writeLog('Logging',__('All log data deleted.', 'wptkt'));
			return true;
        }
    }


?>