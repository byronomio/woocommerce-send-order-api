# WooCommerce Send Order API Plugin
[![License](https://img.shields.io/badge/license-GPL--3.0-blue.svg)](http://www.gnu.org/licenses/gpl-3.0.html) 

WooCommerce Send Order API is a WordPress plugin that sends all new WooCommerce orders to an external API. This plugin is extremely handy if you want to synchronize your WooCommerce store's orders with a different system using its API.

The plugin triggers whenever an order's status changes to 'processing', 'on-hold', or 'completed'. It then captures the necessary order information and sends it in a JSON format to the specified external API.

## Features

- Triggers on order status change
- Sends detailed order information to an external API
- The order data is sent in JSON format for easy processing
- Adds a meta box in the WooCommerce Order Details page, displaying the API response details

## Installation

1. Download the plugin files to your `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Set the `api_key` variable in the plugin code to your actual API key and set the `api_url` to your actual API URL.
4. Use the plugin settings to configure the prefix and suffix for the order number if necessary.

## Usage

The plugin triggers when the WooCommerce order status changes to 'processing', 'on-hold', or 'completed'. When this happens, it constructs a payload from the order data and sends a POST request to the defined API.

The payload contains detailed order data, including customer information, shipping method, payment method, line items, and shipping & billing addresses.

After sending the request, it stores the response details as custom fields on the order. This includes the time the request was sent, the response code, the error message (if any), and the payload that was sent.

It also adds a "Send Order API Info" meta box to the order page in the WooCommerce admin. This meta box displays the API response details, including the send time, response code, error message, and payload.

## Keywords

WordPress, WooCommerce, WooCommerce API, WooCommerce Order API, Order Management, Order Synchronization, WooCommerce Plugin, WooCommerce Extension.
