<?php 
/**
 * Send ORder to Klick Tipp
 *
 * @version 2.0.0 Free
 */

set_time_limit(0);

global $wpdb;
global $woocommerce;

$order = get_post($ID);

if(!empty($order)){
    require_once(dirname(__FILE__) . '/../vendor/klicktipp.api.inc.php');

    $klicktip_username = get_option('wptkt_klicktipp_username');
    $klicktip_password = get_option('wptkt_klicktipp_password');
    $klicktip_apikey = get_option('wptkt_klicktipp_apikey');

    $apiValue = get_option('wptkt_klicktipp_api');
    $apiUrl = ($apiValue == 'br') ? ('http://api.klickmail.com.br') : ('http://api.klick-tipp.com');
	
    $connector = new KlicktippConnector($apiUrl);
    $connector->login($klicktip_username, $klicktip_password);
   
	$email_address = get_post_meta($order->ID,'_billing_email',true);
	$subscriber_id = $connector->subscriber_search($email_address);
	$emailUser = get_user_by('email', $email_address);

	$fields = array();
	$args   = array();	
	$woo_order = new WC_Order($order->ID);
				
	$orderdata = (array) $woo_order;
	$order_status = $orderdata['post_status'];

	$billing_first_name	=	get_post_meta($order->ID,'_billing_first_name',true);
	$billing_last_name	=	get_post_meta($order->ID,'_billing_last_name',true);

	$tag_existA = $aOrderTags = $connector->tag_index();
	$order_tag_id = array_search($order_status,$tag_existA);

	if (!$order_tag_id) $order_tag_id = $connector->tag_create($order_status, $text = 'WooCommerce Order Status created by WPtoKT');
	$tag_id = 0;
	$double_optin_process_id = 0;
	$fields = array (
	   'fieldFirstName' => $billing_first_name,
	   'fieldLastName' => $billing_last_name
	);

	if($order_status=='wc-on-hold' || $order_status=='wc-completed' || $order_status=='wc-processing' || $order_status=='wc-pending'){
		if($subscriber_id) {
			$subscriber = $connector->subscriber_update($subscriber_id,$fields);
			foreach($aOrderTags as $iTagID => $sTagName){
				if(substr($sTagName, 0, 3) != 'wc-') unset($aOrderTags[$iTagID]);
			}
			$oSubscriber = $connector->subscriber_get($subscriber_id);
			$aSubscriberTags = $oSubscriber->tags;
			if(!empty($aSubscriberTags)){
				foreach($aOrderTags as $iTagID => $iTagName){
					if(in_array($iTagID,$aSubscriberTags)) 
						$connector->untag($email_address,$iTagID);
				}
			}
			$connector->tag($email_address, $order_tag_id);
		}else{
			$subscriber = $connector->subscribe($email_address,$double_optin_process_id, $order_tag_id, $fields);
		}
	}	
    $connector->logout();
}
?>