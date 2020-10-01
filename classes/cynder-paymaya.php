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

$fileDir = dirname(__FILE__);
include_once $fileDir.'/paymaya-client.php';

/** Error identifiers */
define('CYNDER_PAYMAYA_PROCESS_PAYMENT_BLOCK', 'Process Payment');
define('CYNDER_PAYMAYA_PROCESS_REFUND_BLOCK', 'Process Refund');
define('CYNDER_PAYMAYA_MASS_REFUND_PAYMENT_BLOCK', 'Mass Refund');
define('CYNDER_PAYMAYA_HANDLE_WEBHOOK_REQUEST_BLOCK', 'Handle Webhook Request');
define('CYNDER_PAYMAYA_ADD_ACTION_BUTTONS_BLOCK', 'Add Action Buttons');
define('CYNDER_PAYMAYA_AFTER_TOTALS_BLOCK', 'After Order Totals');
define('CYNDER_PAYMAYA_CREATE_CHECKOUT_EVENT', 'createCheckout');
define('CYNDER_PAYMAYA_VOID_PAYMENT_EVENT', 'voidPayment');
define('CYNDER_PAYMAYA_REFUND_PAYMENT_EVENT', 'refundPayment');

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
        $this->manual_capture = $this->get_option('manual_capture');
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
                'description' => 'API Keys are used to authenticate yourself to Paymaya checkout.<br/><strong>This plugin will not work without these keys</strong>.<br/>To obtain a set of keys, contact Paymaya directly.'
            ),
            'sandbox' => array(
                'title' => 'Sandbox Mode',
                'type' => 'checkbox',
                'description' => 'Enabled sandbox mode to test payment transactions with Paymaya.<br/>A set of test API keys and card numbers are available <a target="_blank" href="https://hackmd.io/@paymaya-pg/Checkout#Sandbox-Test-Credentials">here</a>.'
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
                'description' => 'The following fields are used by Paymaya to properly process order statuses after payments.<br/><strong>DON\'T CHANGE THIS UNLESS YOU KNOW WHAT YOU\'RE DOING</strong>.<br/>For more information, refer <a target="_blank" href="https://hackmd.io/@paymaya-pg/Checkout#Webhooks">here</a>.'
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
        $is_options_saved = parent::process_admin_options();

        $webhookSuccessUrl = $this->get_option('webhook_success');
        $webhookFailureUrl = $this->get_option('webhook_failure');

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

            $createdWebhook = $this->client->createWebhook('CHECKOUT_SUCCESS', $webhookSuccessUrl);

            if (array_key_exists("error", $createdWebhook)) {
                $this->add_error($createdWebhook["error"]);
            }

            $createdWebhook = $this->client->createWebhook('CHECKOUT_FAILURE',$webhookFailureUrl);

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
                    "description" => $product->get_description(),
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

        $catchRedirectUrl = get_home_url() . '/?wc-api=cynder_paymaya_catch_redirect&order=' . $orderId;

        $payload = array(
            "totalAmount" => array(
                "value" => intval($order->get_total()),
                "currency" => $order->get_currency(),
                "details" => array(
                    "discount" => intval($order->get_discount_total()),
                    "shippingFee" => intval($order->get_shipping_total()),
                    "subtotal" => $order->get_subtotal()
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
                    "firstName" => $order->get_shipping_first_name(),
                    "lastName" => $order->get_shipping_last_name(),
                    "line1" => $order->get_shipping_address_1(),
                    "line2" => $order->get_shipping_address_2(),
                    "city" => $order->get_shipping_city(),
                    "state" => $order->get_shipping_state(),
                    "zipCode" => $order->get_shipping_postcode(),
                    "countryCode" => $order->get_shipping_country(),
                    "shippingType" => 'ST', // standard shipping is hard-coded for now
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
            "items" => array_reduce($order->get_items(), 'getItemPayload', []),
            "redirectUrl" => array(
                "success" => $catchRedirectUrl . '&status=success',
                "failure" => $catchRedirectUrl . '&status=failed',
                "cancel" => $order->get_checkout_payment_url()
            ),
            "requestReferenceNumber" => strval($orderId)
        );

        /** Enable for debugging purposes */
        // wc_get_logger()->log('info', 'Manual capture authorization type ' . $this->manual_capture);

        if ($this->manual_capture !== "none") {
            $payload['authorizationType'] = strtoupper($this->manual_capture);
        };

        $encodedPayload = json_encode($payload);

        /** Enable for debugging purposes */
        // wc_get_logger()->log('info', 'Payload' . $encodedPayload);

        $response = $this->client->createCheckout($encodedPayload);

        /** Enable for debugging purposes */
        // wc_get_logger()->log('info', 'Response ' . json_encode($response));

        if (array_key_exists("error", $response)) {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_PROCESS_PAYMENT_BLOCK . '][' . CYNDER_PAYMAYA_CREATE_CHECKOUT_EVENT . '] ' . json_encode($response['error']));
            return null;
        }

        $order->add_meta_data($this->id . '_checkout_id', $response['checkoutId']);
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
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_PROCESS_PAYMENT_BLOCK . '][' . CYNDER_PAYMAYA_GET_PAYMENTS_EVENT . '] ' . $payments['error']);
            return false;
        }

        $amountValue = floatval($amount);

        /** Enable for debugging purposes */
        // wc_get_logger()->log('info', 'Payments ' . json_encode($payments));

        /** Enable for debugging */
        // wc_get_logger()->log('info', 'Order ID ' . $orderId);

        $orderMetadata = $order->get_meta_data();

        $authorizationTypeMetadataIndex = array_search($this->id . '_authorization_type', array_column($orderMetadata, 'key'));
        $authorizationTypeMetadata = $orderMetadata[$authorizationTypeMetadataIndex];

        /** Enable for debugging purposes */
        // wc_get_logger()->log('info', 'metadata ' . json_encode($authorizationTypeMetadata));

        if ($authorizationTypeMetadata->value === 'none') {
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
    
            /** Enable for debugging purposes */
            // wc_get_logger()->log('info', 'PAYMENT ' . json_encode($successfulPayment));
    
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
            /** Enable for debugging purposes */
            // wc_get_logger()->log('info', 'Amount entered ' . $amountValue);

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
                    return strtotime($a['createdAt']) - strtotime($b['createdAt']);
                });

                /** Enable for debugging purposes */
                // wc_get_logger()->log('info', 'Sorted Payments ' . json_encode($capturedPayments));

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

                /** Enable for debugging purposes */
                // wc_get_logger()->log('info', 'Available Actions ' . json_encode($availableActions));

                $actionsToProcess = array();

                do {
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
                } while ($amountValue != 0 || count($availableActions) > 0);

                /** Enable for debugging purposes */
                // wc_get_logger()->log('info', 'Actions to process ' . json_encode($actionsToProcess));

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
                return new WP_Error(400, 'Something went wrong with the refund. Check your PayMaya merchant dashboard for actual balances.');
            }
        }

        return true;
    }

    public function handle_webhook_request() {
        $isPostRequest = $_SERVER['REQUEST_METHOD'] === 'POST';
        $wcApiQuery = sanitize_text_field($_GET['wc-api']);
        $hasWcApiQuery = isset($wcApiQuery);
        $hasCorrectQuery = $wcApiQuery === 'cynder_paymaya';

        if (!$isPostRequest || !$hasWcApiQuery || !$hasCorrectQuery) {
            status_header(400);
            die();
        }

        $requestBody = file_get_contents('php://input');
        $checkout = json_decode($requestBody, true);

        /** Enable for debugging purposes */
        // wc_get_logger()->log('info', 'Checkout ' . json_encode($checkout));

        $referenceNumber = $checkout['requestReferenceNumber'];

        $order = wc_get_order($referenceNumber);

        if (empty($order)) {
            wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_WEBHOOK_REQUEST_BLOCK . '] No transaction found with reference number '. $referenceNumber);

            status_header(204);
            die();
        }

        $checkoutStatus = $checkout['status'];
        $paymentStatus = $checkout['paymentStatus'];

        if ($checkoutStatus !== 'COMPLETED' || $paymentStatus !== 'PAYMENT_SUCCESS') {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_WEBHOOK_REQUEST_BLOCK . '] Failed to complete order because checkout is ' . $checkoutStatus . ' and  payment is ' . $paymentStatus);
            return;
        }

        $orderMetadata = $order->get_meta_data();

        $authorizationTypeMetadataIndex = array_search($this->id . '_authorization_type', array_column($orderMetadata, 'key'));
        $authorizationTypeMetadata = $orderMetadata[$authorizationTypeMetadataIndex];

        /** Enable for debugging purposes */
        // wc_get_logger()->log('info', 'metadata ' . json_encode($authorizationTypeMetadata));

        $totalAmountData = $checkout['totalAmount'];
        $subtotal = floatval($totalAmountData['details']['subtotal']);
        $shippingFee = floatval($totalAmountData['details']['shippingFee']);
        $discount = floatval($totalAmountData['details']['discount']);
        $totalAmount = ($subtotal + $shippingFee) - $discount;
        $amountPaid = floatval($totalAmountData['value']);

        /** Get txn ref number */
        $transactionRefNumber = $checkout['transactionReferenceNumber'];

        if ($authorizationTypeMetadata->value === 'none') {
            /** For non-manual capture payments: */

            /** With correct data based on assumptions */
            if ($totalAmount === $amountPaid) {
                $order->payment_complete($transactionRefNumber);
            } else {
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_WEBHOOK_REQUEST_BLOCK . '] Amount mismatch. Open payment details on Paymaya dashboard with txn ref number ' . $transactionRefNumber);
            }
        } else {
            /** For manual capture payments */

            $payments = $this->client->getPaymentViaRrn($referenceNumber);

            /** Enable for debugging purposes */
            // wc_get_logger()->log('info', 'Payments ' . json_encode($payments));

            if (array_key_exists("error", $payments)) {
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_WEBHOOK_REQUEST_BLOCK . '] ' . $payments['error']);
                return;
            }

            if (count($payments) === 0) {
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_WEBHOOK_REQUEST_BLOCK . '] No payments associated to order ID ' . $referenceNumber);
                return;
            }

            $capturedPayments = array_values(
                array_filter(
                    $payments,
                    function ($payment) {
                        if (empty($payment['receiptNumber']) || empty($payment['requestReferenceNumber'])) return false;
                        return array_key_exists('authorizationType', $payment);
                    }
                )
            );

            if (count($capturedPayments) === 0) {
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_WEBHOOK_REQUEST_BLOCK . '] No captured payments associated to order ID ' . $referenceNumber);
                return;
            }

            if (count($capturedPayments) > 2) {
                wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_HANDLE_WEBHOOK_REQUEST_BLOCK . '] Multiple captured payments associated to order ID ' . $referenceNumber);
                return;
            }

            $capturedPayment = $capturedPayments[0];

            if ($capturedPayment['amount'] !== $capturedPayment['capturedAmount']) return;

            $order->payment_complete($transactionRefNumber);
        }

        wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_HANDLE_WEBHOOK_REQUEST_BLOCK . '] Webhook processing for checkout ID ' . $checkout['id']);
    }

    function wc_order_item_add_action_buttons_callback($order) {
        /** Enable for debugging purposes */
        // wc_get_logger()->log('info', $order->get_id() . ' ' . $order->get_total_refunded());
        $orderId = $order->get_id();
        $payments = $this->client->getPaymentViaRrn($orderId);

        if (array_key_exists("error", $payments)) {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_ADD_ACTION_BUTTONS_BLOCK . '][' . CYNDER_PAYMAYA_GET_PAYMENTS_EVENT . '] ' . $payments['error']);
            return;
        }

        /** Enable for debugging purposes */
        // wc_get_logger()->log('info', 'Payments ' . json_encode($payments));
    
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
        
            /** Enable for debugging purposes */
            // wc_get_logger()->log('info', 'Payment ID ' . $successfulPayment['id'] . ' canRefund: ' . ($successfulPayment['canRefund'] == true ? 'true' : 'false'));
            // wc_get_logger()->log('info', 'Payment ID ' . $successfulPayment['id'] . ' canVoid: ' . ($successfulPayment['canVoid'] == true ? 'true' : 'false'));
        
            if ($successfulPayment['canVoid']) {
                echo '<span style="color: blue; text-decoration: underline;" class="tips" data-tip="Refunding the full amount for this order voids the payments for this transaction">Voidable</span>';
            }
        }

        $orderMetadata = $order->get_meta_data();

        /** Enable for debugging purposes */
        // wc_get_logger()->log('info', 'Metadata ' . json_encode($orderMetadata));

        $authorizationTypeMetadata = array_search($this->id . '_authorization_type', array_column($orderMetadata, 'key'));

        if ($authorizationTypeMetadata['value'] === 'none') return;

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

        if (count($authorizedPayments) !== 0) {
            // $authorizedPayment = $authorizedPayments[0];
        
            /** Enable for debugging purposes */
            // wc_get_logger()->log('info', 'Payment ID ' . $authorizedPayment['id']);
        
            echo '<button type="button" class="button capture-items">Capture</button>';
        }
    }

    function wc_captured_payments($orderId) {
        $order = wc_get_order($orderId);

        $orderMetadata = $order->get_meta_data();

        $authorizationTypeMetadataIndex = array_search($this->id . '_authorization_type', array_column($orderMetadata, 'key'));
        $authorizationTypeMetadata = $orderMetadata[$authorizationTypeMetadataIndex];
        $authorizationType = $authorizationTypeMetadata->value;

        if ($authorizationType === 'none') return;

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
        $orderMetadata = $order->get_meta_data();

        $authorizationTypeMetadataIndex = array_search($this->id . '_authorization_type', array_column($orderMetadata, 'key'));

        if (!$authorizationTypeMetadataIndex) return;

        $authorizationTypeMetadata = $orderMetadata[$authorizationTypeMetadataIndex];
        $authorizationType = $authorizationTypeMetadata->value;

        if ($authorizationType === 'none') return;

        echo '<h4>Paymaya Payment Processing Notice</h4><em>On capture completion of the total amount, expect delays on payment processing. Refresh page to check if payments have been processed and order status has been updated.</em>';
    }
}
