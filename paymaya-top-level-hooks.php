<?php

function cynder_paymaya_scripts($hook) {
    if ($hook !== 'post.php') return;

    $paymentGatewaId = 'paymaya';
    $paymentGateways = WC_Payment_Gateways::instance();

    $paymayaGateway = $paymentGateways->payment_gateways()[$paymentGatewaId];

    $paymentGatewayEnabled = $paymayaGateway->get_option('enabled');

    /** If gateway isn't enabled, don't load JS scripts */
    if ($paymentGatewayEnabled !== 'yes') return;

    $orderId = $_GET['post'];
    $order = wc_get_order($orderId);

    if (!method_exists($order, 'get_meta_data')) return;

    $orderMetadata = $order->get_meta_data();

    $authorizationTypeMetadata = array_search($paymentGatewaId . '_authorization_type', array_column($orderMetadata, 'key'));

    /** If order isn't made with manual capture, don't load JS scripts */
    if ($authorizationTypeMetadata['value'] === 'none') return;

    $isSandbox = $paymayaGateway->get_option('sandbox');
    $secretKey = $paymayaGateway->get_option('secret_key');
    $publicKey = $paymayaGateway->get_option('public_key');

    $client = new Cynder_PaymayaClient($isSandbox === 'yes', $publicKey, $secretKey);

    $payments = $client->getPaymentViaRrn($orderId);

    if (array_key_exists("error", $payments)) {
        wc_get_logger()->log('error', $payments['error']);
        return;
    }

    if (count($payments) === 0) {
        wc_get_logger()->log('error', 'No payments associated to order ID ' . $orderId);
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
        wc_get_logger()->log('info', '[Loading JS Scripts] No captured payments associated to order ID ' . $orderId);
        return;
    }

    if (count($authorizedOrCapturedPayments) > 2) {
        wc_get_logger()->log('error', 'Multiple captured payments associated to order ID ' . $orderId);
        return;
    }

    $authorizedOrCapturedPayment = $authorizedOrCapturedPayments[0];

    wc_get_logger()->log('info', 'Authorized Or Captured Payment ' . json_encode($authorizedOrCapturedPayment));

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

function capture_payment() {
    $captureAmount = $_POST['capture_amount'];
    $orderId = $_POST['order_id'];

    if (!isset($captureAmount)) {
        return wp_send_json(
            array('error' => 'Invalid capture amount'),
            400
        );
    }

    if (!isset($orderId)) {
        return wp_send_json(
            array('error' => 'Invalid order ID'),
            400
        );
    }

    $paymentGatewaId = 'paymaya';
    $paymentGateways = WC_Payment_Gateways::instance();

    $paymayaGateway = $paymentGateways->payment_gateways()[$paymentGatewaId];

    $paymentGatewayEnabled = $paymayaGateway->get_option('enabled');

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

    /** Enable for debugging purposes */
    // wc_get_logger()->log('info', 'Authorized Payments ' . json_encode($authorizedPayments));

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

    /** Enable for debugging purposes */
    wc_get_logger()->log('info', 'Response ' . json_encode($response));

    if (array_key_exists("error", $response)) {
        wc_get_logger()->log('error', $response['error']);

        return wp_send_json(
            array('error' => $response['error']),
            400
        );
    }

    wp_send_json(array('success' => true), 200);
}

add_action(
    'wp_ajax_capture_payment',
    'capture_payment'
);