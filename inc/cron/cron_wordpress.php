<?php
	/**
	 * This Cron Job sync all wordpress user with an active role.
	 * (except the woocommerce roles!)
	 *
	 * @version 1.0.1
	 * @author Tobias B. Conrad <tobiasconrad@leupus.de>, Timo K�nig <dev@timokoenig.de>
	 */

	set_time_limit(0);
	global $wpdb;

	require_once(dirname(__FILE__) . '/../../vendor/klicktipp.api.php');

	// Get License credentials
	$licenseEmail = get_option('wptkt_license_email');
    $licenseKey = get_option('wptkt_license_key');

	// License function
	function checkLicense($data) {
		$ch = curl_init('http://www.woocommerce-klick-tipp.com/klicktip-capi/check_license_wp.php');
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

	// Check License
	$datastring = 'license_email=' . $licenseEmail . '&license_key=' . $licenseKey . '&site_url=' . site_url();
	$license = checkLicense($datastring);

	// Get Klick-Tipp credentials
	$klicktipUsername = get_option('wptkt_klicktipp_username');
	$klicktipPassword = get_option('wptkt_klicktipp_password');

	// Connect with the Klick-Tipp API
	$apiValue = get_option('wptkt_klicktipp_api');
	if ($apiValue == 'br') {
		$apiUrl = 'http://api.klickmail.com.br';
	} else {
		$apiUrl = 'http://api.klick-tipp.com';
	}
	$connector = new KlicktippConnector($apiUrl);
	$isConnected = $connector->login($klicktipUsername, $klicktipPassword);

	/**
	 * Get the tag id of an specified name
	 *
	 * @param object $connector The API connector
	 * @param string $tagName The name of the tag
	 * @param array $tags The KlickTipps tags array
	 * @return int The ID of the tag
	 */
	function getTagId($connector, $tagName, $tags) {
	    $tagId = array_search($tagName, $tags);
	    if (is_null($tagId) || $tagId === false) {
		    // tag does not exist, create a new one
		    $tagId = $connector->tag_create($tagName, '');
	    }
	    return $tagId;
    }

	/**
	 * Check if the given customer has at least one completed order
	 */
	function hasCustomerCompletedOrder($customerId) {
		global $wpdb;
		$orders = $wpdb->get_row("
			SELECT
				COUNT(*) AS count
			FROM
				wp_posts
			WHERE
				post_status = 'wc-completed'
			AND
				ID IN (
					SELECT
						post_id
					FROM
						wp_postmeta
					WHERE
						meta_key = '_customer_user'
					AND
						meta_value = '" . $customerId . "'
				)
		");
		if (!is_null($orders) && ($orders->count > 0)) {
			return true;
		} else {
			return false;
		}
	}

	if ($isConnected) {
		// get all existing tags from Klick-Tipp
		$tags = $connector->tag_index();

		/* Start with the sync */
		/**************************************************************************/
		$users = get_users();

		global $wp_roles;
		if ($license) {
			$roles = $wp_roles->get_names();

			// check every group if it is active
			foreach ($roles AS $slug => $role) {
				$roleOption = get_option('wptkt_role_' . $slug);
				if ($roleOption != 1) {
					unset($roles[$slug]);
				}
			}
		} else {
			$roles = array(
				'subscriber' => 'Subscriber',
				'customer' => 'Customer'
			);
		}

		// loop over every user in the database
		foreach ($users AS $user) {

		    // check if the user has an active role for the sync
		    foreach ($roles AS $slug => $role) {
			if (in_array($slug, $user->roles)) {

			    // get user data for klick-tipp api
			    $emailAddress = $user->data->user_email;
			    $userData = get_userdata($user->data->ID);
			    $fields = array(
				    'fieldFirstName' => $userData->first_name,
				    'fieldLastName' => $userData->last_name
			    );

			    // check if user has been already synced once
			    $userSync = get_user_meta($user->data->ID, 'wptkt_sync', true);
			    if ($userSync != 1) {
				// user hasn't been synced

				// check if the user is already an klick-tipp subscriber
				$subscriberId = $connector->subscriber_search($emailAddress);
				if ($subscriberId) {
					// don't update fields for woocommerce subscriber
					if ($slug == 'customer' || $slug == 'shop_manager') {
						$fields = array();
					}

					// update existing subscriber
					$connector->subscriber_update($subscriberId, $fields);

					// update subscriber tag if not exist
					$subscriber = $connector->subscriber_get($subscriberId);
					$subscriberTags = $subscriber->tags;
					$tagId = getTagId($connector, $role, $tags);
					if (!in_array($tagId, $subscriberTags)) {
						$connector->tag($emailAddress, $tagId);
					}

					// update user meta for sync check
					update_user_meta($user->data->ID, 'wptkt_sync', 1);

				} else {

				    $doubleOptinProcessId = get_option('wptkt_role_' . $slug . '_id');
				    if (!isset($doubleOptinProcessId) || empty($doubleOptinProcessId)) {
					    $doubleOptinProcessId = 0;
				    }

				    // add user role as an tag for the subscriber and get the tagid
				    $tagId = getTagId($connector, $role, $tags);

				    if ($slug == 'customer') {
					    // special sync behavior for customers
					    if (hasCustomerCompletedOrder($user->data->ID)) {
						    // only sync customers if they have an completed order

						    // add new subscriber
						    $subscriber = $connector->subscribe($emailAddress, $doubleOptinProcessId, $tagId, $fields);

						    // add user meta for sync check
						    add_user_meta($user->data->ID, 'wptkt_sync', 1, true);
					    }
				    } else {
					    // add new subscriber
					    $subscriber = $connector->subscribe($emailAddress, $doubleOptinProcessId, $tagId, $fields);

					    // add user meta for sync check
					    add_user_meta($user->data->ID, 'wptkt_sync', 1, true);
				    }

				}
			    }
			}
		    }
		}

		// disconnect from the Klick-Tipp API
		$connector->logout();
	}
?>