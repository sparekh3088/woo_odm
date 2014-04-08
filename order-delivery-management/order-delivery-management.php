<?php
/*
Plugin Name: OMS
Description: Order by deliver date
Version: 2.0.0.2
Author: Jignesh kaila
Author URI: http://www.mbjtechnolabs.com
License: GPL2
Text Domain: delivery-date-min-max-qty-inventory-woocommerce
Contributor: Sohil Parekh

*/


// only if WooCommerce is active



if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

$plugins_url = plugins_url();
$plugins_url .= '/order-delivery-management';
function ConverDate($myDate = null) {
    $myDate = explode(" ", $myDate);
    $myCustomeDate = "";
    foreach ($myDate as $keyData => $valueData) {
        if ($keyData != 0) {
            if ($keyData != 3) {
                $myCustomeDate .= $valueData . "-";
            } else {
                $myCustomeDate .= $valueData;
            }
        }
    }
    $date = DateTime::createFromFormat('M-d-Y', $myCustomeDate);
    return $date->format('Y-m-d H:i:s');
}

function ConvertDatebyymd($myDate = null) {
    $myDate = explode(" ", $myDate);
    if(count($myDate) > 3) {
	    $myCustomeDate = "";
	    foreach ($myDate as $keyData => $valueData) {
	        if ($keyData != 0) {
	            if ($keyData != 3) {
	                $myCustomeDate .= $valueData . "-";
	            } else {
	                $myCustomeDate .= $valueData;
	            }
	        }
	    }
	    if(!empty($myCustomeDate)) {
	    	$date = DateTime::createFromFormat('M-d-Y', $myCustomeDate);
	    	return $date->format('Y-m-d');
	    } else {
	    	return null;
	    }
    } else {
    	$date = DateTime::createFromFormat('m/d/Y', $myDate[0]);
	    return $date->format('Y-m-d');
    }
}


function TodayTotalQty() {
    global $wp_query, $post, $wpdb;
    $likeOpr = '%';
    $TodayDate = date('Y-m-d');
    $totalqtypercount = 0;
    $QueryForFindQtyPerDeliveryDatePerProduct = "SELECT m3.meta_value as total FROM brj_woocommerce_order_itemmeta
	INNER JOIN  brj_woocommerce_order_itemmeta m1 ON ( brj_woocommerce_order_itemmeta.order_item_id = m1.order_item_id )
	INNER JOIN  brj_woocommerce_order_itemmeta m2 ON ( brj_woocommerce_order_itemmeta.order_item_id = m2.order_item_id )
	INNER JOIN  brj_woocommerce_order_itemmeta m3 ON ( brj_woocommerce_order_itemmeta.order_item_id = m3.order_item_id )
	WHERE 
	(m1.meta_key = '_line_deliveryDate' AND m1.meta_value  LIKE '{$likeOpr}{$TodayDate}{$likeOpr}')  AND  (m2.meta_key = '_product_id' AND m2.meta_value  = $post->ID)
	AND m3.meta_key = '_qty'
	GROUP BY brj_woocommerce_order_itemmeta.order_item_id";
    $mysqlResultforqty = $wpdb->get_results($QueryForFindQtyPerDeliveryDatePerProduct, OBJECT);
    foreach ($mysqlResultforqty as $keydataforkey => $mysqlResultforqtyval) {
        $totalqtypercount = $totalqtypercount + $mysqlResultforqtyval->total;
    }
    return $totalqtypercount;
}

function theme_options_panel(){
	add_menu_page('Theme page title', 'OMS', 'manage_options', 'woocommerce_reports&tab=sales&chart=report_by_delivery_date', 'wps_theme_func');
	add_submenu_page( 'woocommerce_reports&tab=sales&chart=report_by_delivery_date', 'Google Drive Settings', 'OMS Google Drive', 'manage_options', 'oms_google_drive', 'sp_google_drive_settings' );
  
}
add_action('admin_menu', 'theme_options_panel');
function wps_theme_func(){
                echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div>
                <h2>Theme</h2></div>';
}
function wps_theme_func_settings(){
                echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div>
                <h2>Settings</h2></div>';
}
function wps_theme_func_faq(){
                echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div>
                <h2>FAQ</h2></div>';
}
add_action('woocommerce_reports_charts', 'woocommerce_reports_charts_own', 10, 1);

function woocommerce_reports_charts_own($charts){
	$newChartArray = array();
	$charts['sales']['charts']['report_by_delivery_date'] = array('description' => '', 'function' => 'woocommerce_by_delivery_date', 'title' => 'OMS' );
	return $charts;
	
}


function woocommerce_by_delivery_date_ODODO() {
    global $start_date, $end_date, $woocommerce, $wpdb, $wp_locale;
        $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
        $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

        $sLimit = " LIMIT 100";
        $query = '';
        $likeOpr = '%';
        // Search by Email 
        if (isset($_POST['Email']) && !empty($_POST['Email'])) {
            $joinQuery.= 'LEFT JOIN ' . $wpdb->prefix . 'postmeta m10
                ON ( ' . $wpdb->prefix . 'posts.ID = m10.post_id )';
            $query .= " AND  (";
            $query .= "m10.meta_key = '_billing_email'";
            $query .= " AND m10.meta_value  LIKE '{$likeOpr}{$_POST['Email']}{$likeOpr}'";
            $query .= ") ";
        }
        // Search by First Name
        if (isset($_POST['Name']) && !empty($_POST['Name'])) {
            $joinQuery .= 'LEFT JOIN ' . $wpdb->prefix . 'postmeta m11
		             ON ( ' . $wpdb->prefix . 'posts.ID = m11.post_id )';
            $query .= " AND  (";
            $query .= "m11.meta_key = '_billing_first_name'";
            $query .= " AND m11.meta_value LIKE '{$likeOpr}{$_POST['Name']}{$likeOpr}'";
            $query .= ") ";
        }

        // Search by Last Name
        if (isset($_POST['Name']) && !empty($_POST['Name'])) {
            $joinQuery .= 'LEFT JOIN ' . $wpdb->prefix . 'postmeta m13
		             ON ( ' . $wpdb->prefix . 'posts.ID = m13.post_id )';
            $query .= " AND  (";
            $query .= "m13.meta_key = '_billing_last_name'";
            $query .= " AND m13.meta_value LIKE '{$likeOpr}{$_POST['Name']}{$likeOpr}'";
            $query .= ") ";
        }
        // Search by Phone
        if (isset($_POST['Phone']) && !empty($_POST['Phone'])) {

            $joinQuery .= 'LEFT JOIN ' . $wpdb->prefix . 'postmeta m12
            ON ( ' . $wpdb->prefix . 'posts.ID = m12.post_id )';

            $query .= " AND  (";
            $query .= "m12.meta_key = '_billing_phone'";
            $query .= " AND m12.meta_value  = '{$_POST['Phone']}'";
            $query .= ") ";
        }

        if (!empty($query)) {
            $searchQuery = $query;
        }

        $myquery = "
		SELECT *
		FROM {$wpdb->prefix}woocommerce_order_items as order_items
		{$joinQuery}
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
		LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )
		LEFT JOIN {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id = order_items.order_id
		WHERE 	term.slug IN ('" . implode("','", apply_filters('woocommerce_reports_order_statuses', array('completed', 'processing', 'on-hold'))) . "')
		{$searchQuery}
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND 	order_items.order_item_type = 'line_item'
		AND 	order_item_meta.meta_key = '_qty'
        AND 	 postmeta1.meta_key LIKE '%Delivery / Collection%' and STR_TO_DATE(postmeta1.meta_value,'%W, %e %M, %Y') between STR_TO_DATE('$start_date', '%Y-%m-%d') and STR_TO_DATE('$end_date', '%Y-%m-%d') order by order_items.order_id";

        $order_itemsliest = $myquery . $sLimit;
        $order_items = $wpdb->get_results($order_itemsliest, OBJECT);
        ?>

        <form method="post" action="">
            <p>
                <label for="Name"><?php _e('Name:', 'woocommerce'); ?></label> <input type="text" name="Name" id="Name" value="<?php echo $_POST['Name']; ?>" />
                <label for="Email"><?php _e('Email:', 'woocommerce'); ?></label> <input type="text" name="Email" id="Email" value="<?php echo $_POST['Email']; ?>" />
                <label for="Phone"><?php _e('Phone:', 'woocommerce'); ?></label> <input type="text" name="Phone" id="Phone" value="<?php echo $_POST['Phone']; ?>" /><br/>
            </p>
            <p>
                <label for="from"><?php _e('From:', 'woocommerce'); ?></label> <input type="text" name="start_date" id="from" readonly="readonly" value="<?php echo $start_date; ?>" /> 
                <label for="to"><?php _e('To:', 'woocommerce'); ?></label> <input type="text" name="end_date" id="to" readonly="readonly" value="<?php echo $end_date; ?>" />
                <input type="submit" class="button" value="<?php _e('Show', 'woocommerce'); ?>" /></p>
        </form>
        <style>
            .widefat td, .widefat th {
                border: 1px inset #FFFFFF;
                color: #555555;
            }
        </style>
        <div id="poststuff" class="woocommerce-reports-wrap-class">
            <?php if ($order_items) { ?>
                <div class="woocommerce-reports-main">
                    <table class="wp-list-table widefat fixed pages" cellspacing="0" >

                        <thead>
                            <tr>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Order ID</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Name</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Contact</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Email</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Delivery Date</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Time Slot</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Delivery Address</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Shipping Address</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Delivery Note</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Allergies</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Special Request</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Order Item</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Wordings</span></th>
                                <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Candles</span></th>
                            </tr>
                        </thead>
                        <tbody id="the-list">
                            <?php
                            if (count($order_items) > 0) {
                                foreach ($order_items as $keyData => $order_itemsval) {
                                    $order = new WC_Order($order_itemsval->ID);
                                    $TotalCandles = woocommerce_get_order_item_meta($order_itemsval->order_item_id, 'Small Candles', true) + woocommerce_get_order_item_meta($order_itemsval->order_item_id, 'Big Candles', true);
                                    $Wordingvalue = woocommerce_get_order_item_meta($order_itemsval->order_item_id, 'Wording', true) + 0;
                                    $line_deliveryDate = get_post_meta($order_itemsval->ID, 'Delivery / Collection Date', true);
                                    echo "<tr>";
                                    echo "<td>" . "<a target='_blank' href=" . site_url() . "/wp-admin/post.php?post=$order_itemsval->order_id&action=edit" . ">" . $order_itemsval->order_id . "</a></td>";
                                    echo "<td>" . get_post_meta($order_itemsval->order_id, '_billing_first_name', true) . ' ' . get_post_meta($order_itemsval->order_id, '_billing_last_name', true) . "</td>";
                                    echo "<td>" . get_post_meta($order_itemsval->order_id, '_billing_phone', true) . "</td>";
                                    echo "<td>" . get_post_meta($order_itemsval->order_id, '_billing_email', true) . "</td>";
                                    echo "<td>" . $line_deliveryDate . "</td>";
                                    echo "<td>" . get_post_meta($order_itemsval->ID, 'delivery-time', true) . "</td>";
                                    if ($order->get_formatted_billing_address()) {
                                        echo "<td>" . $order->get_formatted_billing_address() . "</td>";
                                    } else {
                                        echo "<td></td>";
                                    }
                                    if ($order->get_formatted_shipping_address()) {
                                        echo "<td>" . $order->get_formatted_shipping_address() . "</td>";
                                    } else {
                                        echo "<td></td>";
                                    }
                                    echo "<td>" . get_post_meta($order_itemsval->order_id, '_purchase_note', true) . "</td>";
                                    echo "<td>" . get_post_meta($order_itemsval->order_id, 'allergies-diet-requirements', true) . "</td>";
                                    echo "<td>" . woocommerce_get_order_item_meta($order_itemsval->order_item_id, 'Frozen', true) . "</td>";
                                    echo "<td>" . woocommerce_get_order_item_meta($order_itemsval->order_item_id, '_qty', true) . "</td>";
                                    echo "<td>" . $Wordingvalue . "</td>";
                                    echo "<td>" . $TotalCandles . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                }
                ?>

            </div>

           <?php    } ?>
                 <script type="text/javascript">
                jQuery(function() {
            <?php woocommerce_datepicker_js_own(); ?>
                });
            </script>
            <?php
}

function woocommerce_by_delivery_date() {
	
	global $start_date, $end_date, $woocommerce, $wpdb, $wp_locale;

	$start_date = isset( $_POST['start_date'] ) ? $_POST['start_date'] : '';
	$end_date	= isset( $_POST['end_date'] ) ? $_POST['end_date'] : '';
        $name = isset( $_POST['Name'] ) ? $_POST['Name'] : NULL;
        $email = isset( $_POST['Email'] ) ? $_POST['Email'] : NULL;
        $phone = isset( $_POST['Phone'] ) ? $_POST['Phone'] : NULL;
        
        
        $myquery = "select distinct * from (
                        SELECT `order_id`,
                        concat((SELECT meta_value FROM `wp_postmeta` wp_postmeta WHERE `post_id` = order_items.order_id AND `meta_key` = '_billing_first_name'),' ',
                        (SELECT meta_value FROM `wp_postmeta` wp_postmeta WHERE `post_id` = order_items.order_id AND `meta_key` = '_billing_last_name')) last_name,
                        (SELECT meta_value FROM `wp_postmeta` wp_postmeta WHERE `post_id` = order_items.order_id AND `meta_key` = '_billing_email') email,
                        (SELECT meta_value FROM `wp_postmeta` wp_postmeta WHERE `post_id` = order_items.order_id AND `meta_key` = '_billing_phone') phone,
                        (SELECT STR_TO_DATE( meta_value, '%W, %e %M, %Y') FROM `wp_postmeta` wp_postmeta WHERE `post_id` = order_items.order_id AND `meta_key` = 'Delivery / Collection Date') delivery_date,
                        (SELECT meta_value FROM `wp_postmeta` wp_postmeta WHERE `post_id` = order_items.order_id AND `meta_key` = 'delivery-time') delivery_time,
                        (SELECT meta_value FROM `wp_postmeta` wp_postmeta WHERE `post_id` = order_items.order_id AND `meta_key` = '_shipping_address_1') address,
                        (SELECT meta_value FROM `wp_postmeta` wp_postmeta WHERE `post_id` = order_items.order_id AND `meta_key` = '_purchase_note') purchase_note,
                        (SELECT meta_value FROM `wp_postmeta` wp_postmeta WHERE `post_id` = order_items.order_id AND `meta_key` = 'allergies-diet-requirements') allergies,
                        (SELECT meta_value FROM `wp_postmeta` wp_postmeta WHERE `post_id` = order_items.order_id AND `meta_key` = 'Frozen') Frozen,
                        (SELECT meta_value FROM `wp_postmeta` wp_postmeta WHERE `post_id` = order_items.order_id AND `meta_key` = '_qty') order_item,
                        (SELECT meta_value FROM  `wp_woocommerce_order_itemmeta` WHERE  `meta_key` LIKE  'Wording' AND `order_item_id` = order_items.order_item_id limit 1) wording,
                        (SELECT meta_value FROM  `wp_woocommerce_order_itemmeta` WHERE  `meta_key` LIKE  '%Candles%' AND `order_item_id` = order_items.order_item_id limit 1) candles,
                        (SELECT sum(meta_value) FROM `wp_woocommerce_order_items` a, `wp_woocommerce_order_itemmeta` b WHERE  a.order_item_id = b.order_item_id and meta_key = '_qty' and order_id = order_items.order_id) as qty
                        FROM `wp_woocommerce_order_items` order_items 
                        ) a
                        WHERE 
                        (case when length(trim('$name')) = 0 then 1 =1 else last_name like '%$name%' end)
                        and (case when length(trim('$email')) = 0 then 1 =1 else email = '$email' end) 
                        and (case when length(trim('$phone')) = 0 then 1 =1 else phone = '$phone' end) 
                        and (case when length(trim('$start_date')) = 0 then 1 =1 else delivery_date >= STR_TO_DATE('$start_date', '%Y-%m-%d') end)     
                        and (case when length(trim('$end_date')) = 0 then 1 =1 else delivery_date <= STR_TO_DATE('$end_date', '%Y-%m-%d') end) order by delivery_date desc";

//        $myquery = "
//		SELECT *
//		FROM {$wpdb->prefix}woocommerce_order_items as order_items
//		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
//		LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
//		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
//		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
//		LEFT JOIN {$wpdb->terms} AS term USING( term_id )
//		LEFT JOIN {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id = order_items.order_id
//		WHERE 	term.slug IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
//		AND 	posts.post_status 	= 'publish'
//		AND 	tax.taxonomy		= 'shop_order_status'
//		AND 	order_items.order_item_type = 'line_item'
//		AND 	order_item_meta.meta_key = '_qty'
//                AND 	 postmeta1.meta_key LIKE '%Delivery / Collection%' and STR_TO_DATE(postmeta1.meta_value,'%W, %e %M, %Y') between STR_TO_DATE('$start_date', '%Y-%m-%d') and STR_TO_DATE('$end_date', '%Y-%m-%d') order by order_items.order_id";
                
	$order_items = $wpdb->get_results( $myquery );
	
	?>
	
        <?php
			
                if(!empty($_POST['Export']))
				{
					try
					{
						//$file="C:/wamp/www/wordpress/wp-content/plugins/order-delivery-management/";
						$file =dirname( __FILE__ );
						$date=date('d-m-Y_h_i_s');
						$fileName="data_".$date.".csv";	
						$file =$file."/uploads/".$fileName;
						$fp = fopen($file,'w');
						
						$header_value = "\"Order ID\",\"Name\",\"Contact\",\"Email\",\"Delivery Date\",\"Time Slot\",\"Delivery Address\",\"Shipping Address\",\"Delivery Note\",\"Allergies\",\"Special Request\",\"Special Request\",\"Wordings\",\"Candles\"";
						fwrite($fp, $header_value . "\r\n");
						
							foreach ($order_items as $order_itemsval)
							{
								$title = '';
								$order = new WC_Order( $order_itemsval->order_id );
								$TotalCandles = woocommerce_get_order_item_meta($order_itemsval->order_item_id, 'Small Candles', true) + woocommerce_get_order_item_meta($order_itemsval->order_item_id, 'Big Candles', true);
								$Wordingvalue = woocommerce_get_order_item_meta($order_itemsval->order_item_id, 'Wording', true) + 0;
								$line_deliveryDate = get_post_meta($order_itemsval->ID, 'Delivery / Collection Date', true);
				
								$order_id = $order_itemsval->order_id;
								if(strlen($order_id) ==0)
								{
									$order_id ='';
								}
								$last_name =$order_itemsval->last_name;
								if(strlen($last_name) ==0)
								{
									$last_name ='';
								}
								$phone=$order_itemsval->phone;
								if(strlen($phone) ==0)
								{
									$phone ='';
								}
								$email=$order_itemsval->email;
								if(strlen($email) ==0)
								{
									$email ='';
								}
								$delivery_date=$order_itemsval->delivery_date;
								if(strlen($delivery_date) ==0)
								{
									$delivery_date ='';
								}
								$delivery_time=$order_itemsval->delivery_time;
								if(strlen($delivery_time) ==0)
								{
									$delivery_time ='';
								}
								$formatted_billing_address =$order->get_formatted_billing_address();
								if(strlen($formatted_billing_address) ==0)
								{
									$formatted_billing_address ='';
								}
								$formatted_shipping_addres =$order->get_formatted_shipping_address();
								if(strlen($formatted_shipping_addres) ==0)
								{
									$formatted_shipping_addres ='';
								}
								$purchase_note=$order_itemsval->purchase_note;
								if(strlen($purchase_note) ==0)
								{
									$purchase_note ='';
								}
								$post_meta = get_post_meta($order_itemsval->order_id,'allergies-diet-requirements',true);
								if(strlen($post_meta) ==0)
								{
									$post_meta ='';
								}
								$Frozen = $order_itemsval->Frozen;
								if(strlen($Frozen) ==0)
								{
									$Frozen ='';
								}
								$qty = $order_itemsval->qty;
								if(strlen($qty) ==0)
								{
									$qty ='';
								}
								$wording = $order_itemsval->wording;
								if(strlen($wording) ==0)
								{
									$wording ='';
								}
								$candles = $order_itemsval->candles;
								if(strlen($candles) ==0)
								{
									$candles ='';
								}
		
								$valueAll="\"".$order_id."\",\"".$last_name."\",\"".$phone."\",\"".$email."\",\"".$delivery_date."\",\"".$delivery_time."\",\"".$formatted_billing_address."\",\"".$formatted_shipping_addres."\",\"".$purchase_note."\",\"".$post_meta."\",\"".$Frozen."\",\"".$qty."\",\"".$wording."\",\"".$candles."\"";
								$valueAll =strip_tags($valueAll);
								$arrOutput = array($valueAll);
								foreach ($arrOutput as $fields) 
								{						
									fwrite($fp,$fields."\r\n");					
								}
							}
							fclose($fp);
							$filearray = array(
							    'name' => $fileName,
							    'path' => $file
							);
							update_site_option( 'sp_filedetails', $filearray );
						}
						catch(Exception $ex)
						{}
                }
                
                $sp_filepath = get_site_option( 'sp_filedetails', null );
                if( !empty( $sp_filepath ) ) {
		    require_once( 'lib/SP_SpreadSheet.php' );
		    if( class_exists( 'SP_SpreadSheet' ) ) {
			try {
			    $client_id = get_site_option( 'sp_google_client_id', null );
			    $client_secret = get_site_option( 'sp_google_client_secret', null );
			    $redirect_uri = ( is_ssl()?'https':'http' ). "://" .$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			    $sp_ss = new SP_SpreadSheet( $client_id, $client_secret, $redirect_uri );
			    $sp_ss->uploadFile( $sp_filepath['name'], $sp_filepath['path'] );
			}
			catch(Exception $ex) {
			    error_log( $ex->getMessage() );
			}
		    }
		    
		    try
		    {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.$sp_filepath['name']);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($sp_filepath['path']));
			ob_clean();
			flush();
			readfile($sp_filepath['path']);
			unlink($sp_filepath['path']);
			update_site_option( 'sp_filedetails', null );
		    }
		    catch(Exception $ex)
		    {}
                }
        ?>

<form method="post" action="">
            <p>
                <label for="Name"><?php _e('Name:', 'woocommerce'); ?></label> <input type="text" name="Name" id="Name" value="<?php echo $_POST['Name']; ?>" />
                <label for="Email"><?php _e('Email:', 'woocommerce'); ?></label> <input type="text" name="Email" id="Email" value="<?php echo $_POST['Email']; ?>" />
                <label for="Phone"><?php _e('Phone:', 'woocommerce'); ?></label> <input type="text" name="Phone" id="Phone" value="<?php echo $_POST['Phone']; ?>" /><br/>
            </p>
            <p>
                <label for="from"><?php _e('From:', 'woocommerce'); ?></label> <input type="text" name="start_date" id="from" readonly="readonly" value="<?php echo $start_date; ?>" /> 
                <label for="to"><?php _e('To:', 'woocommerce'); ?></label> <input type="text" name="end_date" id="to" readonly="readonly" value="<?php echo $end_date; ?>" />
                <input type="submit" class="button" value="<?php _e('Show', 'woocommerce'); ?>" />
                <input type="submit" name="Export" class="button" id="Export" value="<?php _e('Export', 'woocommerce'); ?>"  />
            </p>
        </form>	<style>
	.widefat td, .widefat th {
	    border: 1px inset #FFFFFF;
	    color: #555555;
	}
	</style>
	<div id="poststuff" class="woocommerce-reports-wrap-class">
	<?php
			if ( $order_items ) {
		?>
		<div class="woocommerce-reports-main">
			<table class="wp-list-table widefat fixed pages" cellspacing="0" id="mytable">

	<thead>
		<tr>
			<th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Order ID</span></th>
                        <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Name</span></th>
                        <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Contact</span></th>
                        <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Email</span></th>
			<th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Delivery Date</span></th>
			<th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Time Slot</span></th>
                        <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Delivery Address</span></th>
                        <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Shipping Address</span></th>
                        <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Delivery Note</span></th>
                        <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Allergies</span></th>
                        <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Special Request</span></th>
			<th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Order Item</span></th>
			<th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Wordings</span></th>
                        <th style="font-weight:bold;height:40px;padding-left:10px" class="manage-column column-title sortable desc" id="title" scope="col"><span>Candles</span></th>
		</tr>
	</thead>
	<tbody id="the-list">
		<?php
				
  		foreach ($order_items as $order_itemsval)
		{
                        $title = '';
                        $order = new WC_Order( $order_itemsval->order_id );
                        $TotalCandles = woocommerce_get_order_item_meta($order_itemsval->order_item_id, 'Small Candles', true) + woocommerce_get_order_item_meta($order_itemsval->order_item_id, 'Big Candles', true);
                        $Wordingvalue = woocommerce_get_order_item_meta($order_itemsval->order_item_id, 'Wording', true) + 0;
			$line_deliveryDate = get_post_meta($order_itemsval->ID, 'Delivery / Collection Date', true);
			echo "<tr>";
							
		
						echo "<td>"."<a target='_blank' href=".site_url()."/wp-admin/post.php?post=$order_itemsval->order_id&action=edit".">".$order_itemsval->order_id."</a></td>";
                        
                        echo "<td>".$order_itemsval->last_name."</td>";
                        
                        echo "<td>".$order_itemsval->phone."</td>";
                        
                        echo "<td>".$order_itemsval->email."</td>";
                        
						echo "<td>".date( 'l, d F, Y', strtotime( $order_itemsval->delivery_date ) ) ."</td>";
                        
						echo "<td>".$order_itemsval->delivery_time."</td>";
                       if ( $order->get_formatted_billing_address() )
					   {
                            echo "<td>".$order->get_formatted_billing_address()."</td>";
                       }
					   else
					   {
                          echo "<td></td>";
                       }
                       if ( $order->get_formatted_shipping_address() ) 
					   { 
                            echo "<td>".$order->get_formatted_shipping_address()."</td>";
                       }
					   else 
					   {
                            echo "<td></td>";
                       }
                         
                       echo "<td>".$order_itemsval->purchase_note."</td>";
                        
                       echo "<td>".get_post_meta($order_itemsval->order_id,'allergies-diet-requirements',true)."</td>";
                         
                       echo "<td>".$order_itemsval->Frozen."</td>";
                        
					   echo "<td>".$order_itemsval->qty."</td>";
                        
			           echo "<td>".$order_itemsval->wording."</td>";
                        
                      echo "<td>".$order_itemsval->candles."</td>";
                        
					echo "</tr>";
					
			}	
		?>
	</tbody>
	</table>
		</div>
		<?php
                
                
		
	}
	?>
		
	</div>
	
	<script type="text/javascript">
		jQuery(function(){
			<?php woocommerce_datepicker_js_own(); ?>
		});
	</script>
	<?php
}
   
function woocommerce_datepicker_js_own() {
	global $woocommerce;
	 $plugins_url = plugins_url();
   $plugins_url .= '/order-delivery-management';
	?>
        
	var dates = jQuery( "#from, #to" ).datepicker({
		defaultDate: "",
		dateFormat: "yy-mm-dd",
		minDate: "-12M",
		maxDate: "+4M",
		numberOfMonths: 1,
		showButtonPanel: true,
		showOn: "button",
		buttonImage: "<?php echo $plugins_url; ?>/calendar.png",
		buttonImageOnly: true,
		onSelect: function( selectedDate ) {
			var option = this.id == "from" ? "minDate" : "maxDate",
				instance = jQuery( this ).data( "datepicker" ),
				date = jQuery.datepicker.parseDate(
					instance.settings.dateFormat ||
					jQuery.datepicker._defaults.dateFormat,
					selectedDate, instance.settings );
			dates.not( this ).datepicker( "option", option, date );
		}
	});
        
        
	<?php
}

    
function dateRange( $first, $last, $step = '+1 day', $format = 'n/j/Y' ) {
 
	$dates = array();
	$current = strtotime( $first );
	$last = strtotime( $last );
 
	while( $current <= $last ) {
 
		$dates[] = date( $format, $current );
		$current = strtotime( $step, $current );
	}
 
	return $dates;
}

function sp_google_drive_settings() {

    $sp_saved = false;
    if( isset( $_POST['sp_save_google'] ) ) {
	update_site_option( 'sp_google_client_id', $_POST['sp_client_id'] );
	update_site_option( 'sp_google_client_secret', $_POST['sp_client_secret'] );
	$sp_saved = true;
    }
    
    $sp_client_id = get_site_option( 'sp_google_client_id', null );
    $sp_client_secret = get_site_option( 'sp_google_client_secret', null );
    
    if( $sp_saved ) {
	?>
	    <div class="updated fade">
		<p>
		    <?php _e( 'Settings Saved', 'woocommerce' ); ?>
		</p>
	    </div>
	<?php
    }
    ?>
	<form method="post" action="">
	    <table class="form-table">
		<tr valign="top">
		    <td colspan="2">
			<strong>
			    <?php _e( 'Settings for Google Drive to export Order Delivery Schedule', 'woocommerce' ); ?>
			</strong>
		    </td>
		</tr>
		<tr valign="top">
		    <td>
			<?php _e( 'Client Id', 'woocommerce' ); ?>
		    </td>
		    <td>
			<input type="text" value="<?php echo !empty( $sp_client_id )?$sp_client_id:''; ?>" name="sp_client_id" required />
		    </td>
		</tr>
		<tr valign="top">
		    <td>
			<?php _e( 'Client Secret', 'woocommerce' ); ?>
		    </td>
		    <td>
			<input type="text" value="<?php echo !empty( $sp_client_secret )?$sp_client_secret:''; ?>" name="sp_client_secret" required />
		    </td>
		</tr>
		<tr valign="top">
		    <td>
			<?php _e( 'Note', 'woocommerce' ); ?>
		    </td>
		    <td>
			<?php _e( 'Please set the following url as the application redirect url in google while creating client id.', 'woocommerce' ) ?>
			<br />
			<?php echo site_url().'/wp-admin/admin.php?page=woocommerce_reports&tab=sales&chart=report_by_delivery_date'; ?>
		    </td>
		</tr>
		<tr>
		    <td colspan="2">
			<input type="submit" name="sp_save_google" class="button" value="<?php _e( 'Save', 'woocommerce' ); ?>" />
		    </td>
		</tr>
	    </table>
	</form>
    <?php
}

}