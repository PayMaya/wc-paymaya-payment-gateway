<?php
/**
 * PHP version 7
 * 
 * Paymaya Payment Plugin
 * 
 * @category Plugin
 * @package  Paymaya
 * @author   Cyndertech <devops@cynder.io>
 * @license  n/a (http://127.0.0.0)
 * @link     n/a
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$fileDir = plugin_dir_path( __FILE__ );
include_once $fileDir.'/paymaya-client.php';
include_once $fileDir.'/cynder-paymaya-webhook-guard.php';

/** Error identifiers */
define('CYNDER_PAYMAYA_PROCESS_PAYMENT_BLOCK', 'Process Payment');
define('CYNDER_PAYMAYA_PROCESS_REFUND_BLOCK', 'Process Refund');
define('CYNDER_PAYMAYA_MASS_REFUND_PAYMENT_BLOCK', 'Mass Refund');
define('CYNDER_PAYMAYA_HANDLE_WEBHOOK_REQUEST_BLOCK', 'Handle Webhook Request');
define('CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK', 'Handle Payment Webhook Request');
define('CYNDER_PAYMAYA_ADD_ACTION_BUTTONS_BLOCK', 'Add Action Buttons');
define('CYNDER_PAYMAYA_AFTER_TOTALS_BLOCK', 'After Order Totals');
define('CYNDER_PAYMAYA_CREATE_CHECKOUT_EVENT', 'createCheckout');
define('CYNDER_PAYMAYA_VOID_PAYMENT_EVENT', 'voidPayment');
define('CYNDER_PAYMAYA_REFUND_PAYMENT_EVENT', 'refundPayment');
define('CYNDER_PAYMAYA_OVERRIDABLE_WEBHOOKS', array(
    'CHECKOUT_SUCCESS',
    'CHECKOUT_FAILURE',
    'PAYMENT_SUCCESS',
    'PAYMENT_FAILED',
    'PAYMENT_EXPIRED',
));

define('MAYA_WEBHOOK_PUBLIC_KEYS_SANDBOX', array(
    "-----BEGIN PUBLIC KEY-----\n" .
    "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjNkSX6p+goDPaPAYuTzT\n" .
    "zKTCBeLhh8FkPMbZxDKTUxF93dOwiC7jsdx7KyopupeLosiVlbs+gpAJ7XBQP/Ex\n" .
    "giyzXC9TljpyvkUQfyRPMAMKq+BzxdUliTl6hgrLBsH28CP5FuPHCsfxDXe7mDtv\n" .
    "9H4mP3SKO0HfkZ45tudxD9CWbwWKF0lU9LRbLlJ0y7KEaK7Rv9fI1Dp/KPT+9pls\n" .
    "tU+CPNKaxJjGRKGuxW2AOCabSD0cTZNXki+K51mNoma7Mj1HMhnsR68FGJvCqk1q\n" .
    "Wsr3q8+EUMVPBMX+5nKATfZYGvxg4ytzT8pnEVeWl6phYKviB9aVVwurh1gDJB4r\n" .
    "lQIDAQAB\n" .
    "-----END PUBLIC KEY-----",
    "-----BEGIN PUBLIC KEY-----\n" .
    "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAp14gezqq4dGWu7EZ7BHx\n" .
    "8wD3y1hqxwQR7UYPXtXJP+WngN4wqwatjsnQaRGnmdPRG8VEzUzw9PlR7t7P24uW\n" .
    "+J08xBrtTVouD2MKglcIcy13rt1XL79zr/LIAFMFI6f4O8/OQi1xsGsZ6xarD+wl\n" .
    "OQKG4W66I3yp2jNAbge25eSPuo0BNqPWvebMcIYJu4f3Fxu1eDgeM6zCEqLc6+jX\n" .
    "cNTP/zFHCvQaiIlLOqfgXDRPBcHPPZ2qcB99UVPAHXBKsKdtBB2w2qT2l99MlTAB\n" .
    "iRy+IKtVQcQyRP7T8blegO25x35G2CZ3VCKPkmUen3eXQ4+r5fVlzEIBSfNvBwT9\n" .
    "jQIDAQAB\n" .
    "-----END PUBLIC KEY-----",
));

define('MAYA_WEBHOOK_PUBLIC_KEYS_PRODUCTION', array(
    "-----BEGIN PUBLIC KEY-----\n" .
    "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjCGhkjg1PQe0WVHCYdTT\n" .
    "2luqzXhKfeStALWlEcMpHqYusd6dAU4vZ9bGQns/OYe/H2cIxEPvRJnRcipMKvVZ\n" .
    "pzAFEKHQLiXdeuNcxkAaxEZEwMAmFdVGmNLZbpi579r2s6Q++zYy0OHb9awY/2z0\n" .
    "OYRwV5XN7SCrqIlf1tEHfxKV2cJDCFW030nnRMoWisQ9KXG3Ihvjj4tOQimPCtzp\n" .
    "SDtlf6QFmg/WZBIOEdLro9oROztK6PwrI/yG5ZFaUCQYfY8fw0y1/PI3heEf8z5k\n" .
    "xA466LdSqCeVdGwfjKy9ZHown8XiiPI82HnBrMP3UPX4efEfopbP4SpDFOEwRNA9\n" .
    "FQIDAQAB\n" .
    "-----END PUBLIC KEY-----",
    "-----BEGIN PUBLIC KEY-----\n" .
    "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxZJxNmpNYjxFCBa2P6Ad\n" .
    "wzDDuDKOKAgiTBrQvJGuX/l2u32N4d4FYw99md16rf1iIcxD70/KG9nWrltrxbIs\n" .
    "bm9+bCHVLKMfdjaJQCBGXN/WW6W1XaGQQPft9UlmAwA/uMKTsN/2XqFjoKSJoe9e\n" .
    "Xz/p3pGn66oBTCwvzDqma46GxF92atiOt6CEcRl8P+dDKJlYY7fcxiuNMeDMOOla\n" .
    "KMxUz9nMgJ6uESK/kS8C8+hGuiCWgKeIRm/ONL5Gk/lypWzrphaKcWqpBGZxpNAL\n" .
    "AVmPY9ke4+RxyojkEre4d5sT2C21oAQVHyGewd0ttQ/bK59X17+yg5FOfRpI1BKj\n" .
    "7wIDAQAB\n" .
    "-----END PUBLIC KEY-----",
));

/**
 * Paymaya Class
 * 
 * @category Class
 * @package  Paymaya
 * @author   Cyndertech <devops@cynder.io>
 * @license  n/a (http://127.0.0.0)
 * @link     n/a
 */
class Cynder_Paymaya_Gateway extends WC_Payment_Gateway
{
    public $manual_capture;
    public $sandbox;
    public $secret_key;
    public $public_key;
    public $webhook_success;
    public $webhook_failure;
    public $debug_mode;
    public $client;
    private $rejection_reason = '';
    const GATEWAY_ID = 'paymaya';
    const WEBHOOK_LOOKUP_TIMEOUT = 3;
    const RECONCILE_HOOK = 'cynder_paymaya_reconcile_order';
    const RECONCILE_MAX_ATTEMPTS = 3;
    const SOURCE_REJECTED_OPTION = 'cynder_paymaya_source_rejected';
    private const TIMESTAMP_TOLERANCE_MS = 5 * 60 * 1000;

    /**
     * Starting point of the payment gateway
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->id = self::GATEWAY_ID;
        $this->has_fields = true;
        $this->method_title = 'Payments via Maya';
        $this->method_description = 'Secure online payments via Maya';

        $this->supports = array(
            'products',
            'refunds'
        );

        $this->initFormFields();

        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->manual_capture = $this->get_option('manual_capture');
        $this->sandbox = $this->get_option('sandbox');
        $this->secret_key = $this->get_option('secret_key');
        $this->public_key = $this->get_option('public_key');
        $this->webhook_success = $this->get_option('webhook_success');
        $this->webhook_failure = $this->get_option('webhook_failure');
        
        $debugMode = $this->get_option('debug_mode');
        $this->debug_mode = !empty($debugMode) && $debugMode === 'yes';

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array($this, 'process_admin_options')
        );

        add_action(
            'woocommerce_api_cynder_' . $this->id,
            array($this, 'handle_webhook_request')
        );

        add_action(
            'woocommerce_api_cynder_' . $this->id . '_payment',
            array($this, 'handle_payment_webhook_request')
        );

        add_action(
            'woocommerce_order_item_add_action_buttons',
            array($this, 'wc_order_item_add_action_buttons_callback'),
            10,
            1
        );

        add_action(
            'woocommerce_admin_order_totals_after_total',
            array($this, 'wc_captured_payments')
        );

        add_action(
            'woocommerce_admin_order_data_after_shipping_address',
            array($this, 'wc_paymaya_webhook_labels')
        );

        $this->client = new Cynder_PaymayaClient($this->sandbox === 'yes', $this->public_key, $this->secret_key);
    }

    /**
     * Payment Gateway Settings Page Fields
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    public function initFormFields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Maya Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'type'        => 'text',
                'title'       => 'Title',
                'description' => 'This controls the title that ' .
                                 'the user sees during checkout.',
                'default'     => 'Payments via Maya',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description that ' .
                                 'the user sees during checkout.',
                'default'     => 'Secure online payments via Maya',
            ),
            'manual_capture' => array(
                'title' => 'Manual Capture',
                'type' => 'select',
                'options' => array(
                    'none' => 'None',
                    'normal' => 'Normal',
                    'final' => 'Final',
                    'preauthorization' => 'Pre-authorization'
                ),
                'description' => 'To enable manual capture, select an authorization type. Setting the value to <strong>None</strong> disables manual capture.<br/><strong><em>Disabled by default.</em></strong>',
                'default' => 'none',
            ),
            'environment_title' => array(
                'title' => 'API Keys',
                'type' => 'title',
                'description' => 'API Keys are used to authenticate yourself to Maya checkout.<br/><strong>This plugin will not work without these keys</strong>.<br/>To obtain a set of keys, contact Maya directly.'
            ),
            'sandbox' => array(
                'title' => 'Sandbox Mode',
                'type' => 'checkbox',
                'description' => 'Enabled sandbox mode to test payment transactions with Maya.<br/>A set of test API keys and card numbers are available <a target="_blank" href="https://developers.maya.ph/docs/sandbox-credentials-and-cards-guide">here</a>.'
            ),
            'public_key' => array(
                'title'       => 'Public Key',
                'type'        => 'text',
            ),
            'secret_key' => array(
                'title'       => 'Secret Key',
                'type'        => 'text'
            ),
            'webhook_title' => array(
                'title' => 'Webhooks',
                'type' => 'title',
                'description' => 'The following fields are used by Maya to properly process order statuses after payments.<br/><strong>DON\'T CHANGE THIS UNLESS YOU KNOW WHAT YOU\'RE DOING</strong>.<br/>For more information, refer <a target="_blank" href="https://hackmd.io/@paymaya-pg/Checkout#Webhooks">here</a>.'
            ),
            'webhook_success' => array(
                'title' => 'Webhook Checkout Success URL',
                'type' => 'text',
                'default' => home_url( '/?wc-api=cynder_paymaya' )
            ),
            'webhook_failure' => array(
                'title' => 'Webhook Checkout Failure URL',
                'type' => 'text',
                'default' => home_url( '/?wc-api=cynder_paymaya' )
            ),
            'webhook_payment_status' => array(
                'title' => 'Webhook Payment Status URL',
                'type' => 'text',
                'default' => home_url( '/?wc-api=cynder_paymaya_payment' )
            ),
            'proxy_title' => array(
                'title' => 'Proxy / CDN',
                'type' => 'title',
                'description' => 'Maya only accepts webhooks from its own IP addresses, so the plugin must know the real IP of the sender.<br/>Cloudflare is detected automatically. <strong>Only fill this in if your site is behind another reverse proxy or load balancer</strong> and paid orders stay pending after payment. Leave blank otherwise.'
            ),
            'trusted_proxy_header' => array(
                'title' => 'Trusted Proxy Header',
                'type' => 'select',
                'options' => array(
                    '' => 'None (use the connection IP)',
                    'x-forwarded-for' => 'X-Forwarded-For',
                    'x-real-ip' => 'X-Real-IP',
                ),
                'default' => '',
                'description' => 'The header your proxy uses to pass on the original sender\'s IP. Ignored unless Trusted Proxy IPs is also set.',
                'desc_tip' => false,
            ),
            'trusted_proxy_ips' => array(
                'title' => 'Trusted Proxy IPs',
                'type' => 'textarea',
                'default' => '',
                'description' => 'IP addresses or ranges (CIDR) of your proxy or load balancer, one per line. The header above is only trusted for requests coming from these addresses, so nobody else can fake it. Ask your host if unsure.',
                'desc_tip' => false,
            ),
            'debug_mode' => array(
                'title' => 'Debug Mode',
                'type' => 'checkbox', 
                'description' => 'Enables debug mode. Produces more verbose logs for most of the plugin processes. Helpful when coordinating with customer support.',
                'default' => 'no',
            ),
            'require_billing_address_2' => array(
                'title' => 'Require Billing Address Line 2',
                'type' => 'checkbox',
                'description' => 'On certain cases, Maya may engage additional security checks using certain data. Enable this should they need your customers to fill out address line 2 during checkout.',
                'default' => 'no',
            ),
        );
    }

    function process_error($response) {
        if ($this->debug_mode) {
            wc_get_logger()->log('error', '[Registering Webhooks] ' . wc_print_r($response, true));
        }

        if (isset($response["error"]["error"])) {
            $this->add_error($response["error"]["error"]);
        } else if (isset($response["error"]["message"])) {
            $this->add_error($response["error"]["message"]);
        } else {
            $this->add_error($response["error"]);
        }
    }

    /**
     * Warn admins when Maya-signed webhooks were rejected only because of their source IP.
     * Registered once from the plugin bootstrap (gateways are instantiated lazily). Cleared when the gateway settings are saved.
     */
    public static function webhook_source_rejected_notice() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $rejected = get_option(self::SOURCE_REJECTED_OPTION);

        if (!is_array($rejected) || empty($rejected['time']) || $rejected['time'] < time() - WEEK_IN_SECONDS) {
            return;
        }

        $settingsUrl = admin_url('admin.php?page=wc-settings&tab=checkout&section=' . self::GATEWAY_ID);

        printf(
            '<div class="notice notice-error"><p><strong>Maya:</strong> a payment webhook was rejected on %s because it came from IP <code>%s</code>, which is not a Maya address. Paid orders may be stuck as pending. If your site is behind a proxy or load balancer, set <a href="%s">Trusted Proxy Header and Trusted Proxy IPs</a> in the Maya settings.</p></div>',
            esc_html(wp_date('Y-m-d H:i', (int) $rejected['time'])),
            esc_html(isset($rejected['ip']) ? (string) $rejected['ip'] : ''),
            esc_url($settingsUrl)
        );
    }

    /** Keep only valid IPs/CIDRs and tell the merchant about the rest, instead of silently ignoring them. */
    public function validate_trusted_proxy_ips_field($key, $value) {
        $value = is_null($value) ? '' : wp_unslash($value);
        $invalid = Cynder_Paymaya_Webhook_Guard::invalid_range_entries($value);

        if (!empty($invalid)) {
            $message = 'Maya: these Trusted Proxy IPs were not saved because they are not valid IP addresses or CIDR ranges, or are broader than /' . Cynder_Paymaya_Webhook_Guard::MIN_PREFIX_IPV4 . ' (IPv4) or /' . Cynder_Paymaya_Webhook_Guard::MIN_PREFIX_IPV6 . ' (IPv6): ' . implode(', ', $invalid);

            if (class_exists('WC_Admin_Settings')) {
                WC_Admin_Settings::add_error($message);
            } else {
                $this->add_error($message);
            }
        }

        return implode("\n", Cynder_Paymaya_Webhook_Guard::parse_ranges($value));
    }

    public function process_admin_options() {
        $is_options_saved = parent::process_admin_options();

        if ($is_options_saved) {
            delete_option(self::SOURCE_REJECTED_OPTION);
        }

        $webhookSuccessUrl = $this->get_option('webhook_success');
        $webhookFailureUrl = $this->get_option('webhook_failure');
        $webhookPaymentUrl = $this->get_option('webhook_payment_status');

        if (isset($this->enabled) && $this->enabled === 'yes' && isset($this->public_key) && isset($this->secret_key)) {
            $webhooks = $this->client->retrieveWebhooks();

            if (array_key_exists("error", $webhooks)) {
                $this->process_error($webhooks);
            } else {
                if ($this->debug_mode) {
                    wc_get_logger()->log('info', '[Registering Webhooks] ' . wc_print_r($webhooks, true));
                }

                wc_get_logger()->log('info', 'valid webhooks: ' . wc_print_r(CYNDER_PAYMAYA_OVERRIDABLE_WEBHOOKS, true));
    
                foreach($webhooks as $webhook) {
                    /**
                     * Only override webhook names that are being used by the plugin, disregard the rest
                     */
    
                    wc_get_logger()->log('info', 'Webhook name ' . $webhook['name']);
    
                    if (in_array($webhook['name'], CYNDER_PAYMAYA_OVERRIDABLE_WEBHOOKS)) {
                        $deletedWebhook = $this->client->deleteWebhook($webhook["id"]);
    
                        if (array_key_exists("error", $deletedWebhook)) {
                            $this->process_error($deletedWebhook);
                        }
                    }
                }
    
                $createdWebhook = $this->client->createWebhook('CHECKOUT_SUCCESS', $webhookSuccessUrl);
    
                if (array_key_exists("error", $createdWebhook)) {
                    $this->process_error($createdWebhook);
                }
    
                $createdWebhook = $this->client->createWebhook('CHECKOUT_FAILURE',$webhookFailureUrl);
    
                if (array_key_exists("error", $createdWebhook)) {
                    $this->process_error($createdWebhook);
                }
    
                $createdWebhook = $this->client->createWebhook('PAYMENT_SUCCESS', $webhookPaymentUrl);
    
                if (array_key_exists("error", $createdWebhook)) {
                    $this->process_error($createdWebhook);
                }
    
                $createdWebhook = $this->client->createWebhook('PAYMENT_FAILED', $webhookPaymentUrl);
    
                if (array_key_exists("error", $createdWebhook)) {
                    $this->process_error($createdWebhook);
                }
    
                $createdWebhook = $this->client->createWebhook('PAYMENT_EXPIRED', $webhookPaymentUrl);
    
                if (array_key_exists("error", $createdWebhook)) {
                    $this->process_error($createdWebhook);
                }
            }

            $this->display_errors();
        }

        return $is_options_saved;
    }

    public function process_payment($orderId) {
        $order = wc_get_order($orderId);
        
        $orderItemArray = [];

        $orderKey = $order->get_order_key();
        $catchRedirectUrl = home_url( '/?wc-api=cynder_paymaya_catch_redirect&order=' . $orderId . '&key=' . $orderKey );

        $shippingFirstName = $this->get_address_fallback($order, 'first_name');
        $shippingLastName  = $this->get_address_fallback($order, 'last_name');
        $shippingLine1     = $this->get_address_fallback($order, 'address_1');
        $shippingLine2     = $this->get_address_fallback($order, 'address_2');
        $shippingCity      = $this->get_address_fallback($order, 'city');
        $shippingZipCode   = $this->get_address_fallback($order, 'postcode');
        $shippingCountry   = $this->get_address_fallback($order, 'country');

        foreach ($order->get_items() as $orderItem) {
            // Use standard WooCommerce methods to safely get the unit price and line total
            $item_unit_price = $order->get_item_total($orderItem, false, false);
            $item_line_total = $order->get_line_total($orderItem, false, false);

            array_push($orderItemArray, array(
                "name" => $orderItem->get_name(),
                "description" => $orderItem->get_name(),
                "quantity" => $orderItem->get_quantity(),
                "code" => '001',
                "amount" => array(
                    "value" => floatval($item_unit_price)
                ),
                "totalAmount" => array(
                    "value" => floatval($item_line_total)
                )
            ));
        }
        
        $payload = array(
            "totalAmount" => array(
                "value" => floatval($order->get_total()),
                "currency" => $order->get_currency(),
                "details" => array(
                    "discount" => floatval($order->get_discount_total()),
                    "shippingFee" => floatval($order->get_shipping_total()),
                    "subtotal" => floatval($order->get_subtotal())
                )
            ),
            "buyer" => array(
                "firstName" => $order->get_billing_first_name(),
                "lastName" => $order->get_billing_last_name(),
                "contact" => array(
                    "phone" => $order->get_billing_phone(),
                    "email" => $order->get_billing_email()
                ),
                "shippingAddress" => array(
                    "firstName" => $shippingFirstName,
                    "lastName" => $shippingLastName,
                    "line1" => $shippingLine1,
                    "line2" => $shippingLine2,
                    "city" => $shippingCity,
                    "state" => $order->get_shipping_state(),
                    "zipCode" => $shippingZipCode,
                    "countryCode" => $shippingCountry,
                    "shippingType" => 'ST', // TODO: standard shipping is hard-coded for now
                    "phone" => $order->get_billing_phone(),
                    "email" => $order->get_billing_email()
                ),
                "billingAddress" => array(
                    "line1" => $order->get_billing_address_1(),
                    "line2" => $order->get_billing_address_2(),
                    "city" => $order->get_billing_city(),
                    "state" => $order->get_billing_state(),
                    "zipCode" => $order->get_billing_postcode(),
                    "countryCode" => $order->get_billing_country()
                )
            ),
            "items" => $orderItemArray,
            "redirectUrl" => array(
                "success" => $catchRedirectUrl . '&status=success',
                "failure" => $catchRedirectUrl . '&status=failed',
                "cancel" => $order->get_checkout_payment_url()
            ),
            "requestReferenceNumber" => strval($orderId)
        );

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_PROCESS_PAYMENT_BLOCK . '] Manual capture authorization type ' . $this->manual_capture);
        }

        if ($this->manual_capture !== "none") {
            $payload['authorizationType'] = strtoupper($this->manual_capture);
        };

        $encodedPayload = json_encode($payload);

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_PROCESS_PAYMENT_BLOCK . '] Payload' . wc_print_r($encodedPayload, true));
        }

        $response = $this->client->createCheckout($encodedPayload);

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_PROCESS_PAYMENT_BLOCK . '][' . CYNDER_PAYMAYA_CREATE_CHECKOUT_EVENT . '] Create Checkout Response ' . wc_print_r($response, true));
        }

        if (array_key_exists("error", $response)) {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_PROCESS_PAYMENT_BLOCK . '][' . CYNDER_PAYMAYA_CREATE_CHECKOUT_EVENT . '] ' . json_encode($response['error']));
            return null;
        }

        $existingCheckout = $order->get_meta($this->id . '_checkout_id');

        if (isset($existingCheckout) && $existingCheckout !== '') {
            $order->add_meta_data($this->id . '_checkout_id_old', $existingCheckout);
        }
        
        $order->update_meta_data($this->id . '_checkout_id', $response['checkoutId']);
        $order->add_meta_data($this->id . '_authorization_type', $this->manual_capture);
        $order->save_meta_data();

        return array(
            "result" => "success",
            "redirect" => $response["redirectUrl"]
        );
    }

    public function process_refund($orderId, $amount = NULL, $reason = '') {
        $order = wc_get_order($orderId);
        $payments = $this->client->getPaymentViaRrn($orderId);

        if (array_key_exists("error", $payments)) {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_PROCESS_REFUND_BLOCK . '][' . CYNDER_PAYMAYA_GET_PAYMENTS_EVENT . '] ' . $payments['error']);
            return false;
        }

        $amountValue = floatval($amount);

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_PROCESS_REFUND_BLOCK . '][' . CYNDER_PAYMAYA_GET_PAYMENTS_EVENT . '] Payments via RRN ' . wc_print_r($payments, true));
        }

        $authorizationType = $order->get_meta($this->id . '_authorization_type');

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_PROCESS_REFUND_BLOCK . '] Authorization Type: ' . $authorizationType);
        }

        if (empty($authorizationType) || $authorizationType === 'none') {
            $successfulPayments = array_values(
                array_filter(
                    $payments,
                    function ($payment) use ($orderId) {
                        if (empty($payment['receiptNumber']) || empty($payment['requestReferenceNumber'])) return false;
                        $success = $payment['status'] == 'PAYMENT_SUCCESS';
                        $refunded = $payment['status'] == 'REFUNDED';
                        $matchedRefNum = $payment['requestReferenceNumber'] == strval($orderId);
                        return ($success || $refunded) && $matchedRefNum;
                    }
                )
            );
        
            if (count($successfulPayments) === 0) return;
        
            $successfulPayment = $successfulPayments[0];
    
            if ($this->debug_mode) {
                wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_PROCESS_REFUND_BLOCK . '] Successful Payment ' . wc_print_r($successfulPayment, true));
            }
    
            if (!$successfulPayment) {
                return new WP_Error(404, 'Can\'t find payment record to refund in Paymaya');
            }
    
            $paymentId = $successfulPayment['id'];
    
            /** Only void if payment is voidable and full amount */
            if ($successfulPayment['canVoid']) {
                if ($amountValue === floatval($successfulPayment['amount'])) {

                    $response = $this->client->voidPayment($paymentId, empty($reason) ? 'Merchant manually voided' : $reason);

                    if (array_key_exists("error", $response)) {
                        wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_PROCESS_REFUND_BLOCK . '][' . CYNDER_PAYMAYA_VOID_PAYMENT_EVENT . '] ' . $response['error']);
                        return false;
                    }
            
                    return true;
                } else {
                    return new WP_Error(400, 'Partial voids are not allowed by the payment gateway');
                }
            }
    
            if ($successfulPayment['canRefund']) {
                $payload = json_encode(
                    array(
                        'totalAmount' => array(
                            'amount' => $amountValue,
                            'currency' => $successfulPayment['currency']
                        ),
                        'reason' => empty($reason) ? 'Merchant manually refunded' : $reason
                    )
                );
        
                $response = $this->client->refundPayment($paymentId, $payload);
        
                if (array_key_exists("error", $response)) {
                    wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_PROCESS_REFUND_BLOCK . '][' . CYNDER_PAYMAYA_REFUND_PAYMENT_EVENT . '] ' . $response['error']);
                    return false;
                }
        
                return true;
            }
        } else {
            if ($this->debug_mode) {
                wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_PROCESS_REFUND_BLOCK . '] Amount entered ' . $amountValue);
            }

            $authorizedPayments = array_values(
                array_filter(
                    $payments,
                    function ($payment) {
                        if (empty($payment['receiptNumber']) || empty($payment['requestReferenceNumber'])) return false;
                        return array_key_exists('authorizationType', $payment);
                    }
                )
            );

            /** If no authorized payment, return error */
            if (count($authorizedPayments) === 0) {
                return new WP_Error(400, 'No authorized payment to refund');
            }

            $authorizedPayment = $authorizedPayments[0];
            $authorizedFullAmount = floatval($authorizedPayment['amount']);

            /**
             * If there are no other payments other than the authorized payment,
             * assume there were no captures made yet.
             */
            if (count($payments) === 1) {
                $paymentId = $authorizedPayment['id'];
                $authorized = $authorizedPayment['status'] === 'AUTHORIZED';
                $canVoid = $authorizedPayment['canVoid'];

                if (!$canVoid) {
                    return new WP_Error(400, 'Authorized payment can no longer be voided');
                }
                
                if ($authorized && $authorizedFullAmount === $amountValue) {
                    $response = $this->client->voidPayment($paymentId, empty($reason) ? 'Merchant manually voided' : $reason);

                    if (array_key_exists("error", $response)) {
                        wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_PROCESS_REFUND_BLOCK . '][' . CYNDER_PAYMAYA_VOID_PAYMENT_EVENT . '] ' . $response['error']);
                        return false;
                    }

                    return true;
                } else {
                    return new WP_Error(400, 'Partial voids are not allowed by the payment gateway');
                }
            } else {
                $capturedPayments = array_values(
                    array_filter(
                        $payments,
                        function ($payment) {
                            if (empty($payment['receiptNumber']) || empty($payment['requestReferenceNumber'])) return false;
                            return array_key_exists('authorizationPayment', $payment);
                        }
                    )
                );

                $sorted = usort($capturedPayments, function ($a, $b) {
                    return strtotime($a['createdAt']) <=> strtotime($b['createdAt']);
                });

                // In PHP 8.2, usort always returns true which will make this check redundant.
                if (!$sorted) {
                    return new WP_Error(400, 'Something went wrong with refunding the captured payments');
                }

                $availableActions = array_reduce($capturedPayments, function ($actions, $capturedPayment) {
                    $paymentId = $capturedPayment['id'];
                    $paymentAmount = floatval($capturedPayment['amount']);
                    $paymentCurrency = $capturedPayment['currency'];

                    if ($capturedPayment['canVoid']) {
                        array_push(
                            $actions,
                            array(
                                'action' => 'void',
                                'paymentId' => $paymentId,
                                'amount' => $paymentAmount,
                                'currency' => $paymentCurrency,
                            )
                        );
                    } else if ($capturedPayment['canRefund']) {
                        $refunds = $this->client->getRefunds($paymentId);
                        $amountToRefund = $paymentAmount;

                        if (count($refunds) > 0) {
                            $amountToRefund = array_reduce($refunds, function ($balance, $refund) {
                                if ($refund['status'] !== 'SUCCESS') return $balance;
                                if ($balance == 0) return 0;

                                return $balance - floatval($refund['amount']);
                            }, $amountToRefund);
                        }

                        if ($amountToRefund != 0) {
                            array_push(
                                $actions,
                                array(
                                    'action' => 'refund',
                                    'paymentId' => $paymentId,
                                    'amount' => $amountToRefund,
                                    'currency' => $paymentCurrency
                                )
                            );
                        }
                    }

                    return $actions;
                }, []);

                if ($this->debug_mode) {
                    wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_PROCESS_REFUND_BLOCK . '] Available Actions ' . wc_print_r($availableActions, true));
                }

                $actionsToProcess = array();

                while ($amountValue > 0 && count($availableActions) > 0) {
                    $availableAction = array_shift($availableActions);
                    $actionType = $availableAction['action'];
                    $actionAmount = floatval($availableAction['amount']);

                    if ($actionType === 'void' && $actionAmount <= $amountValue) {
                        array_push($actionsToProcess, $availableAction);
                        $amountValue = $amountValue - $actionAmount;
                    } else if ($actionType === 'void' && $amountValue != 0) {
                        return new WP_Error(400, 'Partial voids are not allowed by the payment gateway');
                    } else if ($actionType === 'refund' && $amountValue != 0) {
                        $amountToRefund = $actionAmount;

                        if ($amountValue >= $actionAmount) {
                            $amountValue = $amountValue - $actionAmount;
                        } else {
                            $amountToRefund = $amountValue;
                            $amountValue = 0;
                        }

                        $availableAction['amount'] = $amountToRefund;

                        array_push($actionsToProcess, $availableAction);
                    }
                }

                if ($this->debug_mode) {
                    wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_PROCESS_REFUND_BLOCK . '] Actions to process ' . wc_print_r($actionsToProcess, true));
                }

                if ($amountValue > 0) {
                    return new WP_Error(400, 'Insufficient captured amount to cover the requested refund.');
                }


                return $this->do_mass_refund($actionsToProcess, $reason);
            }
        }

        return new WP_Error(400, 'Payment cannot be refunded');
    }

    function do_mass_refund($actions, $reason) {
        foreach ($actions as $action) {
            $actionType = $action['action'];
            $defaultReason = 'Merchant manually '  . ($actionType === 'void' ? 'voided' : 'refunded');
            $finalReason = empty($reason) ? $defaultReason : $reason;

            $params = array(
                $action['paymentId'],
            );

            if ($actionType === 'refund') {
                $payload = json_encode(
                    array(
                        'totalAmount' => array(
                            'amount' => $action['amount'],
                            'currency' => $action['currency'],
                        ),
                        'reason' => $finalReason
                    )
                );

                array_push($params, $payload);
            } else {
                array_push($params, $finalReason);
            }

            $functionKey = $actionType === 'void' ? 'voidPayment' : 'refundPayment';

            $response = $this->client->$functionKey(...$params);

            if (array_key_exists("error", $response)) {
                $errorIdentifier = $actionType === 'void' ? CYNDER_PAYMAYA_VOID_PAYMENT_EVENT : CYNDER_PAYMAYA_REFUND_PAYMENT_EVENT;
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_MASS_REFUND_PAYMENT_BLOCK . '][' . $errorIdentifier . '] ' . $response['error']);
                return new WP_Error(400, 'Something went wrong with the refund. Check your Maya merchant dashboard for actual balances.');
            }
        }

        return true;
    }

    public function handle_webhook_request() {
        /** Passthrough */
        status_header(200);
        die();
    }

    function get_source() {
        /**
         * Only the TCP peer is trusted, plus CF-Connecting-IP when the peer is a Cloudflare edge, plus the
         * merchant's configured proxy header when the peer is one of their trusted proxies.
         * Developers can still override the resolved IP with this filter, provided the origin
         * only accepts traffic from the proxy it trusts.
         */
        $ip = Cynder_Paymaya_Webhook_Guard::resolve_source_ip(
            $_SERVER,
            (string) $this->get_option('trusted_proxy_header'),
            Cynder_Paymaya_Webhook_Guard::parse_ranges($this->get_option('trusted_proxy_ips'))
        );

        return (string) apply_filters('cynder_paymaya_webhook_source_ip', $ip);
    }

    function is_valid_source($source) {
        $this->rejection_reason = '';

        $serverTimestamp = $_SERVER['HTTP_X_MAYA_WEBHOOK_TIMESTAMP'] ?? '';
        $envTimestamp = getenv('HTTP_X_MAYA_WEBHOOK_TIMESTAMP');

        $webhookTimestamp = !empty($serverTimestamp) ? $serverTimestamp : ($envTimestamp !== false ? $envTimestamp : '');
        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Webhook Timestamp ' . $webhookTimestamp);
        }

        if (!$this->verify_timestamp($webhookTimestamp)) {
            /** Exit early if validation fails */
            $this->rejection_reason = 'timestamp';
            return false;
        }

        $serverSig = $_SERVER['HTTP_X_MAYA_WEBHOOK_SIGNATURE'] ?? '';
        $envSig = getenv('HTTP_X_MAYA_WEBHOOK_SIGNATURE');

        $webhookSignature = !empty($serverSig) ? $serverSig : ($envSig !== false ? $envSig : '');
        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Webhook Signature ' . $webhookSignature);
        }

        $webhookSignatureArray = explode(',', $webhookSignature);
        
        $webhookNonce = null;
        $webhookV1 = null;

        foreach ($webhookSignatureArray as $webhookSignatureItem) {
            if (strpos($webhookSignatureItem, "nonce=") === 0) {
                $webhookNonce = substr($webhookSignatureItem, strlen("nonce="));
            } elseif (strpos($webhookSignatureItem, "v1=") === 0) {
                $webhookV1 = substr($webhookSignatureItem, strlen("v1="));
            }
        }

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Webhook nonce: ' . $webhookNonce);
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Webhook V1: ' . $webhookV1);
        }
        
        if ($webhookNonce === null || $webhookV1 === null) {
            $this->rejection_reason = 'signature';
            if ($this->debug_mode) {
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Webhook signatures not found');
            }
            return false;
        }
        
        
        $requestBody = file_get_contents('php://input');
        $payment = json_decode($requestBody, true);

        if (!is_array($payment)) {
            $this->rejection_reason = 'payload';
            return false;
        }

        if (!$this->verify_signature_v1($payment, $webhookV1, $webhookNonce)) {
            $this->rejection_reason = 'signature';
            if ($this->debug_mode) {
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Webhook signature mismatch');
            }
            return false;
        }

        $mayaIps = $this->sandbox === 'yes'
            ? array('13.229.160.234', '3.1.199.75')
            : array('18.138.50.235', '3.1.207.200');

        if (!in_array($source, $mayaIps, true)) {
            /** Reached only after the signature verified, so Maya signed this request but it came from an unexpected IP. */
            $this->rejection_reason = 'source_ip';
            return false;
        }

        return true;
    }

    function handle_payment_webhook_request() {
        $isPostRequest = $_SERVER['REQUEST_METHOD'] === 'POST';
        $wcApiQuery = isset($_GET['wc-api']) ? sanitize_text_field($_GET['wc-api']) : null;
        $hasWcApiQuery = isset($wcApiQuery);
        $hasCorrectQuery = $wcApiQuery === 'cynder_paymaya_payment';
        $source = $this->get_source();
        $isValidSource = $this->is_valid_source($source);

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Webhook request received from ' . $source);

            if ($isValidSource) {
                wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Source is valid');
            } else {
                wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Source is invalid');
            }
        }

        if (!$isValidSource && $this->rejection_reason === 'source_ip') {
            /** Logged regardless of debug mode: this is almost always a proxy/CDN misconfiguration. */
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Webhook has a valid Maya signature but came from IP ' . $source . ', which is not a Maya IP. If your site is behind a proxy or load balancer, set Trusted Proxy Header and Trusted Proxy IPs in the Maya gateway settings.');
            update_option(self::SOURCE_REJECTED_OPTION, array('ip' => $source, 'time' => time()), false);
        }

        if (!$isValidSource || !$isPostRequest || !$hasWcApiQuery || !$hasCorrectQuery) {
            status_header(400);
            die();
        }

        $requestBody = file_get_contents('php://input');
        $payment = json_decode($requestBody, true);

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Payment Webhook payload ' . wc_print_r($payment, true));
        }

        if (!Cynder_Paymaya_Webhook_Guard::is_valid_webhook_payload($payment)) {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Malformed webhook payload');

            status_header(400);
            die();
        }

        $referenceNumber = $payment['requestReferenceNumber'];

        $order = wc_get_order($referenceNumber);

        if (empty($order)) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] No transaction found with reference number '. $referenceNumber);

            status_header(204);
            die();
        }

        $authorizationType = $order->get_meta($this->id . '_authorization_type');

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Authorization Type: ' . $authorizationType);
        }

        $transactionRefNumber = $payment['id'];
        $status = $payment['status'];
        $amountPaid = $payment['amount'];

        if (empty($authorizationType) || $authorizationType === 'none') {
            /** For non-manual capture payments: */

            if ($order->is_paid()) {
                wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Order ' . $referenceNumber . ' is already paid. Cannot process payment ' . $transactionRefNumber . ' with ' . $status . ' status.');

                status_header(204);
                die();
            }

            /** With correct data based on assumptions */
            if (Cynder_Paymaya_Webhook_Guard::amounts_equal($amountPaid, $order->get_total()) && $status === 'PAYMENT_SUCCESS') {
                /** Never rely on the webhook body alone: confirm the payment with Maya before releasing the order. */
                $confirmedPayment = $this->confirm_successful_payment($order, $referenceNumber);

                if ($confirmedPayment === null) {
                    $this->schedule_reconcile($order);
                    $this->add_order_note_once($order, 'lookup-failed-' . $transactionRefNumber, 'Maya payment webhook ' . $transactionRefNumber . ' received but the payment could not be confirmed with Maya (lookup failed). Waiting for Maya to retry; check the payment on the Maya dashboard if this order stays pending.');
                    status_header(503);
                    die();
                }

                if ($confirmedPayment === false) {
                    wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Webhook claimed success for order ' . $referenceNumber . ' but Maya has no matching successful payment. Order left unchanged.');
                    $this->add_order_note_once($order, 'no-match-' . $transactionRefNumber, 'Maya payment webhook ' . $transactionRefNumber . ' reported success but no matching successful payment (reference, amount, currency) was found at Maya. Order left unchanged; check the payment on the Maya dashboard.');
                    $this->schedule_reconcile($order);
                    /** Retryable: Maya's payment records can lag behind the webhook. Maya retries on non-2xx, up to 4 attempts over ~1h; a 2xx would end retries (https://developers.maya.ph/reference/configuring-your-webhook-for-maya-checkout). */
                    status_header(503);
                    die();
                }

                $order->payment_complete($confirmedPayment['id']);
            } else if ($status === 'PAYMENT_FAILED' || $status === 'PAYMENT_EXPIRED' || $status === 'AUTH_FAILED') {
                $note = '';

                switch ($status) {
                    case 'PAYMENT_EXPIRED': {
                        $note = 'Payment expired';
                        break;
                    }
                    case 'AUTH_FAILED':
                    case 'PAYMENT_FAILED':
                    default: {
                        $note = 'Payment failed';
                    }
                }

                $order->update_status('failed', $note, true);
            } else {
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Amount mismatch. Open payment details on Maya dashboard with txn ref number ' . $transactionRefNumber);
            }
        } else {
            /** Process manual captures */

            /** A failure webhook must never change an order that is already paid. */
            if ($order->is_paid() && in_array($status, array('PAYMENT_EXPIRED', 'AUTH_FAILED', 'PAYMENT_FAILED'), true)) {
                wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Order ' . $referenceNumber . ' is already paid. Ignoring ' . $status . ' status for payment ' . $transactionRefNumber);
                $order->add_order_note('Ignored failed payment ' . $transactionRefNumber . ' (' . $status . '): order is already paid');

                status_header(204);
                die();
            }

            $payments = $this->client->getPaymentViaRrn($referenceNumber, self::WEBHOOK_LOOKUP_TIMEOUT);

            if ($this->debug_mode) {
                wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Payments via RRN ' . wc_print_r($payments, true));
            }

            list($outcome, $authorizedPayment) = Cynder_Paymaya_Webhook_Guard::classify_manual_capture($payments, floatval($order->get_total()), $order->get_currency(), $referenceNumber, $status);

            if ($outcome === 'unreachable') {
                /** Retryable: Maya could not be reached or errored, so nothing was confirmed either way. */
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] ' . (is_array($payments) && is_string($payments['error'] ?? null) ? $payments['error'] : 'Could not look up payments for order ' . $referenceNumber));
                $this->schedule_reconcile($order);
                status_header(503);
                die();
            }

            if ($outcome === 'none') {
                /** For a signed success webhook "no record yet" may just be lag at Maya, so ask for a retry (non-2xx); otherwise it is final. */
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] No authorized or captured payments associated to order ID ' . $referenceNumber);

                if ($status === 'PAYMENT_SUCCESS') {
                    $this->schedule_reconcile($order);
                    status_header(503);
                } else {
                    status_header(204);
                }

                die();
            }

            if ($outcome === 'ambiguous') {
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Multiple confirmed captured payments associated to order ID ' . $referenceNumber . '. Order left unchanged.');
                $this->add_order_note_once($order, 'ambiguous-' . $transactionRefNumber, 'Maya payment webhook ' . $transactionRefNumber . ' reported success but several captured payments match this order. Order left unchanged; check the payments on the Maya dashboard.');
                status_header(204);
                die();
            }

            if ($outcome === 'mismatch') {
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Webhook claimed success for order ' . $referenceNumber . ' but Maya\'s payment does not match the order reference, total or currency. Order left unchanged.');
                $this->add_order_note_once($order, 'mismatch-' . $transactionRefNumber, 'Maya payment webhook ' . $transactionRefNumber . ' reported success but Maya\'s payment record does not match the order reference, total or currency. Order left unchanged; check the payment on the Maya dashboard.');
                /** Final: a fully captured record with the wrong reference, total, currency or status will not start matching on retry. */
                status_header(204);
                die();
            }

            if ($outcome === 'complete') {
                if ($order->is_paid()) {
                    $order->update_status('processing');
                } else {
                    $order->payment_complete($authorizedPayment['id']);
                }
            } else {
                $note = '';

                switch ($status) {
                    case 'PAYMENT_SUCCESS': {
                        $note = 'Successful payment ' . $payment['id'];
                        break;
                    }
                    case 'PAYMENT_EXPIRED':
                    case 'AUTH_FAILED':
                    case 'PAYMENT_FAILED': {
                        $note = 'Failed payment ' . $payment['id'];

                        $order->update_status('on-hold');
                        break;
                    }
                }

                if (!empty($note)) {
                    $order->add_order_note($note);
                }
            }
        }

        wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Webhook processing done for payment ' . $payment['id']);
    }

    function wc_order_item_add_action_buttons_callback($order) {
        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_ADD_ACTION_BUTTONS_BLOCK . '] Total refunded for order ID ' . $order->get_id() . ': ' . $order->get_total_refunded());
        }
        $orderId = $order->get_id();
        $payments = $this->client->getPaymentViaRrn($orderId);

        if (array_key_exists("error", $payments)) {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_ADD_ACTION_BUTTONS_BLOCK . '][' . CYNDER_PAYMAYA_GET_PAYMENTS_EVENT . '] ' . $payments['error']);
            return;
        }

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_ADD_ACTION_BUTTONS_BLOCK . '] Payments via RRN ' . wc_print_r($payments, true));
        }
    
        $successfulPayments = array_values(
            array_filter(
                $payments,
                function ($payment) use ($orderId) {
                    if (empty($payment['receiptNumber']) || empty($payment['requestReferenceNumber'])) return false;
                    $success = $payment['status'] == 'PAYMENT_SUCCESS';
                    $matchedRefNum = $payment['requestReferenceNumber'] == strval($orderId);
                    return $success && $matchedRefNum;
                }
            )
        );
    
        if (count($successfulPayments) !== 0) {
            $successfulPayment = $successfulPayments[0];
        
            if ($this->debug_mode) {
                wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_ADD_ACTION_BUTTONS_BLOCK . '] Payment ID ' . $successfulPayment['id'] . ' canRefund: ' . ($successfulPayment['canRefund'] == true ? 'true' : 'false'));
                wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_ADD_ACTION_BUTTONS_BLOCK . '] Payment ID ' . $successfulPayment['id'] . ' canVoid: ' . ($successfulPayment['canVoid'] == true ? 'true' : 'false'));
            }
        
            if ($successfulPayment['canVoid']) {
                echo '<span style="color: blue; text-decoration: underline;" class="tips" data-tip="Refunding the full amount for this order voids the payments for this transaction">Voidable</span>';
            }
        }

        $authorizationType = $order->get_meta($this->id . '_authorization_type');

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_ADD_ACTION_BUTTONS_BLOCK . '] Authorization Type: ' . $authorizationType);
        }

        if (empty($authorizationType) || $authorizationType === 'none') return;

        $authorizedPayments = array_values(
            array_filter(
                $payments,
                function ($payment) use ($orderId) {
                    if (empty($payment['receiptNumber']) || empty($payment['requestReferenceNumber'])) return false;
                    $authorizationPayment = array_key_exists('authorizationType', $payment);
                    $canCapture = $payment['canCapture'] == true;
                    $matchedRefNum = $payment['requestReferenceNumber'] == strval($orderId);
                    return $authorizationPayment && $canCapture && $matchedRefNum;
                }
            )
        );

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_ADD_ACTION_BUTTONS_BLOCK . '] Authorized payments ' . wc_print_r($authorizedPayments, true));
        }

        if (count($authorizedPayments) !== 0) {
            echo '<button type="button" class="button capture-items">Capture</button>';
        }
    }

    function wc_captured_payments($orderId) {
        $order = wc_get_order($orderId);

        $authorizationType = $order->get_meta($this->id . '_authorization_type');

        if (empty($authorizationType) || $authorizationType === 'none') return;

        $payments = $this->client->getPaymentViaRrn($orderId);

        if (array_key_exists("error", $payments)) {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_AFTER_TOTALS_BLOCK . '][' . CYNDER_PAYMAYA_GET_PAYMENTS_EVENT . '] ' . $payments['error']);
            return;
        }
    
        if (count($payments) === 0) {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_AFTER_TOTALS_BLOCK . '] No payments associated to order ID ' . $orderId);
            return;
        }

        $authorizedOrCapturedPayments = array_values(
            array_filter(
                $payments,
                function ($payment) {
                    if (empty($payment['receiptNumber']) || empty($payment['requestReferenceNumber'])) return false;
                    $authorized = $payment['status'] == 'AUTHORIZED';
                    $captured = $payment['status'] == 'CAPTURED';
                    $done = $payment['status'] == 'DONE';
                    return $authorized || $captured || $done;
                }
            )
        );

        if (count($authorizedOrCapturedPayments) === 0) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_AFTER_TOTALS_BLOCK . '] No captured payments associated to order ID ' . $orderId);
            return;
        }
    
        if (count($authorizedOrCapturedPayments) > 2) {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_AFTER_TOTALS_BLOCK . '] Multiple captured payments associated to order ID ' . $orderId);
            return;
        }

        $authorizedOrCapturedPayment = $authorizedOrCapturedPayments[0];
        $authorizedAmount = $authorizedOrCapturedPayment['amount'];
        $capturedAmount = $authorizedOrCapturedPayment['capturedAmount'];
        $balance = floatval($authorizedAmount) - floatval($capturedAmount);

        $pluginPath = plugin_dir_path(CYNDER_PAYMAYA_MAIN_FILE);

        include $pluginPath . '/views/manual-capture.php';
    }

    function wc_paymaya_webhook_labels($order) {
        $authorizationType = $order->get_meta($this->id . '_authorization_type');

        if (empty($authorizationType) || $authorizationType === 'none') return;

        echo '<h4>Maya Payment Processing Notice</h4><em>On capture completion of the total amount, expect delays on payment processing. Refresh page to check if payments have been processed and order status has been updated.</em>';
    }
    
    function flatten_object_to_string($obj, $prefix = '', &$data = []) {
        if (!is_array($obj)) {
            throw new InvalidArgumentException('Input must be an array');
        }

        foreach ($obj as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;

            if (
                $value === null ||
                $value === '' ||
                $value === [] ||
                (is_array($value) && empty($value)) ||
                (is_object($value) && empty((array) $value))
            ) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $this->flatten_object_to_string((array) $value, $fullKey, $data);
                continue;
            }

            if (is_bool($value)) {
                $data[] = $fullKey . '=' . ($value ? 'true' : 'false');
            } else {
                $data[] = $fullKey . '=' . (string) $value;
            }
        }

        return $data;
    }
    

    /** Add an order note once per $key (e.g. per webhook id), so Maya's retries do not repeat it. */
    function add_order_note_once($order, $key, $note) {
        $metaKey = '_cynder_paymaya_note_' . md5($key);

        if ($order->get_meta($metaKey) !== '') {
            return;
        }

        $order->add_order_note($note);
        $order->update_meta_data($metaKey, '1');
        $order->save_meta_data();
    }

    /**
     * Whether a payment record from Maya belongs to this order, has an acceptable status and matches the order total.
     * Every path that calls payment_complete() must pass this, so a webhook body alone never completes an order.
     */
    function is_payment_confirmed($maya, $order, $referenceNumber, array $allowedStatuses) {
        return Cynder_Paymaya_Webhook_Guard::is_payment_confirmed($maya, floatval($order->get_total()), $order->get_currency(), $referenceNumber, $allowedStatuses);
    }

    /**
     * Ask Maya for the order's payments and find a successful one matching the order reference and total.
     *
     * @return array|false|null The matching Maya payment, false if none matches, null if Maya could not be reached.
     */
    function confirm_successful_payment($order, $referenceNumber) {
        $payments = $this->client->getPaymentViaRrn($referenceNumber, self::WEBHOOK_LOOKUP_TIMEOUT);

        list($outcome, $matched) = Cynder_Paymaya_Webhook_Guard::classify_lookup($payments, floatval($order->get_total()), $order->get_currency(), $referenceNumber, array('PAYMENT_SUCCESS'));

        if ($outcome === 'unreachable') {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Could not confirm payment with Maya for order ' . $referenceNumber);
            return null;
        }

        return $outcome === 'match' ? $matched : false;
    }

    /**
     * Maya retries a webhook only 4 times over about an hour. If we still could not confirm a payment by then,
     * re-check once more later (Action Scheduler ships with WooCommerce) so a paid order is not left pending.
     */
    function schedule_reconcile($order, $attempt = 1) {
        if (!function_exists('as_schedule_single_action') || !function_exists('as_has_scheduled_action')) {
            return;
        }

        $args = array($order->get_id(), $attempt);

        if (!as_has_scheduled_action(self::RECONCILE_HOOK, $args)) {
            as_schedule_single_action(time() + 2 * HOUR_IN_SECONDS, self::RECONCILE_HOOK, $args);
        }
    }

    /** Action Scheduler callback: confirm a still-unpaid order with Maya and complete it on a match. */
    public function reconcile_order($orderId, $attempt = 1) {
        $order = wc_get_order($orderId);

        if (!$order || $order->get_payment_method() !== $this->id || !$order->has_status(array('pending', 'on-hold'))) {
            return;
        }

        $referenceNumber = strval($order->get_id());
        $authorizationType = $order->get_meta($this->id . '_authorization_type');
        $payments = $this->client->getPaymentViaRrn($referenceNumber, self::WEBHOOK_LOOKUP_TIMEOUT);
        $matched = null;

        if (empty($authorizationType) || $authorizationType === 'none') {
            list($outcome, $matched) = Cynder_Paymaya_Webhook_Guard::classify_lookup($payments, floatval($order->get_total()), $order->get_currency(), $referenceNumber, array('PAYMENT_SUCCESS'));
            $outcome = $outcome === 'match' ? 'complete' : $outcome;
        } else {
            list($outcome, $matched) = Cynder_Paymaya_Webhook_Guard::classify_manual_capture($payments, floatval($order->get_total()), $order->get_currency(), $referenceNumber, 'PAYMENT_SUCCESS');
        }

        if ($outcome === 'complete') {
            $order->payment_complete($matched['id']);
            $order->add_order_note('Payment ' . $matched['id'] . ' confirmed with Maya by a scheduled re-check after the webhook could not be confirmed.');
            return;
        }

        if ($outcome === 'unreachable' && $attempt < self::RECONCILE_MAX_ATTEMPTS) {
            $this->schedule_reconcile($order, $attempt + 1);
            return;
        }

        $order->add_order_note('Scheduled re-check with Maya found no confirmed payment for this order (' . $outcome . '). If the customer was charged, check the payment on the Maya dashboard.');
    }

    function verify_signature_v1($payload, $signature, $nonce) {
        $flatString = $this->flatten_object_to_string($payload);
        sort($flatString);
        $concatenatedFlatString = implode('&', $flatString);
        
        $verifyString = "{$concatenatedFlatString}&nonce={$nonce}";

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Flattened Payload: '. $verifyString);
        }
        
        if ($this->sandbox === 'yes') {
            if ($this->debug_mode) {
                wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Using sandbox public keys');
            }
            $publicKeys = MAYA_WEBHOOK_PUBLIC_KEYS_SANDBOX;
        } else {
            if ($this->debug_mode) {
                wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Using production public keys');
            }
            $publicKeys = MAYA_WEBHOOK_PUBLIC_KEYS_PRODUCTION;
        }

        return Cynder_Paymaya_Webhook_Guard::is_valid_signature($verifyString, $signature, $publicKeys);
    }
    
    function verify_timestamp($timestamp) {
        $currentTime = floor(microtime(true) * 1000);

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Timestamp: '. $timestamp);
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Current Time: '. $currentTime);
        }

        $timeDifference = abs((int) $currentTime - (int) $timestamp);

        if ($this->debug_mode) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Time Difference: '. $timeDifference);
        }
            
        if ($timeDifference > self::TIMESTAMP_TOLERANCE_MS) {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_PAYMENT_WEBHOOK_REQUEST_BLOCK . '] Webhook timestamp outside tolerance window (diff: ' . $timeDifference . 'ms, max: ' . self::TIMESTAMP_TOLERANCE_MS . 'ms)');
            return false;
        }
        
        return true;
    }

    private function get_address_fallback($order, $field_suffix) {
        $shipping_method = "get_shipping_{$field_suffix}";
        $billing_method = "get_billing_{$field_suffix}";
        
        $value = $order->$shipping_method();
        
        return empty($value) ? $order->$billing_method() : $value;
    }
}
