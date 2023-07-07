<?php

/**
 * Plugin Name: WooCommerce Send Order API
 * Description: A WooCommerce plugin that sends all new orders to an external URL via API connection using cURL.
 * Version: 1.0
 * Author: Byron Jacobs
 * Author URI: https://byronjacobs.co.za
 */

// Ensures the script is not accessed directly, for security reasons
if (!defined('ABSPATH')) {
    exit;
}

// Check if the class exists to avoid naming conflicts with other plugins
if (!class_exists('WC_Send_Order_API')) {

    class WC_Send_Order_API
    {
        // Set prefix and suffix for the order number
        public $prefix = 'PRE';
        public $suffix = 'SUF';

        // Set API key and key type variables
        public $api_key = 'YOUR_API_KEY';  // Replace 'YOUR_API_KEY' with your actual API key
        public $api_key_type = 'Bearer';  // 'Bearer', 'Basic', etc.

        // Constructor: This function is automatically called when a new instance of the class is created
        public function __construct()
        {
            // Add an action that triggers when the WooCommerce order status changes
            // This will run the send_order_api function
            add_action('woocommerce_order_status_changed', [$this, 'send_order_api'], 10, 3);

            // Add a meta box to the order page in the WooCommerce admin
            add_action('add_meta_boxes', [$this, 'add_order_api_info_metabox']);
        }

        // This function sends order data to the external API
        public function send_order_api($order_id, $from_status, $to_status)
        {
            // Get the order object
            $order = wc_get_order($order_id);

            // Check if the order object exists
            if (!$order) return;

            // Check if the new order status is one we want to send to the API
            // In this case, we're looking for orders that are processing, on-hold, or completed
            if (!in_array($to_status, ['processing', 'on-hold', 'completed'])) return;

            // Get the billing and shipping address from the order
            $billing_address = $order->get_address('billing');
            $shipping_address = $order->get_address('shipping');

            // Build the payload data
            $payload = array(
                'order_number' => $this->prefix . $order->get_order_number() . $this->suffix,
                'customer_name' => $billing_address['first_name'] . ' ' . $billing_address['last_name'],
                'customer_email' => $order->get_billing_email(),
                'customer_phone' => $order->get_billing_phone(),
                'shipping_method' => $order->get_shipping_method(),
                'payment_method' => $order->get_payment_method_title(),
                'customer_note' => $order->get_customer_note(),
                'line_items' => array_map(function ($item) {
                    // For each line item in the order, return an array of the product details
                    return array(
                        'product_id' => $item->get_product_id(),
                        'sku' => $item->get_product()->get_sku(),
                        'quantity' => $item->get_quantity(),
                        'price' => $item->get_total() // Total price for that line item
                    );
                }, $order->get_items()),
                'shipping_price' => $order->get_shipping_total(),
                'billing' => array(
                    'first_name' => $billing_address['first_name'],
                    'last_name' => $billing_address['last_name'],
                    'company' => $billing_address['company'],
                    'address_1' => $billing_address['address_1'],
                    'address_2' => $billing_address['address_2'],
                    'city' => $billing_address['city'],
                    'state' => $billing_address['state'],
                    'postcode' => $billing_address['postcode'],
                    'country' => $billing_address['country'],
                    'email' => $billing_address['email'],
                    'phone' => $billing_address['phone']
                ),
                'shipping' => array(
                    'first_name' => $shipping_address['first_name'],
                    'last_name' => $shipping_address['last_name'],
                    'company' => $shipping_address['company'],
                    'address_1' => $shipping_address['address_1'],
                    'address_2' => $shipping_address['address_2'],
                    'city' => $shipping_address['city'],
                    'state' => $shipping_address['state'],
                    'postcode' => $shipping_address['postcode'],
                    'country' => $shipping_address['country']
                )
            );

            // Define the URL for your API and the arguments for the request
            $api_url = ''; // Replace with your API url
            $args = array(
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => $this->api_key_type . ' ' . $this->api_key, // Use API key for authorization
                ),
                'body' => json_encode($payload),  // Convert the payload data to JSON format
            );

            // Send the request to the API and store the response
            $response = wp_remote_request($api_url, $args);

            // Store the details of the API response as custom fields on the order
            update_post_meta($order_id, '_api_send_time', current_time('mysql'));
            update_post_meta($order_id, '_api_response_code', wp_remote_retrieve_response_code($response));
            update_post_meta($order_id, '_api_payload', $payload);
            update_post_meta($order_id, '_api_error_message', wp_remote_retrieve_response_message($response));
            update_post_meta($order_id, '_api_line_items', $order->get_items());
        }

        // This function adds a "Send Order API Info" meta box to the order page in the WooCommerce admin
        public function add_order_api_info_metabox()
        {
            add_meta_box(
                'wc_order_api_info', // Unique ID for the meta box
                'Send Order API Info', // Title displayed at the top of the meta box
                [$this, 'order_api_info_metabox_content'], // Callback function to output the content of the meta box
                'shop_order', // Post type where the meta box should appear
                'side', // Where the meta box should be placed (side, normal, advanced)
                'high' // Priority level for the meta box position (high, core, default, low)
            );
        }

        // Function to output the content of the "Send Order API Info" meta box
        public function order_api_info_metabox_content($post)
        {
            // Get the custom field values
            $send_time = get_post_meta($post->ID, '_api_send_time', true);
            $response_code = get_post_meta($post->ID, '_api_response_code', true);
            $payload = get_post_meta($post->ID, '_api_payload', true);
            $error_message = get_post_meta($post->ID, '_api_error_message', true);

            // Display the custom field values in the meta box
            echo '<p><strong>API Send Time:</strong> ' . $send_time . '</p>';
            echo '<p><strong>API Response Code:</strong> ' . $response_code . '</p>';
            echo '<p><strong>API Error Message:</strong> ' . $error_message . '</p>';
            echo '<p><strong>API Payload:</strong> <pre>' . print_r($payload, true) . '</pre></p>';

            // Check the response code and provide feedback
            if ($response_code >= 200 && $response_code < 300) {
                echo '<p><strong>API Status:</strong> Success!</p>';
            } else {
                echo '<p><strong>API Status:</strong> Failed. Check the error message and payload for debugging information.</p>';
            }
        }
    }

    // Create an instance of the WC_Send_Order_API class
    $GLOBALS['WC_Send_Order_API'] = new WC_Send_Order_API();
}
