<?php
    /**
     * Class WpToKlickTippApi
     *
     * @version 2.0.0 Free
     */
    class WpToKlickTippApi {
		/*
		 * Add/modify user to contact cloud at Klick-Tipp
         *
         * @access private
         * @param string $username
         * @param string $password
         * @return boolean
		 */
		public function User2KT($iOrderID) {
            $sKTUsername = get_option('wptkt_klicktipp_username');
            $sKTPassword = get_option('wptkt_klicktipp_password');
            $sKTApiKey = get_option('wptkt_klicktipp_apikey');
			$sUserEmail = get_post_meta($iOrderID,'_billing_email',true);
			
			$aFields = $this->getKTfields($iOrderID);

            $oConnector = new KlicktippConnector();
			$sRedirectUrl = $oConnector->signin($sKTUsername, $sUserEmail, $aFields);
			
			if (!$sRedirectUrl) {
				print $connector->get_last_error();
			}
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

    }

?>