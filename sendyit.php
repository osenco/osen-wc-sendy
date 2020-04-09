<?php

/**
 * Plugin Name:       Sendy For WooCommmerce
 * Plugin URI:        https://github.com/sendyit/woocommerce
 * Description:       This is the Sendy WooCommerce Plugin for Sendy Public API.
 * Author:            Dervine N
 * Version: 1.20.4
 * Author URI: https://osen.co.ke/
 *
 * Requires at least: 4.6
 * Tested up to: 5.4
 *
 * WC requires at least: 3.5.0
 * WC tested up to: 4.0
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sendyit
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined("WPINC")) {
	die;
}

define("PLUGIN_NAME_VERSION", "1.20.4");

register_activation_hook(__FILE__, "activate_sendy_api");
function activate_sendy_api()
{
	require_once plugin_dir_path(__FILE__) . "includes/class-sendy-api-activator.php";
	Sendy_Api_Activator::activate();
}

register_deactivation_hook(__FILE__, "deactivate_sendy_api");
function deactivate_sendy_api()
{
	require_once plugin_dir_path(__FILE__) . "includes/class-sendy-api-deactivator.php";
	Sendy_Api_Deactivator::deactivate();
}

require plugin_dir_path(__FILE__) . "includes/class-sendy-api.php";

/**
 * Check if WooCommerce is active
 */
if (in_array("woocommerce/woocommerce.php", apply_filters("active_plugins", get_option("active_plugins")))) {
	add_filter('woocommerce_shipping_methods', function ($methods) {
		require_once plugin_dir_path(__FILE__) . "includes/class-wc-sendy-shipping-method.php";
		$methods['sendyit'] = WC_Sendy_Shipping_Method::class;

		return $methods;
	});

	add_action("woocommerce_cart_totals_before_shipping", "get_delivery_address");
	function get_delivery_address()
	{
		echo '<div class="sendy-api">
			<div class="input-block">
			   <input class="input" id="api_to" type="text" placeholder="Enter Delivery Address to Get A Sendy Quote">
			</div>
			</div>
			<div class="loader"></div>
			<div id="pricing" class="divHidden">
				<div class="show-currency" >KES</div>
				<div class="show-price">240</div>
			</div>
			<button id="submitBtn" type="button" class="btn">Estimating...</button>
			<div id="info-block" class="alert alert-info">
				Please choose a location within Nairobi to deliver with Sendy.
			</div>
			<div id="error-block" class="alert alert-danger">
			</div>';
	}

	add_action("wp_enqueue_scripts", "add_js_scripts");
	function add_js_scripts()
	{
		wp_register_script("moment", "https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.20.1/moment.min.js", null, null, true);
		wp_enqueue_script("moment");
		//wp_enqueue_script("moment", plugin_dir_url(__FILE__) . "/public/js/cookie.js", array("jquery"), "1.0", true);
		wp_enqueue_script("cookie-script", plugin_dir_url(__FILE__) . "public/js/cookie.js", array("jquery"), "1.0", true);
		wp_enqueue_script("ajax-script", plugin_dir_url(__FILE__) . "public/js/sendy-api-public.js", array("jquery"), "1.0", true);
		wp_localize_script("ajax-script", "ajax_object", array("ajaxurl" => admin_url("admin-ajax.php")));
	}

	//add_action("wp_enqueue_scripts", "add_style");
	function add_style()
	{
		wp_enqueue_style("styles", plugin_dir_url(__FILE__) . "public/css/sendy-api-public.css", false);
	}

	add_action("admin_enqueue_scripts", "add_admin_scripts");
	function add_admin_scripts()
	{
		wp_enqueue_script("admin-script", plugin_dir_url(__FILE__) . "admin/js/sendy-api-admin.js", array("jquery"), "1.0", true);
	}

	add_action("wp_ajax_nopriv_getPriceQuote", "getPriceQuote");
	add_action("wp_ajax_getPriceQuote", "getPriceQuote");
	function getPriceQuote($type = "quote", $pick_up_date = null, $note = "Sample Note", $recepient_name = "Dervine N", $recepient_phone = "0716163362", $recepient_email = "ndervine@sendy.co.ke", $delivery = false)
	{
		$sendy_settings = get_option("woocommerce_sendyit_settings");
		$api_key = $sendy_settings["sendy_api_key"];
		$api_username = $sendy_settings["sendy_api_username"];
		$pickup = $sendy_settings["shop_location"];
		$pickup_lat = $sendy_settings["from_lat"];
		$pickup_long = $sendy_settings["from_long"];
		$sender_phone = $sendy_settings["phone"];
		$test = ($sendy_settings["live"] == "yes") ? '' : 'test';

		//if post is set
		if (isset($_POST["to_name"])) {
			$to_name = $_POST["to_name"];
			$to_lat = $_POST["to_lat"];
			$to_long = $_POST["to_long"];
			//then update session
			WC()->session->set("to_name", $to_name);
			WC()->session->set("to_lat", $to_lat);
			WC()->session->set("to_long", $to_long);
		} else {
			//use session
			$to_name = WC()->session->get("to_name", $pickup);
			$to_lat = WC()->session->get("to_lat", $pickup_lat);
			$to_long = WC()->session->get("to_long", $pickup_long);
		}

		$to_name = $to_name;
		$to_lat = $to_lat;
		$to_long = $to_long;

		$cart_items = [];

		foreach (WC()->cart->get_cart() as $cart_item) {
			$item_name = $cart_item['data']->get_title();
			$product =  wc_get_product($cart_item['data']->get_id());

			$cart_items[] = array(
				"weight" => $product->has_weight() ? $product->get_weight() : 20,
				"height" => $product->get_height(),
				"width" => $product->get_width(),
				"length" => $product->get_length(),
				"item_name" => $item_name
			);
		}

		$payload = array(
			"command" => "request",
			"data" => array(
				"api_key" => $api_key,
				"api_username" => $api_username,
				"vendor_type" => 1,
				"rider_phone" => "0728561783",
				"from" => array(
					"from_name" => $pickup,
					"from_lat" => $pickup_lat,
					"from_long" => $pickup_long,
					"from_description" => get_bloginfo('description')
				),
				"to" => array(
					"to_name" => $to_name,
					"to_lat" => $to_lat,
					"to_long" => $to_long,
					"to_description" => "Delivery to {$recepient_name} in {$to_name}"
				),
				"recepient" => array(
					"recepient_name" => $recepient_name,
					"recepient_phone" => $recepient_phone,
					"recepient_email" => $recepient_email,
					"recepient_notes" => $note
				),
				"sender" => array(
					"sender_name" => get_bloginfo('name'),
					"sender_phone" => $sender_phone,
					"sender_email" => get_bloginfo('admin_email'),
					"sender_notes" => "Pickup from {$pickup}"
				),
				"delivery_details" => array(
					"pick_up_date" => (is_null($pick_up_date) ? date("Y-m-d H:i:s", strtotime("+2 hours")) : $pick_up_date),
					"collect_payment" => array(
						"status" => false,
						"pay_method" => 0,
						"amount" => 10
					),
					"return" => false,
					"note" => $note,
					"note_status" => true,
					"request_type" => $type,
					"order_type" => "ondemand_delivery",
					"ecommerce_order" => false,
					"express" => false,
					"skew" => 1,
					"package_size" => $cart_items
				)
			),
			"request_token_id" => base64_encode("{$api_username}:{$api_key}")
		);

		$endpoint = "https://api{$test}.sendyit.com/v1/#request";

		$response     = wp_remote_post(
			$endpoint,
			array(
				"headers" => array(
					"Content-Type" => "application/json",
					//'Authorization' => 'Basic ' . base64_encode($api_username . ':' . $api_key)
				),
				"body"    => wp_json_encode($payload)
			)
		);

		if (is_wp_error($response)) {
			$json = array(
				"status" => false,
				"description" => "Something went wrong: " . $response->get_error_message()
			);
		} else {
			$json = json_decode($response["body"], true);
		}

		WC()->session->set("orderCost", $json["data"]["amount"]);
		WC()->session->set("sendyOrderNo", $json["data"]["order_no"]);

		wp_send_json($json, 200);
	}

	add_action("wp_ajax_nopriv_displayDelivery", "displayDelivery");
	add_action("wp_ajax_displayDelivery", "displayDelivery");
	add_action("woocommerce_after_shipping_rate", "displayDelivery");
	function displayDelivery()
	{
		//if (isset($_POST)) {
		$hour = date("H", strtotime("+3 hours"));
		if ($hour >= 14 && $hour <= 20) {
			echo '<div id="delivery-info" class="alert alert-info">
				Orders placed from <b>2 PM</b> will be delivered on the next day via Sendy.
			</div>';
		} elseif ($hour >= 6 && $hour <= 14) {
			echo '<div id="delivery-info" class="alert alert-info">
				Orders placed before <b>2 PM</b> will be delivered on the same day via Sendy.
			</div>';
		} else {
			echo '<div id="delivery-info" class="alert alert-info">
				Place orders as from <b>6 AM</b> to <b>8 PM</b> to deliver with Sendy.
			</div>';
		}
		////}
	}

	add_filter("woocommerce_package_rates", "setSendyDeliveryCost", 10, 2);
	function setSendyDeliveryCost($rates, $package)
	{
		$cost = WC()->session->get("orderCost", 0);
		if (isset($rates["sendyit"])) {
			$rates["sendyit"]->cost = $cost;
		}

		return $rates;
	}

	add_action("woocommerce_thankyou", "completeOrder", 10, 1);
	function completeOrder($order_id)
	{
		if (!$order_id) {
			return;
		} else {
			$order = new WC_Order($order_id);
			$note = $order->get_customer_note();
			$fName = $order->get_billing_first_name();
			$lName = $order->get_billing_last_name();
			$name = "{$fName} {$lName}";
			$phone = $order->get_billing_email();
			$email = $order->get_billing_phone();

			$sendy_settings = get_option("woocommerce_sendyit_settings");
			$order_no = WC()->session->get("sendyOrderNo");
			$sendy_hour = 14;
			$type = "delivery";
			$open_hour = $sendy_settings["open_hours"];
			$close_hour = $sendy_settings["close_hours"];
			$order_hour = date("H", strtotime("+3 hours"));

			if ($order_hour < $open_hour && $order_hour >= $close_hour && $close_hour < 20) {
				$pick_up_date = date("Y-m-d H:i:s", strtotime("+3 hours"));
			} elseif ($order_hour >= $open_hour && $order_hour < $sendy_hour) {
				$pick_up_date = date("Y-m-d H:i:s", strtotime("+3 hours"));
			} elseif ($order_hour >= $sendy_hour && $order_hour < $close_hour && $close_hour < 20) {
				$pick_up_date = date("Y-m-d H:i:s", mktime(8, 00, 0, date("n"), date("j") + 1, date("Y")));
			} else {
				$pick_up_date = date("Y-m-d H:i:s", strtotime("+3 hours"));
			}

			getPriceQuote($type, $pick_up_date, $note, $name, $phone, $email, true);

			$tracking_url = "https://app.sendyit.com/biz/sendyconnect/track/" . $order_no;

			echo '<p><a target=\"_tab\" href="' . $tracking_url . '"> Click Here To Track Your Sendy Order. </a></p>';
			return;
		}
	}
}

function run_sendy_api()
{
	$plugin = new Sendy_Api();
	$plugin->run();
}

run_sendy_api();