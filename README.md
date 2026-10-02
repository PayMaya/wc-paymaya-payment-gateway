# Maya Business Plugin

This is the repository of the official Maya Business payment gateway plugin

## Give your customers a better online checkout experience

Installation guide can be found [here](https://s3-us-west-2.amazonaws.com/developers.paymaya.com.pg/plugins/WooCommerce+Plugin+Installation+Guide+2.0.pdf)

With Maya Checkout, your website or app can directly accept credit and debit cards, e-wallet, and other emerging payment solutions.
* Mastercard
* Visa
* JCB
* WeChat Pay
* Pay With Maya

### Features
* Payments via Maya Checkout
* Full 3DS Support and PCI-DSS Compliant
* Checkout page customizations
* Voids and Refunds
* Straight purchase or Authorization & Capture

### Don't have an account yet? [Click here to get started](https://enterprise.paymaya.com/solutions/plugins/woocommerce)

### Version Compatibility
This version (1.4.1) is currently compatible with the following WordPress and WooCommerce version:
* WordPress 7.1
* WooCommerce 11.0.1

### Webhooks behind a proxy or CDN
Maya only sends webhooks from its own IP addresses, so the plugin checks the sender's IP. The IP used is the connection address; client-settable headers like `X-Forwarded-For` are not trusted on their own.

**Symptom:** customers pay but orders stay "Pending payment", and the WooCommerce log (WooCommerce > Status > Logs) shows "Webhook has a valid Maya signature but came from IP ...". An error notice also appears in the WordPress admin.

**Cloudflare connecting directly to your server:** works automatically, nothing to do. If a load balancer sits between Cloudflare and your server, follow the steps below using the load balancer's IPs (the visitor's IP is then taken from Cloudflare's `CF-Connecting-IP` header).

**Any other proxy, CDN or load balancer:** go to WooCommerce > Settings > Payments > Maya and fill in the **Proxy / CDN** section:
1. **Trusted Proxy Header** - the header your proxy uses to pass on the visitor's IP (`X-Forwarded-For` for most load balancers, such as AWS ALB/ELB and nginx).
2. **Trusted Proxy IPs** - the IP addresses or ranges (CIDR) of your proxy or load balancer, one per line. The header is only trusted for requests coming from these addresses. If you leave this blank the header is ignored.

Saving the settings clears the admin notice. Developers can still override the resolved IP with the `cynder_paymaya_webhook_source_ip` filter.

### Running the tests
```
composer install
vendor/bin/phpunit
```
