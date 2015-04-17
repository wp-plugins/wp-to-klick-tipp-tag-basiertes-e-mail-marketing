<?php 

set_time_limit(0);

global $wpdb;
global $woocommerce;

$site_url	=	site_url();

function requestServer($datastring) {

	$ch		=	curl_init('http://woocommerce-klick-tipp.com/klicktip-capi/check_license.php');
	curl_setopt($ch,CURLOPT_POST,true);				
	curl_setopt($ch,CURLOPT_POSTFIELDS,$datastring);	
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);		
	$output	=	curl_exec($ch);		
	curl_error($ch);	
	return $output;	

}




$license_email	=	get_option('wptkt_license_email');		
$license_key	=	get_option('wptkt_license_key');	


$datastring = 'license_email='.$license_email.'&license_key='.$license_key.'&site_url='.$site_url;
$yes		= requestServer($datastring);

	function filter_where_wpa89154($where = '') {
		/*posts in the last 30 days*/
		$klicktippbridge_last_export	=	trim(get_option('wptkt_last_export',true));
		if($klicktippbridge_last_export == '' || $klicktippbridge_last_export == '1')
			$where = '';
		else
			$where .= " AND post_modified_gmt > '" . date('Y-m-d H:i:s', $klicktippbridge_last_export) . "'";
		return $where;

	}

	add_filter('posts_where', 'filter_where_wpa89154');

	/* get all the orders of woocomerce */

	$order_args	=	array(

						'posts_per_page'   => -1,
						'offset'           => 0,
						'category'         => '',
						'orderby'          => 'post_date',
						'order'            => 'DESC',
						'include'          => '',
						'exclude'          => '',
						'meta_key'         => '',
						'meta_value'       => '',
						'post_type'        => 'shop_order',
						'post_mime_type'   => '',
						'post_parent'      => '',
						'post_status'      => 'publish',
						'suppress_filters' => false ); 

					

	$order_posts	=	get_posts($order_args);

	remove_filter('posts_where', 'filter_where_wpa89154');

	


	

	require_once(dirname(__FILE__) . '/../../vendor/klicktipp.api.php');

	

	$klicktip_username = get_option('wptkt_klicktipp_username');

	$klicktip_password = get_option('wptkt_klicktipp_password');

	
	$apiValue = get_option('wptkt_klicktipp_api');
	if ($apiValue == 'br') {
		$apiUrl = 'http://api.klickmail.com.br';
	} else {
		$apiUrl = 'http://api.klick-tipp.com';
	}
	
	$connector = new KlicktippConnector($apiUrl);

	$connector->login($klicktip_username, $klicktip_password);
	
	if(!empty($order_posts)){

		foreach($order_posts as $order){
		
			/*$user_info	=	get_user_by('id',$order->ID);
			
			echo $email_address		=	$user_info->data->user_email;*/
			
			
			
			$email_address	=	get_post_meta($order->ID,'_billing_email',true);
			
			
			// get user by the email address
			// added January 13, 2015
			$emailUser = get_user_by('email', $email_address);
			$emailUserRoles = $emailUser->roles;
			if (count($emailUserRoles) > 0) {
				$emailUserRole = strtolower($emailUserRoles[0]);
				$double_optin_process_id = get_option('wptkt_role_' . $emailUserRole . '_id');
				if ($double_optin_process_id === false) {
					$double_optin_process_id = 0;
				}
			} else {
				$double_optin_process_id = 0;
			}
			 
			
			$fields = array();
			$args   = array();	
			$woo_order = new WC_Order($order->ID);
				
			$orderdata = (array) $woo_order;
			$order_status = $orderdata['post_status'];

			 /*$terms =  wp_get_post_terms( $order->ID, 'shop_order_status', $args );
			 $order_status = $terms[0]->name;*/

			 $order_tag_idA	=	array('419940','419946','419948','419950','419941','419949','419947',); 
			if($order_status=='wc-pending')
				$order_tag_id	= '419940';
			else if($order_status=='wc-on-hold')
				$order_tag_id	= '419946';
			else if($order_status=='wc-completed')	
				$order_tag_id	= '419948';
			else if($order_status=='wc-cancelled')	
				$order_tag_id	= '419950';
			else if($order_status=='wc-failed')	
				$order_tag_id	= '419941';
			else if($order_status=='wc-refunded')	
				$order_tag_id	= '419949';
			else if($order_status=='wc-processing')	
				$order_tag_id	= '419947';
			
			 if($yes>0){
			 
			 
		
				$billing_first_name	=	get_post_meta($order->ID,'_billing_first_name',true);
				$billing_last_name	=	get_post_meta($order->ID,'_billing_last_name',true);
				$billing_company	=	get_post_meta($order->ID,'_billing_company',true);
				$billing_address_1	=	get_post_meta($order->ID,'_billing_address_1',true);
				$billing_address_2	=	get_post_meta($order->ID,'_billing_address_2',true);
				$billing_city	=	get_post_meta($order->ID,'_billing_city',true);
				$billing_state	=	get_post_meta($order->ID,'_billing_state',true);
				$billing_postcode	=	get_post_meta($order->ID,'_billing_postcode',true);
				$billing_country	=	get_post_meta($order->ID,'_billing_country',true);
				$billing_phone	=	get_post_meta($order->ID,'_billing_phone',true);
				$order_total	=	get_post_meta($order->ID,'_order_total',true);
				$order_shipping	=	get_post_meta($order->ID,'_order_shipping',true);
				$cart_discount	=	get_post_meta($order->ID,'_cart_discount',true);
				$order_discount	=	get_post_meta($order->ID,'_order_discount',true);
				$order_tax		=	get_post_meta($order->ID,'_order_tax',true);
				$order_shipping_tax		=	get_post_meta($order->ID,'_order_shipping_tax',true);
				$total_discount	=	$order_discount+$cart_discount;
				$order_date		=	$order->post_date;
				
				$items = '';
				$itemsA		=	array();
				$itemsCat	=	array();
				/* get all item names */
				$item_results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_order_items WHERE order_id = $order->ID and order_item_type!='shipping'", OBJECT );
				
				if($item_results){
					foreach($item_results as $item_row){
						$items.=$item_row->order_item_name.",";
						/*$itemsA[]=strtolower($item_row->order_item_name);*/
						$product_id = $wpdb->get_row( "SELECT meta_value FROM ".$wpdb->prefix."woocommerce_order_itemmeta WHERE order_item_id = $item_row->order_item_id and meta_key='_product_id'", OBJECT );
						$post_data = get_post($product_id->meta_value, ARRAY_A);
						$slug = $post_data['post_name']; 
						$itemsA[]=$slug;
						$categoryArr	=	get_the_terms( $product_id->meta_value, 'product_cat' );
						if(!empty($categoryArr)){
							foreach($categoryArr as $category_arr){
								$itemsCat[]		=	$category_arr->name;
							}
						}
					}
					
					$itemsCat	=	array_unique($itemsCat);
				}
				$items 	=	rtrim($items,",");
				$fields = array (
				   'fieldFirstName' => $billing_first_name,
				   'fieldLastName' => $billing_last_name,
				   'fieldCompanyName' => $billing_company,
				   'fieldStreet1' => $billing_address_1,
				   'fieldStreet2' => $billing_address_2,
				   'fieldCity' => $billing_city,
				   'fieldState' => $billing_state,
				   'fieldZip' => $billing_postcode,
				   'fieldCountry' => $billing_country,
				   'fieldMobilePhone' => $billing_phone,
				   'field15494' => $order->ID, /* Order Number */
				   'field15495' => $order_status, /* Order Status */
				   'field15496' => $order_total,     /* Total Amount */
				   'field15497' => $items,	/* Items */	
				   'field15498' => $order_date,   /* Order Date */
				   'field15550' => $order_shipping,   /* Total Shipping Fee */
				   'field15551' => $total_discount   /* Total Discount */
				   
				 );
				 
				 /* if he is already subscribed  update it instead of inserting*/
				 $subscriber_id = $connector->subscriber_search($email_address);
				

				if($subscriber_id) {
					/*first untag him from order status tag*/
					$subscriber = $connector->subscriber_update($subscriber_id,$fields);
					
					$prev_subscribe = $connector->subscriber_get($subscriber_id);
					$prev_tagsA		=	$prev_subscribe->tags;
					if(!empty($prev_tagsA)){
						foreach($prev_tagsA as $prev_tag_id){
							if(in_array($prev_tag_id,$order_tag_idA))
								$connector->untag($email_address,$prev_tag_id);
						}
					}
					
					//echo "order_tag_id=".$order_tag_id;
					$connector->tag($email_address, $order_tag_id);
					
				}else{
					$subscriber = $connector->subscribe($email_address,$double_optin_process_id, $order_tag_id, $fields);
				
				}
				
				/* tag all if order status is completed */
				if($order_status == 'wc-completed'){
					/* Rashmi */
					$array=array();
					$subscribed = $connector->subscriber_get($subscriber->ID);
					if(trim($subscribed->status)!='Subscribed')
					{
						$array=unserialize(get_option('klicktiip_unsubscribe_order'));
						$array[]=$order->ID;
						$array = array_unique($array);
						$NS_id=serialize($array);
						update_option('klicktiip_unsubscribe_order',$NS_id);
					}
					/***** create product tag item wise if already there don't create *****/	
					/* first checke exist or not*/
					$tag_existA = $connector->tag_index();
					foreach($itemsA as $item_name ){
						$item_name	=	$item_name;
						$product_tag_id = array_search($item_name,$tag_existA);
						if($product_tag_id){	
							$subscriber_item = $connector->tag($email_address, $product_tag_id);
						}
						else{
							/* create tag */
							$product_tag_id = $connector->tag_create($item_name,'');
							$connector->tag($email_address, $product_tag_id);							 
						}
					}
					
					
					/***** create Category tag item wise if already there don't create *****/	
					/* first checke exist or not*/
					foreach($itemsCat as $item_cat ){
						$item_cat	=	html_entity_decode($item_cat);
						$product_cat_tag_id = array_search($item_cat,$tag_existA);
						if($product_cat_tag_id){	
							$subscriber_item = $connector->tag($email_address, $product_cat_tag_id);
						}
						else{
							/* create tag */
							$product_cat_tag_id = $connector->tag_create($item_cat,'');
							$connector->tag($email_address, $product_cat_tag_id);							 
						}
					}
					
					/* tag for amounts without shipping but including tax */
					$amount_tag = ( ($order_total-$order_shipping)+ ($order_shipping_tax+$order_tax) );
					if($amount_tag<50){
						$amount_tag = 'under50';
					}
					else{
						$divinder = (int)($amount_tag/50);
						$lower_amount_tag = $divinder*50;
						$upper_amount_tag = $lower_amount_tag+50;
						$amount_tag = $lower_amount_tag."-".$upper_amount_tag;
					}
					$amount_tag_id = array_search($amount_tag,$tag_existA);
					if($amount_tag_id){
						$subscriber_amount = $connector->tag($email_address, $amount_tag_id);
					}
					else{
						/* create tag */
						$amount_tag_id = $connector->tag_create($amount_tag,'');
						$connector->tag($email_address, $amount_tag_id);							 
					}
					
					/* tag for last 6 motnhs */
					

					$last_6month_flag	=	1;
					
					$last_6_row = $wpdb->get_row("select b.`post_id`,a.`post_date` from ".$wpdb->prefix."postmeta b, ".$wpdb->prefix."posts a where b.meta_key='_billing_email' and b.meta_value='".$email_address."' and b.post_id=a.id order by `meta_id` desc limit 1,1");
					if(empty($last_6_row)){
						$last_6month_flag	=	0;
					}
					else{
						$last_order_date	=	$last_6_row->post_date;
					}

					if($last_6month_flag == 1){
						$order_date_str	=	strtotime($order_date);
						list($last_date,$last_time)	=	explode(" ",$last_order_date);
						list($y,$m,$d)	=	explode("-",$last_date);
						list($h,$i,$s)	=	explode(":",$last_time);
						$order_6month_time = mktime($h,$i,$s,$m+6,$d,$y);
						
						if( ($order_date_str<=$order_6month_time) && ($order_date_str != strtotime($last_order_date)) ){
							/* it means current order is coming in between 6 months from last order */
							/* check if exist within 6month tag then dont add else add */
							$within_6month_tag_id = array_search('order_within_6month',$tag_existA);
							if($within_6month_tag_id){	
								$subscriber_item = $connector->tag($email_address, $within_6month_tag_id);
							}
							else{
								/* create tag */
								$within_6month_tag_id = $connector->tag_create('order_within_6month','');
								$connector->tag($email_address, $within_6month_tag_id);							 
							}
						}
						else{
							/* delete the tag */
							$within_6month_tag_id = array_search('order_within_6month',$tag_existA);
							if($within_6month_tag_id)									
							 $connector->untag($email_address,$within_6month_tag_id);	
						}
					}
					/** total orders amount to be updated **/
					
				}
			}
			
			else{
				$tag_id = 0;
				/* free push only if order status is complete*/
				if($order_status=='wc-completed'){
					$subscriber = $connector->subscribe($email_address,$double_optin_process_id, $tag_id, $fields);
				}	
			}


		}

		

		/* after inserting records update option of last updated time */
		$current_time	=	time();
		update_option('wptkt_last_export',$current_time);
	}
	
	
	
	$orderIDS=array();
	$subscribeId=array();
	$orderID=get_option('klicktiip_unsubscribe_order');
	$orderIDS=unserialize($orderID);
	if(!empty($orderIDS))
	{
		foreach($orderIDS as $order_id)
		{
			/*$terms =  wp_get_post_terms( $order_id, 'shop_order_status', $args );
			$order_status = $terms[0]->name;*/
			$woo_order = new WC_Order($order_id);
				
			$orderdata = (array) $woo_order;
			$order_status = $orderdata['post_status'];
			
			if($order_status=='wc-completed')
			{
				$email_address	=	get_post_meta($order_id,'_billing_email',true);

				$subscriber_id = $connector->subscriber_search($email_address);
				$subscribe_prev = $connector->subscriber_get($subscriber_id);
				if( trim($subscribe_prev->status)=='Subscribed')
				{
					
					// get user by the email address
					// added January 13, 2015
					$emailUser = get_user_by('email', $email_address);
					$emailUserRoles = $emailUser->roles;
					if (count($emailUserRoles) > 0) {
						$emailUserRole = strtolower($emailUserRoles[0]);
						$double_optin_process_id = get_option('wptkt_role_' . $emailUserRole . '_id');
						if ($double_optin_process_id === false) {
							$double_optin_process_id = 0;
						}
					} else {
						$double_optin_process_id = 0;
					}
					

					   /* Replace 123 with the id of the double optin process.*/
					 $fields = array();
					 $args   = array();	
					 
					 
					 
					 /*$terms =  wp_get_post_terms( $order_id, 'shop_order_status', $args );
					 $order_status = $terms[0]->name;*/
					 $order_tag_idA	=	array('419940','419946','419948','419950','419941','419949','419947',); 
					if($order_status=='wc-pending')
						$order_tag_id	= '419940';
					else if($order_status=='wc-on-hold')
						$order_tag_id	= '419946';
					else if($order_status=='wc-completed')	
						$order_tag_id	= '419948';
					else if($order_status=='wc-cancelled')	
						$order_tag_id	= '419950';
					else if($order_status=='wc-failed')	
						$order_tag_id	= '419941';
					else if($order_status=='wc-refunded')	
						$order_tag_id	= '419949';
					else if($order_status=='wc-processing')	
						$order_tag_id	= '419947';
		 
					 if($yes>0){
				
						$billing_first_name	=	get_post_meta($order_id,'_billing_first_name',true);
						$billing_last_name	=	get_post_meta($order_id,'_billing_last_name',true);
						$billing_company	=	get_post_meta($order_id,'_billing_company',true);
						$billing_address_1	=	get_post_meta($order_id,'_billing_address_1',true);
						$billing_address_2	=	get_post_meta($order_id,'_billing_address_2',true);
						$billing_city	=	get_post_meta($order_id,'_billing_city',true);
						$billing_state	=	get_post_meta($order_id,'_billing_state',true);
						$billing_postcode	=	get_post_meta($order_id,'_billing_postcode',true);
						$billing_country	=	get_post_meta($order_id,'_billing_country',true);
						$billing_phone	=	get_post_meta($order_id,'_billing_phone',true);
						$order_total	=	get_post_meta($order_id,'_order_total',true);
						$order_shipping	=	get_post_meta($order_id,'_order_shipping',true);
						$cart_discount	=	get_post_meta($order_id,'_cart_discount',true);
						$order_discount	=	get_post_meta($order_id,'_order_discount',true);
						$order_tax		=	get_post_meta($order_id,'_order_tax',true);
						$order_shipping_tax		=	get_post_meta($order_id,'_order_shipping_tax',true);
						$total_discount	=	$order_discount+$cart_discount;
						$order_date		=	$order->post_date;
						
						$items = '';
						$itemsA		=	array();
						$itemsCat	=	array();
						/* get all item names */
						$item_results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_order_items WHERE order_id = $order_id and order_item_type!='shipping'", OBJECT );
						if($item_results){
							foreach($item_results as $item_row){
								$items.=$item_row->order_item_name.",";
								$product_id = $wpdb->get_row( "SELECT meta_value FROM ".$wpdb->prefix."woocommerce_order_itemmeta WHERE order_item_id = $item_row->order_item_id and meta_key='_product_id'", OBJECT );
								$post_data = get_post($product_id->meta_value, ARRAY_A);
								$slug = $post_data['post_name']; 
								$itemsA[]=$slug;
								$categoryArr	=	get_the_terms( $product_id->meta_value, 'product_cat' );
								if(!empty($categoryArr)){
									foreach($categoryArr as $category_arr){
										$itemsCat[]		=	$category_arr->name;
									}
								}
							}
							$itemsCat	=	array_unique($itemsCat);
						}
						$items 	=	rtrim($items,",");
						$fields = array (
						   'fieldFirstName' => $billing_first_name,
						   'fieldLastName' => $billing_last_name,
						   'fieldCompanyName' => $billing_company,
						   'fieldStreet1' => $billing_address_1,
						   'fieldStreet2' => $billing_address_2,
						   'fieldCity' => $billing_city,
						   'fieldState' => $billing_state,
						   'fieldZip' => $billing_postcode,
						   'fieldCountry' => $billing_country,
						   'fieldMobilePhone' => $billing_phone,
						   'field15494' => $order_id, /* Order Number */
						   'field15495' => $order_status, /* Order Status */
						   'field15496' => $order_total,     /* Total Amount */
						   'field15497' => $items,	/* Items */	
						   'field15498' => $order_date,   /* Order Date */
						   'field15550' => $order_shipping,   /* Total Shipping Fee */
						   'field15551' => $total_discount   /* Total Discount */
						   
						 );
						 
						 
						if($subscriber_id) {
							/*first untag him from order status tag*/
							$subscriber = $connector->subscriber_update($subscriber_id,$fields);
							
							$prev_subscribe = $connector->subscriber_get($subscriber_id);
							$prev_tagsA		=	$prev_subscribe->tags;
							if(!empty($prev_tagsA)){
								foreach($prev_tagsA as $prev_tag_id){
									if(in_array($prev_tag_id,$order_tag_idA))
										$connector->untag($email_address,$prev_tag_id);
								}
							}
							//echo "order_tag_id=".$order_tag_id;
							$connector->tag($email_address, $order_tag_id);
							
						}
						/* tag all if order status is completed */
						if($order_status == 'wc-completed'){
							
							/***** create product tag item wise if already there don't create *****/	
							/* first checke exist or not*/
							$tag_existA = $connector->tag_index();
							
							foreach($itemsA as $item_name ){
								$item_name	=	html_entity_decode($item_name);
								$product_tag_id = array_search($item_name,$tag_existA);
								if($product_tag_id){	
									$subscriber_item = $connector->tag($email_address, $product_tag_id);
								}
								else{
									/* create tag */
									$product_tag_id = $connector->tag_create($item_name,'');
									$connector->tag($email_address, $product_tag_id);							 
								}
							}
							
							
							/***** create Category tag item wise if already there don't create *****/	
							/* first checke exist or not*/
							foreach($itemsCat as $item_cat ){
								$item_cat	=	html_entity_decode($item_cat);
								$product_cat_tag_id = array_search($item_cat,$tag_existA);
								if($product_cat_tag_id){	
									$subscriber_item = $connector->tag($email_address, $product_cat_tag_id);
								}
								else{
									/* create tag */
									$product_cat_tag_id = $connector->tag_create($item_cat,'');
									$connector->tag($email_address, $product_cat_tag_id);							 
								}
							}
							
							/* tag for amounts without shipping but including tax */
							$amount_tag = ( ($order_total-$order_shipping)+ ($order_shipping_tax+$order_tax) );
							if($amount_tag<50){
								$amount_tag = 'under50';
							}
							else{
								$divinder = (int)($amount_tag/50);
								$lower_amount_tag = $divinder*50;
								$upper_amount_tag = $lower_amount_tag+50;
								$amount_tag = $lower_amount_tag."-".$upper_amount_tag;
							}
							$amount_tag_id = array_search($amount_tag,$tag_existA);
							if($amount_tag_id){
								$subscriber_amount = $connector->tag($email_address, $amount_tag_id);
							}
							else{
								/* create tag */
								$amount_tag_id = $connector->tag_create($amount_tag,'');
								$connector->tag($email_address, $amount_tag_id);							 
							}
							
							
							/* tag for last 6 motnhs */
							$last_6month_flag	=	1;
					
							$last_6_row = $wpdb->get_row("select b.`post_id`,a.`post_date` from ".$wpdb->prefix."postmeta b, ".$wpdb->prefix."posts a where b.meta_key='_billing_email' and b.meta_value='".$email_address."' and b.post_id=a.id order by `meta_id` desc limit 1,1");
							if(empty($last_6_row)){
								$last_6month_flag	=	0;
							}
							else{
								$last_order_date	=	$last_6_row->post_date;
							}
							
							
							if($last_6month_flag == 1){
								$order_date_str	=	strtotime($order_date);
								list($last_date,$last_time)	=	explode(" ",$last_order_date);
								list($y,$m,$d)	=	explode("-",$last_date);
								list($h,$i,$s)	=	explode(":",$last_time);
								$order_6month_time = mktime($h,$i,$s,$m+6,$d,$y);
								if( ($order_date_str<=$order_6month_time) && ($order_date_str != strtotime($last_order_date) ) ){
									/* it means current order is coming in between 6 months from last order */
									/* check if exist within 6month tag then dont add else add */
									$within_6month_tag_id = array_search('order_within_6month',$tag_existA);
									if($within_6month_tag_id){	
										$subscriber_item = $connector->tag($email_address, $within_6month_tag_id);
									}
									else{
										/* create tag */
										$within_6month_tag_id = $connector->tag_create('order_within_6month','');
										$connector->tag($email_address, $within_6month_tag_id);							 
									}
								}
								else{
									/* delete the tag */
									$within_6month_tag_id = array_search('order_within_6month',$tag_existA);
									if($within_6month_tag_id)									
									 $connector->untag($email_address,$within_6month_tag_id);	
								}
							}
						}
					}
					
					/*remove order id from klicktiip_unsubscribe_order */
					
					$subscribeId[]=$order_id;
					
					
				}
			}
		}
		
		
		
		$filtered = array_diff($orderIDS, $subscribeId);
		$unsubID = array_unique($filtered);
		$unsubIDS=serialize($unsubID);
		update_option('klicktiip_unsubscribe_order',$unsubIDS);
		
	}
	
	
$connector->logout();
//echo "All Done";
//header( "refresh:3;url=http://klick-tipp.com" );


?>