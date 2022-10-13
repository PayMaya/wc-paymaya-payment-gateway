<?php

$fileDir = dirname(__FILE__);
include_once $fileDir.'/classes/paymaya-client.php';

/** Logger indicators */
define('CYNDER_PAYMAYA_LOADING_ADMIN_JS_SCRIPTS_BLOCK', 'Loading Admin JS Scripts');
define('CYNDER_PAYMAYA_CAPTURE_PAYMENT_BLOCK', 'Capture Payment');
define('CYNDER_PAYMAYA_CATCH_REDIRECT_BLOCK', 'Paymaya Catch Redirect');
define('CYNDER_PAYMAYA_GET_PAYMENTS_EVENT', 'getPaymentViaRrn');
define('CYNDER_PAYMAYA_CAPTURE_PAYMENT_EVENT', 'capturePayment');
define('CYNDER_PAYMAYA_UPDATE_EVENT', 'Update Maya Plugin');

function cynder_paymaya_scripts($hook) {
    if ($hook !== 'post.php') return;

    $paymentGatewaId = 'paymaya';
    $paymentGateways = WC_Payment_Gateways::instance();

    $paymayaGateway = $paymentGateways->payment_gateways()[$paymentGatewaId];

    $paymentGatewayEnabled = $paymayaGateway->get_option('enabled');
    $debugMode = $paymayaGateway->get_option('debug_mode');

    /** If gateway isn't enabled, don't load JS scripts */
    if ($paymentGatewayEnabled !== 'yes') return;

    $orderId = sanitize_key($_GET['post']);
    $order = wc_get_order($orderId);

    if (empty($order)) return;
    if (!method_exists($order, 'get_meta_data')) return;

    $orderMetadata = $order->get_meta_data();

    $authorizationTypeMetadataIndex = array_search($paymentGatewaId . '_authorization_type', array_column($orderMetadata, 'key'));
    $authorizationTypeMetadata = $orderMetadata[$authorizationTypeMetadataIndex];

    /** If order isn't made with manual capture, don't load JS scripts */
    if ($authorizationTypeMetadata->value === 'none') return;

    $isSandbox = $paymayaGateway->get_option('sandbox');
    $secretKey = $paymayaGateway->get_option('secret_key');
    $publicKey = $paymayaGateway->get_option('public_key');

    $client = new Cynder_PaymayaClient($isSandbox === 'yes', $publicKey, $secretKey);

    $payments = $client->getPaymentViaRrn($orderId);

    if ($debugMode === 'yes') {
        wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_LOADING_ADMIN_JS_SCRIPTS_BLOCK . '] Payments via RRN ' . wc_print_r($payments, true));
    }

    if (array_key_exists('error', $payments)) {
        wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_LOADING_ADMIN_JS_SCRIPTS_BLOCK . '][' . CYNDER_PAYMAYA_GET_PAYMENTS_EVENT . '] ' . $payments['error']);
        return;
    }

    if (count($payments) === 0) {
        wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_LOADING_ADMIN_JS_SCRIPTS_BLOCK . '] No payments associated to order ID ' . $orderId);
        return;
    }

    $authorizedOrCapturedPayments = array_values(
        array_filter(
            $payments,
            function ($payment) {
                if (empty($payment['receiptNumber']) || empty($payment['requestReferenceNumber'])) return false;
                $authorized = $payment['status'] == 'AUTHORIZED';
                $captured = $payment['status'] == 'CAPTURED';
                return $authorized || $captured;
            }
        )
    );

    if (count($authorizedOrCapturedPayments) === 0) {
        wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_LOADING_ADMIN_JS_SCRIPTS_BLOCK . '] No captured payments associated to order ID ' . $orderId);
        return;
    }

    if (count($authorizedOrCapturedPayments) > 2) {
        wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_LOADING_ADMIN_JS_SCRIPTS_BLOCK . '] Multiple captured payments associated to order ID ' . $orderId);
        return;
    }

    $authorizedOrCapturedPayment = $authorizedOrCapturedPayments[0];

    /** Enable for debugging purposes */
    // wc_get_logger()->log('info', 'Authorized Or Captured Payment ' . json_encode($authorizedOrCapturedPayment));

    $jsVar = array(
        'order_id' => $orderId,
        'amount_authorized' => intval($authorizedOrCapturedPayment['amount']),
        'amount_captured' => intval($authorizedOrCapturedPayment['capturedAmount']),
    );

    wp_register_script(
        'woocommerce_cynder_paymaya',
        plugins_url('assets/js/paymaya.js', CYNDER_PAYMAYA_MAIN_FILE),
        array('jquery')
    );

    wp_enqueue_script('woocommerce_cynder_paymaya');
    wp_localize_script('woocommerce_cynder_paymaya', 'cynder_paymaya_order', $jsVar);
}

add_action(
    'admin_enqueue_scripts',
    'cynder_paymaya_scripts'
);

function cynder_paymaya_capture_payment() {
    $captureAmount = sanitize_text_field($_POST['capture_amount']);
    $orderId = sanitize_key($_POST['order_id']);

    if (!isset($captureAmount)) {
        return wp_send_json(
            array('error' => '[' . CYNDER_PAYMAYA_CAPTURE_PAYMENT_BLOCK . '] Invalid capture amount'),
            400
        );
    }

    if (!isset($orderId)) {
        return wp_send_json(
            array('error' => '[' . CYNDER_PAYMAYA_CAPTURE_PAYMENT_BLOCK . '] Invalid order ID'),
            400
        );
    }

    $paymentGatewaId = 'paymaya';
    $paymentGateways = WC_Payment_Gateways::instance();

    $paymayaGateway = $paymentGateways->payment_gateways()[$paymentGatewaId];

    $paymentGatewayEnabled = $paymayaGateway->get_option('enabled');
    $debugMode = $paymayaGateway->get_option('debug_mode');

    if ($paymentGatewayEnabled !== 'yes') {
        return wp_send_json(
            array('error' => 'Payment gateway must be enabled'),
            400
        );
    }

    $isSandbox = $paymayaGateway->get_option('sandbox');
    $secretKey = $paymayaGateway->get_option('secret_key');
    $publicKey = $paymayaGateway->get_option('public_key');

    $client = new Cynder_PaymayaClient($isSandbox === 'yes', $publicKey, $secretKey);

    $payments = $client->getPaymentViaRrn($orderId);

    if (array_key_exists('error', $payments)) {
        wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_CAPTURE_PAYMENT_BLOCK . '][' . CYNDER_PAYMAYA_GET_PAYMENTS_EVENT . '] ' . wc_print_r($payments['error'], true));
        return wp_send_json(
            array('error' => 'An error occured. If issue persists, contact Paymaya support.'),
            400
        );
    }

    $authorizedPayments = array_values(
        array_filter($payments, function ($payment) use ($orderId) {
            if (empty($payment['receiptNumber']) || empty($payment['requestReferenceNumber'])) return false;
            $authorized = $payment['status'] == 'AUTHORIZED';
            $captured = $payment['status'] == 'CAPTURED';
            $canCapture = $payment['canCapture'] == true;
            $matchedRefNum = $payment['requestReferenceNumber'] == strval($orderId);
            return ($authorized || $captured) && $canCapture && $matchedRefNum;
        })
    );

    if ($debugMode === 'yes') {
        wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_CAPTURE_PAYMENT_BLOCK . '] Authorized Payments ' . wc_print_r($authorizedPayments, true));
    }

    if (count($authorizedPayments) === 0) {
        return wp_send_json(
            array('error' => 'No authorized payments to capture'),
            400
        );
    }

    $authorizedPayment = $authorizedPayments[0];
    $order = wc_get_order($orderId);
    $currency = $order->get_currency();

    $payload = json_encode(
        array(
            'requestReferenceNumber' => $orderId,
            'captureAmount' => array(
                'amount' => $captureAmount,
                'currency' => $currency
            )
        )
    );

    $response = $client->capturePayment($authorizedPayment['id'], $payload);

    if ($debugMode === 'yes') {
        wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_CAPTURE_PAYMENT_BLOCK . '] Capture payment response ' . wc_print_r($response, true));
    }

    if (array_key_exists("error", $response)) {
        wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_CAPTURE_PAYMENT_BLOCK . '][' . CYNDER_PAYMAYA_CAPTURE_PAYMENT_EVENT . '] ' . wc_print_r($response['error'], true));

        $message = 'An error occured. If issue persists, contact Maya support.';

        if (array_key_exists('message', $response['error'])) {
            $message = $response['error']['message'];
        }

        return wp_send_json(
            array('error' => $message),
            400
        );
    }

    wp_send_json(array('success' => true), 200);
}

add_action(
    'wp_ajax_cynder_paymaya_capture_payment',
    'cynder_paymaya_capture_payment'
);

function cynder_paymaya_catch_redirect() {
    $paymentGatewaId = 'paymaya';
    $paymentGateways = WC_Payment_Gateways::instance();

    $paymayaGateway = $paymentGateways->payment_gateways()[$paymentGatewaId];
    $debugMode = $paymayaGateway->get_option('debug_mode');

    if ($debugMode === 'yes') {
        wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_CATCH_REDIRECT_BLOCK . '] Redirect Params ' . wc_print_r($_GET, true));
    }

    $orderId = sanitize_key($_GET['order']);

    if (!isset($orderId)) {
        /** Check order ID */
        wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_CATCH_REDIRECT_BLOCK . '] No order found with ID ' . $orderId);
        wc_add_notice('Something went wrong, please contact Maya support.', 'error');
        wp_redirect(get_home_url());
    }

    $order = wc_get_order($orderId);

    $status = sanitize_text_field($_GET['status']);

    if ($status === 'success') {
        wp_redirect($order->get_checkout_order_received_url());
    } else if ($status === 'failed') {
        wc_add_notice('Payment failed. Please try again or try another payment method.', 'error');
        wp_redirect($order->get_checkout_payment_url());
    }
}

add_action(
    'woocommerce_api_cynder_paymaya_catch_redirect',
    'cynder_paymaya_catch_redirect'
);

function cynder_paymaya_require_shipping_address2_checkout_field($fields) {
    $paymentGatewaId = 'paymaya';
    $paymentGateways = WC_Payment_Gateways::instance();

    $paymayaGateway = $paymentGateways->payment_gateways()[$paymentGatewaId];
    $requireLine2 = $paymayaGateway->get_option('require_billing_address_2');

    $fields['billing']['billing_address_2']['required'] = $requireLine2 === 'yes';
    return $fields;
}

add_filter('woocommerce_checkout_fields', 'cynder_paymaya_require_shipping_address2_checkout_field');

function update_paymaya_plugin() {
    $mainPluginSettings = get_option('woocommerce_paymaya_settings');

    $client = new Cynder_PaymayaClient(
        $mainPluginSettings['sandbox'] === 'yes',
        $mainPluginSettings['public_key'],
        $mainPluginSettings['secret_key'],
    );

    $webhooks = $client->retrieveWebhooks();

    if ($mainPluginSettings['debug_mode'] === 'yes') {
        wc_get_logger()->log('info', '[' . CYNDER_PAYMAYA_UPDATE_EVENT . '] ' . wc_print_r($webhooks, true));
    }

    if (array_key_exists("error", $webhooks)) {
        wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_UPDATE_EVENT . '] Error retrieving webhooks ' . wc_print_r($webhooks['error'], true));
    }

    foreach($webhooks as $webhook) {
        $deletedWebhook = $client->deleteWebhook($webhook["id"]);

        if (array_key_exists("error", $deletedWebhook)) {
            wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_UPDATE_EVENT . '] Error deleting webhooks ' . wc_print_r($deletedWebhook['error'], true));
        }
    }

    $webhookUrl = isset($mainPluginSettings['webhook_payment_status']) ? $mainPluginSettings['webhook_payment_status'] : get_home_url() . '?wc-api=cynder_paymaya_payment';

    $createdWebhook = $client->createWebhook('PAYMENT_SUCCESS', $webhookUrl);

    if (array_key_exists("error", $createdWebhook)) {
        wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_UPDATE_EVENT . '] Error creating webhooks ' . wc_print_r($createdWebhook['error'], true));
    }

    $createdWebhook = $client->createWebhook('PAYMENT_FAILED', $webhookUrl);

    if (array_key_exists("error", $createdWebhook)) {
        wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_UPDATE_EVENT . '] Error creating webhooks ' . wc_print_r($createdWebhook['error'], true));
    }

    $createdWebhook = $client->createWebhook('PAYMENT_EXPIRED', $webhookUrl);

    if (array_key_exists("error", $createdWebhook)) {
        wc_get_logger()->log('error', '[' . CYNDER_PAYMAYA_UPDATE_EVENT . '] Error creating webhooks ' . wc_print_r($createdWebhook['error'], true));
    }
}

add_action('woocommerce_paymaya_updated', 'update_paymaya_plugin');