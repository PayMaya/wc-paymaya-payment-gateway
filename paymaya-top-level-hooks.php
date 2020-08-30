<?php

function cynder_paymaya_scripts($hook) {
    if ($hook !== 'post.php') return;

    $orderId = $_GET['post'];
    $order = wc_get_order($orderId);
    
    wp_register_script(
        'woocommerce_cynder_paymaya',
        plugins_url('assets/js/paymaya.js', CYNDER_PAYMAYA_MAIN_FILE),
        array('jquery')
    );

    wp_enqueue_script('woocommerce_cynder_paymaya');

    $jsVar = array(
        'order_id' => $orderId,
        'total_amount' => intval($order->get_total()),
    );

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

    $authorizedPayments = array_filter($payments, function ($payment) use ($orderId) {
        if (empty($payment['receiptNumber']) || empty($payment['requestReferenceNumber'])) return false;
        $authorized = $payment['status'] == 'AUTHORIZED';
        $canCapture = $payment['canCapture'] == true;
        $matchedRefNum = $payment['requestReferenceNumber'] == strval($orderId);
        return $authorized && $canCapture && $matchedRefNum;
    });

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
    // wc_get_logger()->log('info', 'Response ' . json_encode($response));

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