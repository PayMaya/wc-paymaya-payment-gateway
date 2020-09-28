<?php

/**
 * Paymaya Client Class
 * 
 * API Documentation: https://hackmd.io/@paymaya-pg/Checkout
 * 
 * A small wrapper for PayMaya's Checkout API built on top of
 * WordPress' wp_remote functions
 */
class Cynder_PaymayaClient {
    public function __construct($isSandbox, $publicKey, $secretKey) {
        $this->isSandbox = $isSandbox;
        $this->public_key = $publicKey;
        $this->secret_key = $secretKey;
    }

    /**
     * Generate headers for the requests, particularly authorization headers
     * based on configured API keys
     */
    private function getHeaders($usePublicKey = false, $additionalHeaders = []) {
        $key = $usePublicKey ? $this->public_key : $this->secret_key;

        $baseHeaders =  array(
            'Authorization' => 'Basic ' . base64_encode($key . ':'),
            'Content-Type' => 'application/json'
        );

        return array_merge($baseHeaders, $additionalHeaders);
    }

    /** Handle PayMaya API responses */
    private function handleResponse($response) {
        if (is_wp_error($response)) {
            return array(
                'error' => $response->get_error_message()
            );
        }

        $body = json_decode($response['body'], true);

        return $body;
    }

    /** Get the base URL depending on the sandbox mode */
    private function getBaseUrl() {
        return $this->isSandbox ? CYNDER_PAYMAYA_BASE_SANDBOX_URL : CYNDER_PAYMAYA_BASE_PRODUCTION_URL;
    }

    /** https://hackmd.io/@paymaya-pg/Checkout#Create-Checkout-Payment---POST-httpspg-sandboxpaymayacomcheckoutv1checkouts */
    public function createCheckout($payload) {
        $requestArgs = array(
            'body' => $payload,
            'method' => 'POST',
            'headers' => $this->getHeaders(true)
        );

        $response = wp_remote_post($this->getBaseUrl() . '/checkout/v1/checkouts', $requestArgs);

        return $this->handleResponse($response);
    }

    /** https://hackmd.io/@paymaya-pg/Checkout#Get-Webhook-List---GET-httpspg-sandboxpaymayacomcheckoutv1webhooks */
    public function retrieveWebhooks() {
        $requestArgs = array(
            'headers' => $this->getHeaders()
        );

        $response = wp_remote_get($this->getBaseUrl() . '/checkout/v1/webhooks', $requestArgs);

        return $this->handleResponse($response);
    }

    /** https://hackmd.io/@paymaya-pg/Checkout#Delete-Webhook---DELETE-httpspg-sandboxpaymayacomcheckoutv1webhookswebhookId */
    public function deleteWebhook($id) {
        $requestArgs = array(
            'method' => 'DELETE',
            'headers' => $this->getHeaders()
        );

        $response = wp_remote_post($this->getBaseUrl() . '/checkout/v1/webhooks/' . $id, $requestArgs);

        return $this->handleResponse($response);
    }

    /** https://hackmd.io/@paymaya-pg/Checkout#Create-Webhook---POST-httpspg-sandboxpaymayacomcheckoutv1webhooks */
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

        $response = wp_remote_post($this->getBaseUrl() . '/checkout/v1/webhooks', $requestArgs);

        return $this->handleResponse($response);
    }

    /** https://hackmd.io/@paymaya-pg/Checkout#Get-Payments-via-RRN---GET-httpspg-sandboxpaymayacompaymentsv1payment-rrnsrrn */
    public function getPaymentViaRrn($orderId) {
        $requestArgs = array(
            'headers' => $this->getHeaders()
        );

        $response = wp_remote_get($this->getBaseUrl() . '/payments/v1/payment-rrns/' . $orderId, $requestArgs);

        return $this->handleResponse($response);
    }

    /** https://hackmd.io/@paymaya-pg/Checkout#Refund-by-ID---POST-httpspg-sandboxpaymayacompaymentsv1paymentspaymentIdrefunds */
    public function refundPayment($paymentId, $payload) {
        $requestArgs = array(
            'body' => $payload,
            'method' => 'POST',
            'headers' => $this->getHeaders(),
        );

        $response = wp_remote_post($this->getBaseUrl() . '/payments/v1/payments/' . $paymentId . '/refunds', $requestArgs);

        return $this->handleResponse($response);
    }

    /** https://hackmd.io/@paymaya-pg/Checkout#Void-by-ID---POST-httpspg-sandboxpaymayacompaymentsv1paymentspaymentIdvoids */
    public function voidPayment($paymentId, $reason) {
        $requestArgs = array(
            'body' => json_encode(
                array(
                    'reason' => $reason
                )
            ),
            'method' => 'POST',
            'headers' => $this->getHeaders(),
        );

        $response = wp_remote_post($this->getBaseUrl() . '/payments/v1/payments/' . $paymentId . '/voids', $requestArgs);

        return $this->handleResponse($response);
    }

    /** https://hackmd.io/@paymaya-pg/Checkout#Capture---POST-httpspg-sandboxpaymayacompaymentsv1paymentspaymentIdcapture */
    public function capturePayment($paymentId, $payload) {
        $requestArgs = array(
            'body' => $payload,
            'method' => 'POST',
            'headers' => $this->getHeaders(),
        );

        $response = wp_remote_post($this->getBaseUrl() . '/payments/v1/payments/' . $paymentId . '/capture', $requestArgs);

        return $this->handleResponse($response);
    }

    /** https://hackmd.io/@paymaya-pg/Checkout#Get-Refunds---GET-httpspg-sandboxpaymayacompaymentsv1paymentspaymentIdrefunds */
    public function getRefunds($paymentId) {
        $requestArgs = array(
            'headers' => $this->getHeaders(),
        );

        $response = wp_remote_get($this->getBaseUrl() . '/payments/v1/payments/' . $paymentId . '/refunds', $requestArgs);

        return $this->handleResponse($response);
    }
}