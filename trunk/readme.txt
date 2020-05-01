=== NETOPIA Payments Woocommerce Payment Gateway ===
Contributors: NETOPIA
Tags: mobilpay, woocommerce, mobilpay card, mobilpay payment gateway, mobilpay for woocommerce, mobilpay romania, mobilpay card for woocommerce, netopia, netopia payments
Requires at least: 4.0.1
Tested up to: 5.3
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

NETOPIA Payments Woocommerce Payment Gateway extends WooCommerce payment options by adding NETOPIA's Payment Gateway options.

== Description ==

NETOPIA Payments Woocommerce Payment Gateway extends WooCommerce payment options by adding NETOPIA's Payment Gateway options.

= Features: =

* **100% FREE TO USE** (GPLv2 license).
* Integrates NETOPIA payments' card and cryptocoin payments service with your WordPress + WooCommerce online shop. SMS and Wire transfer options are still under development.
* Accepts payments with Visa and Mastercard credit/debit cards, Bitcoin and Ethereum
* Handles IPN responses and automatically changes order status on your shop in real time (confirmed/paid or failure messages, even refunds).

== Installation ==

1. Upload `netopiapayments` to the `/wp-content/plugins/` directory.

2. Activate the plugin through the `Plugins` menu in WordPress.

3. Configure your settings under `WooCommerce > Settings > Checkout > NETOPIA Payments` option panel: enable the payment gateway and test mode, fill in your Merchant ID (get it from your Netopia account under Admin - Seller accounts - Edit - Security settings) and select at least one payment option (usually Credit Card).

4. Place your `private.key` and `public.cer` files from your NETOPIA merchant account into the `netopia` folder of the plugin. These certificates should look like this: `live.XXXX-XXXX-XXXX-XXXX-XXXXprivate.key` and `live.XXXX-XXXX-XXXX-XXXX-XXXX.public.cer`. Don't rename `.key` and `.cer` files and make sure that `XXXX-XXXX-XXXX-XXXX-XXXX` matches your Merchant ID! For testing purposes you will also need your sandbox keys to be uploaded into the `netopia` folder of the plugin. These certificates should look like this: `sandbox.XXXX-XXXX-XXXX-XXXX-XXXXprivate.key` and `sandbox.XXXX-XXXX-XXXX-XXXX-XXXX.public.cer`. Don't rename `.key` and `.cer` files and make sure that `XXXX-XXXX-XXXX-XXXX-XXXX` matches your Merchant ID!


== Screenshots ==

1. Backend: WooCommerce > Settings > Checkout
`screenshot-1.png`

2. Frontend: Your website checkout page
`screenshot-2.png`

== Changelog ==

= 1.0 =
* Initial release (Tested up to WP 5.3.3 with WooCommerce 4.0.1)
