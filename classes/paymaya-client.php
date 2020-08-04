<?php

class Cynder_PaymayaClient {
    public function __construct($publicKey, $secretKey) {
        $this->public_key = $publicKey;
        $this->secret_key = $secretKey;
    }

    private function getHeaders($usePublicKey = false, $additionalHeaders = []) {
        $key = $usePublicKey ? $this->public_key : $this->secret_key;

        $baseHeaders =  array(
            'Authorization' => 'Basic ' . base64_encode($key . ':'),
            'Content-Type' => 'application/json'
        );

        return array_merge($baseHeaders, $additionalHeaders);
    }

    private function handleResponse($response) {
        wc_get_logger()->log('info', json_encode($response));

        if (is_wp_error($response)) {
            return array(
                'error' => $response->get_error_message()
            );
        }

        $body = json_decode($response['body'], true);

        return $body;
    }

    public function createCheckout($payload) {
        $requestArgs = array(
            'body' => $payload,
            'method' => 'POST',
            'headers' => $this->getHeaders(true)
        );

        $response = wp_remote_post(CYNDER_PAYMAYA_BASE_URL . '/checkout/v1/checkouts', $requestArgs);

        return $this->handleResponse($response);
    }

    public function retrieveWebhooks() {
        $requestArgs = array(
            'headers' => $this->getHeaders()
        );

        wc_get_logger()->log('info', CYNDER_PAYMAYA_BASE_URL);

        $response = wp_remote_get(CYNDER_PAYMAYA_BASE_URL . '/checkout/v1/webhooks', $requestArgs);

        return $this->handleResponse($response);
    }

    public function deleteWebhook($id) {
        $requestArgs = array(
            'method' => 'DELETE',
            'headers' => $this->getHeaders()
        );

        $response = wp_remote_post(CYNDER_PAYMAYA_BASE_URL . '/checkout/v1/webhooks/' . $id, $requestArgs);

        return $this->handleResponse($response);
    }

    public function createWebhook($type, $callbackUrl) {
        $requestArgs = array(
            'body' => json_encode(
                array(
                    'name' => $type,
                    'callbackUrl' => $callbackUrl
                )
            ),
            'method' => 'POST',
            'headers' => $this->getHeaders()
        );

        $response = wp_remote_post(CYNDER_PAYMAYA_BASE_URL . '/checkout/v1/webhooks', $requestArgs);

        return $this->handleResponse($response);
    }
}