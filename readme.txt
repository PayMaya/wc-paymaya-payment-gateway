=== Maya Business Plugin ===
Tags: payments, credit card
Requires at least: 5.0
Tested up to: 5.9
Requires PHP: 5.6
Stable tag: 1.0.8
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Accept payments in WooCommerce using Maya

== Description ==

= Give your customers a better online checkout experience =

With Maya Checkout, your website or app can directly accept credit and debit cards, e-wallet, and other emerging payment solutions.

* Mastercard
* Visa
* JCB
* WeChat Pay
* Pay With Maya

= Features =
* Payments via Maya Checkout
* Full 3DS Support and PCI-DSS Compliant
* Checkout page customizations
* Voids and Refunds
* Straight purchase or Authorization & Capture

**Don't have an account yet? [Click here to get started](https://enterprise.paymaya.com/solutions/plugins/woocommerce)**

== Installation ==

This gateway requires WooCommerce 3.9.3 and above.

= Setup =

1. After installation and activation, go to WooCommerce menu on the left sidebar of your WordPress admin dashboard.
2. Go to Settings, then go to Payments tab.
3. You should see a **Payments via Maya** item. Click on the Manage/Set up button.
4. Enter your public and private API keys in the **API Keys** section.

= Sandbox Mode =

To test payments, enable **Sandbox Mode**. This will let you transact test payments and check if your WordPress and WooCommerce installation doesn't have conflicts with the plugin. Although **not required**, it is **highly recommended** to test your plugin installation first. You can find test API keys and credit cards [here](https://hackmd.io/@paymaya-pg/Checkout#Sandbox-Test-Credentials).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Changelog ==

= 1.0.8 =
*Release Date - September 9, 2022*

* Add debug mode for better logging
* Add optional address line 2 setting
* Fix error handling for certain processes
* Tested compatibility for WP 6.0.2
* Tested compatibility for WC 6.8.2

= 1.0.7 =
*Release Date - May 16, 2022*

* Rebrand PayMaya to Maya
* Tested compatibility for WC 6.5.1

= 1.0.6 =
*Release Date - January 28, 2022*

* Fix admin scripts loading issue with non-existent order ID
* Tested compatibility up to WP 5.9 and WooCommerce 6.1.1

= 1.0.5 =
*Release Date - December 17, 2021*

* Fix issue with shipping detail requirements

= 1.0.4 =
*Release Date - September 27, 2021*

* Added error logger for Maya API errors

= 1.0.3 =
*Release Date - 3 August 2021*

* Fix line item bug - clump into one for now

= 1.0.2 =
*Release Date - 17 November 2020*

* Fixed bug for decimal values for items

= 1.0.1 =
*Release Date - 4 November 2020*

* Fixed bug for manual captures on order details

= 1.0.0 =
*Release Date - 21 September 2020*

* Initial release
