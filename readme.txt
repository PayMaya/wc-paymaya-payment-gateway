=== Paymaya for WooCommerce ===
Tags: payments, credit card
Requires at least: 5.0
Tested up to: 5.5
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Accept payments in WooCommerce using Paymaya

== Description ==

Accept Visa & MasterCard payments directly on your Woocommerce store with the Paymaya.

Features:
* Securely process credit card payments using Paymaya Checkout
* Voiding and refunding functions, including partial refunds
* Manual payment captures

== Installation ==

This gateway requires WooCommerce 3.9.3 and above.

= Setup =

1. After installation and activation, go to WooCommerce menu on the left sidebar of your WordPress admin dashboard.
2. Go to Settings, then go to Payments tab.
3. You should see a **Payments via Paymaya** item. Click on the Manage/Set up button.
4. Enter your public and private API keys in the **API Keys** section.

= Sandbox Mode =

To test payments, enable **Sandbox Mode**. This will let you transact test payments and check if your WordPress and WooCommerce installation doesn't have conflicts with the plugin. Although **not required**, it is **highly recommended** to test your plugin installation first. You can find test API keys and credit cards [here](https://hackmd.io/@paymaya-pg/Checkout#Sandbox-Test-Credentials).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Changelog ==

= 1.0.0 =
*Release Date - 21 September 2020*

Initial release
