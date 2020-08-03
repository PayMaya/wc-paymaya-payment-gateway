<?php

class Cynder_PaymayaClient {
    public function __construct($publicKey, $secretKey) {
        $this->public_key = $publicKey;
        $this->secret_key = $secretKey;
    }

    public function getHeaders($usePublicKey = false, $additionalHeaders = []) {
        $key = $usePublicKey ? $this->public_key : $this->secret_key;

        $baseHeaders =  array(
            'Authorization' => 'Basic ' . base64_encode($key . ':'),
            'Content-Type' => 'application/json'
        );

        return array_merge($baseHeaders, $additionalHeaders);
    }

    public function createCheckout($payload) {
        $requestArgs = array(
            'body' => $payload,
            'method' => 'POST',
            'headers' => $this->getHeaders(true)
        );

        $response = wp_remote_post(CYNDER_PAYMAYA_BASE_URL . '/checkout/v1/checkouts', $requestArgs);

        if (!is_wp_error($response)) {
            $body = json_decode($response['body'], true);

            return $body;
        } else {
            return array(
                'error' => $response->get_error_message()
            );
        }
    }
}