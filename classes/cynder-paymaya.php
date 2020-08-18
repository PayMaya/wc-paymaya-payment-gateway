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
    /**
     * Singleton instance
     * 
     * @var Singleton The reference the *Singleton* instance of this class
     */
    private static $_instance;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === self::$_instance ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Starting point of the payment gateway
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->id = 'paymaya';
        $this->has_fields = true;
        $this->method_title = 'Payments via Paymaya';
        $this->method_description = 'Secure online payments via Paymaya';

        $this->supports = array(
            'products',
            'refunds'
        );

        $this->initFormFields();

        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->sandbox = $this->get_option('sandbox');
        $this->secret_key = $this->get_option('secret_key');
        $this->public_key = $this->get_option('public_key');
        $this->webhook_success = $this->get_option('webhook_success');
        $this->webhook_failure = $this->get_option('webhook_failure');

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array($this, 'process_admin_options')
        );

        add_action(
            'woocommerce_api_cynder_' . $this->id,
            array($this, 'handle_webhook_request')
        );

        add_action(
            'woocommerce_order_item_add_action_buttons',
            array($this, 'wc_order_item_add_action_buttons_callback'),
            10,
            1
        );

        $fileDir = dirname(__FILE__);
        include_once $fileDir.'/paymaya-client.php';

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
                'label'       => 'Enable Paymaya Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'type'        => 'text',
                'title'       => 'Title',
                'description' => 'This controls the title that ' .
                                 'the user sees during checkout.',
                'default'     => 'Payments via Paymaya',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description that ' .
                                 'the user sees during checkout.',
                'default'     => 'Secure online payments via Paymaya',
            ),
            'environment_title' => array(
                'title' => 'API Keys',
                'type' => 'title',
                'description' => 'API Keys are used to authenticate yourself to PayMaya checkout.<br/><strong>This plugin will not work without these keys</strong>.<br/>To obtain a set of keys, contact PayMaya directly.'
            ),
            'sandbox' => array(
                'title' => 'Sandbox Mode',
                'type' => 'checkbox',
                'description' => 'Enabled sandbox mode to test payment transactions with PayMaya.<br/>A set of test API keys and card numbers are available <a target="_blank" href="https://hackmd.io/@paymaya-pg/Checkout#Sandbox-Test-Credentials">here</a>.'
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
                'description' => 'The following fields are used by PayMongo to properly process order statuses after payments.<br/><strong>DON\'T CHANGE THIS UNLESS YOU KNOW WHAT YOU\'RE DOING</strong>.<br/>For more information, refer <a target="_blank" href="https://hackmd.io/@paymaya-pg/Checkout#Webhooks">here</a>.'
            ),
            'webhook_success' => array(
                'title' => 'Webhook Success URL',
                'type' => 'text',
                'default' => get_home_url() . '?wc-api=cynder_paymaya'
            ),
            'webhook_failure' => array(
                'title' => 'Webhook Failure URL',
                'type' => 'text',
                'default' => get_home_url() . '?wc-api=cynder_paymaya'
            )
        );
    }

    public function process_admin_options() {
        global $woocommerce;

        $is_options_saved = parent::process_admin_options();

        if (isset($this->enabled) && $this->enabled === 'yes' && isset($this->public_key) && isset($this->secret_key)) {
            $webhooks = $this->client->retrieveWebhooks();

            if (array_key_exists("error", $webhooks)) {
                $this->add_error($webhooks["error"]);
            }

            foreach($webhooks as $webhook) {
                $deletedWebhook = $this->client->deleteWebhook($webhook["id"]);

                if (array_key_exists("error", $deletedWebhook)) {
                    $this->add_error($deletedWebhook["error"]);
                }
            }

            $createdWebhook = $this->client->createWebhook('CHECKOUT_SUCCESS', $this->webhook_success);
            $createdWebhook = $this->client->createWebhook('CHECKOUT_FAILURE', $this->webhook_failure);

            if (array_key_exists("error", $createdWebhook)) {
                $this->add_error($createdWebhook["error"]);
            }

            $this->display_errors();
        }

        return $is_options_saved;
    }

    public function process_payment($orderId) {
        $order = wc_get_order($orderId);

        function getItemPayload($items, $item) {
            $product = $item->get_product();

            array_push(
                $items,
                array(
                    "name" => $item->get_name(),
                    "quantity" => $item->get_quantity(),
                    "code" => strval($item->get_product_id()),
                    "amount" => array(
                        "value" => $product->get_price()
                    ),
                    "totalAmount" => array(
                        "value" => $item->get_subtotal()
                    )
                )
            );

            return $items;
        }

        $payload = json_encode(
            array(
                "totalAmount" => array(
                    "value" => intval($order->get_total()),
                    "currency" => $order->get_currency(),
                ),
                "buyer" => array(
                    "firstName" => $order->get_billing_first_name(),
                    "lastName" => $order->get_billing_last_name(),
                    "contact" => array(
                        "phone" => $order->get_billing_phone(),
                        "email" => $order->get_billing_email()
                    ),
                    "billing_address" => array(
                        "line1" => $order->get_billing_address_1(),
                        "line2" => $order->get_billing_address_2(),
                        "city" => $order->get_billing_city(),
                        "state" => $order->get_billing_state(),
                        "zipCode" => $order->get_billing_postcode(),
                        "countryCode" => $order->get_billing_country()
                    )
                ),
                "items" => array_reduce($order->get_items(), 'getItemPayload', []),
                "redirectUrl" => array(
                    "success" => $order->get_checkout_order_received_url(),
                    "failure" => $order->get_checkout_payment_url(),
                    "cancel" => $order->get_checkout_payment_url()
                ),
                "requestReferenceNumber" => strval($orderId)
            )
        );

        wc_get_logger()->log('info', 'Payload' . json_encode($payload));

        $response = $this->client->createCheckout($payload);

        if (array_key_exists("error", $response)) {
            wc_add_notice($response["error"], "error");
            return null;
        }

        return array(
            "result" => "success",
            "redirect" => $response["redirectUrl"]
        );
    }

    public function process_refund($orderId, $amount = NULL, $reason = '') {
        $order = wc_get_order($orderId);
        $payments = $this->client->getPaymentViaRrn($orderId);

        wc_get_logger()->log('info', 'Order ID ' . $orderId);

        $transactionId = $order->get_transaction_id();
        $receiptNumber = end(explode('-', $transactionId));

        $successfulPayments = array_filter($payments, function ($payment) use ($orderId, $receiptNumber) {
            if (empty($payment['receiptNumber']) || empty($payment['requestReferenceNumber'])) return false;
            $success = $payment['status'] == 'PAYMENT_SUCCESS';
            $matchedRefNum = $payment['requestReferenceNumber'] == strval($orderId);
            $matchedReceiptNum = $payment['receiptNumber'] == $receiptNumber;
            return $success && $matchedRefNum && $matchedReceiptNum;
        });
    
        if (count($successfulPayments) === 0) return;
    
        $successfulPayment = $successfulPayments[0];

        wc_get_logger()->log('info', 'PAYMENT ' . json_encode($successfulPayment));

        if (!$successfulPayment) {
            return new WP_Error(404, 'Can\'t find payment record to refund in Paymaya');
        }

        $paymentId = $successfulPayment['id'];

        if ($successfulPayment['canVoid']) {
            $response = $this->client->voidPayment($paymentId, empty($reason) ? 'Merchant manually voided' : $reason);

            if (array_key_exists("error", $response)) {
                wc_get_logger()->log('error', $response['error']);
                return false;
            }
    
            return true;
        }

        if ($successfulPayment['canRefund']) {
            $payload = json_encode(
                array(
                    'totalAmount' => array(
                        'amount' => $amount,
                        'currency' => $successfulPayment['currency']
                    ),
                    'reason' => empty($reason) ? 'Merchant manually refunded' : $reason
                )
            );
    
            $response = $this->client->refundPayment($paymentId, $payload);
    
            if (array_key_exists("error", $response)) {
                wc_get_logger()->log('error', $response['error']);
                return false;
            }
    
            return true;
        }

        return new WP_Error(400, 'Payment cannot be refunded');
    }

    public function handle_webhook_request() {
        $isPostRequest = $_SERVER['REQUEST_METHOD'] === 'POST';
        $hasWcApiQuery = isset($_GET['wc-api']);
        $hasCorrectQuery = $_GET['wc-api'] === 'cynder_paymaya';

        if (!$isPostRequest || !$hasWcApiQuery || !$hasCorrectQuery) {
            status_header(400);
            die();
        }

        $requestBody = file_get_contents('php://input');
        $checkout = json_decode($requestBody, true);

        $referenceNumber = $checkout['requestReferenceNumber'];

        $order = wc_get_order($referenceNumber);

        if (empty($order)) {
            wc_get_logger()->log('info', 'No transaction found with reference number '. $referenceNumber);

            status_header(204);
            die();
        }

        $checkoutStatus = $checkout['status'];
        $paymentStatus = $checkout['paymentStatus'];

        if ($checkoutStatus === 'COMPLETED' && $paymentStatus === 'PAYMENT_SUCCESS') {
            $transactionRefNumber = $checkout['transactionReferenceNumber'];

            $order->payment_complete($transactionRefNumber);
        } else {
            wc_get_logger()->log('error', 'Failed to complete order because checkout is ' . $checkoutStatus . ' and  payment is ' . $paymentStatus);
        }

        wc_get_logger()->log('info', 'Webhook processing for checkout ID ' . $checkout['id']);
    }

    function wc_order_item_add_action_buttons_callback($order) {
        $trxId = $order->get_transaction_id();
    
        if (!$trxId) return;
    
        $orderId = $order->get_id();
        $payments = $this->client->getPaymentViaRrn($orderId);
    
        $trxParts = explode('-', $trxId);
        $receiptNumber = end($trxParts);
    
        $successfulPayments = array_filter($payments, function ($payment) use ($orderId, $receiptNumber) {
            if (empty($payment['receiptNumber']) || empty($payment['requestReferenceNumber'])) return false;
            $success = $payment['status'] == 'PAYMENT_SUCCESS';
            $matchedRefNum = $payment['requestReferenceNumber'] == strval($orderId);
            $matchedReceiptNum = $payment['receiptNumber'] == $receiptNumber;
            return $success && $matchedRefNum && $matchedReceiptNum;
        });
    
        if (count($successfulPayments) === 0) return;
    
        $successfulPayment = $successfulPayments[0];
    
        wc_get_logger()->log('info', 'Payment ID ' . $successfulPayment['id'] . ' canRefund: ' . ($successfulPayment['canRefund'] == true ? 'true' : 'false'));
        wc_get_logger()->log('info', 'Payment ID ' . $successfulPayment['id'] . ' canVoid: ' . ($successfulPayment['canVoid'] == true ? 'true' : 'false'));
    
        if ($successfulPayment['canVoid']) {
            echo '<span style="color: blue; text-decoration: underline;" class="tips" data-tip="Refunding this order voids the payments for this transaction">Voidable</span>';
        }
    }
}