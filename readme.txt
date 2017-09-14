=== Waves Gateway for Woocommerce ===
Contributors: uwtoken,tubbynl
Donate link: Waves: 3P4gvv7rZC1kFDobs4oQHN3H6NQckWiu9wz (tubbynl),3PFn9SGPJ8yVjcmBps48Jx6ddz1QXwUiYqP (uwtoken)
Tags: billing, invoicing, woocommerce, payment
Requires at least: 3.0.1
Tested up to: 4.8
Stable tag: 0.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Show prices in WAVES and accept Waves payments in your woocommerce webshop

== Description ==

Display prices in WAVES and let your clients pay through Waves. Built on top of Ripple Gateway developed by Casper Mekel and uses Base58 library developed by Stephen Hill for encoding and decoding. 

* Display prices in WAVES in store and on checkout
* Prices are calculated based on Cryptonator exchange rate
* Links can be copied by clicking and a QR code is supplied which can be used in the Waves wallet app op iOS and Android
* Countdown refreshes form each 10 minutes, updating amounts using the most recent conversion reate
* Matches payments on (encoded) attachment and amount
* Checkout page is automatically refreshed after a successful payment 
* Dutch and Russian translations included. More translations are welcome.

== Installation ==

Install the plugin by uploading the zipfile in your WP admin interface or via FTP:

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Find the configuration in Woocommerce->Payment page and fill out your settings and preferences

== Frequently Asked Questions ==

== Screenshots ==

1. Admin Settings
2. Front-end view

== Changelog ==

- 0.2.0
* Fixed the WNET code to correctly work with woocommerce again
* Routed the WNET exchange REST requests the same as the Waves REST requests
- 0.1.3
* updated this plugin to WNET currency
- 0.0.2
* Correct issue which caused the zip files to be broken
- 0.0.1
* Initial release

== Upgrade Notice ==

No upgrade notices apply.



