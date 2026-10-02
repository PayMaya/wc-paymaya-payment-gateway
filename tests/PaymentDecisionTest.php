<?php
use PHPUnit\Framework\TestCase;

/**
 * Decisions behind the payment webhook handler. Payment shapes follow Maya's documented samples
 * (https://developers.maya.ph/page/webhook-sample-json-data); replace with a recorded sandbox
 * response when one is available.
 */
class PaymentDecisionTest extends TestCase {
    private function payment(array $override = array()) {
        return array_merge(array(
            'id' => 'e732f996-cb87-4120-b712-166d8183c01d',
            'status' => 'PAYMENT_SUCCESS',
            'amount' => '100',
            'currency' => 'PHP',
            'requestReferenceNumber' => '1568',
            'receiptNumber' => 'abc123',
        ), $override);
    }

    private function captured(array $override = array()) {
        return $this->payment(array_merge(array('status' => 'CAPTURED', 'authorizationType' => 'NORMAL', 'capturedAmount' => '100'), $override));
    }

    private function lookup($payments) {
        return Cynder_Paymaya_Webhook_Guard::classify_lookup($payments, 100.0, 'PHP', '1568', array('PAYMENT_SUCCESS'));
    }

    private function manual($payments, $webhookStatus = 'PAYMENT_SUCCESS') {
        return Cynder_Paymaya_Webhook_Guard::classify_manual_capture($payments, 100.0, 'PHP', '1568', $webhookStatus);
    }

    // ---- is_valid_webhook_payload (400) ----

    /** @dataProvider malformedPayloads */
    public function test_malformed_payload_is_rejected($payload) {
        $this->assertFalse(Cynder_Paymaya_Webhook_Guard::is_valid_webhook_payload($payload));
    }

    public function malformedPayloads() {
        $ok = array('requestReferenceNumber' => '1', 'id' => 'x', 'status' => 'PAYMENT_SUCCESS', 'amount' => '1');
        return array(
            'null (bad json)' => array(null),
            'string' => array('PAYMENT_SUCCESS'),
            'empty' => array(array()),
            'no reference' => array(array_diff_key($ok, array('requestReferenceNumber' => 1))),
            'no id' => array(array_diff_key($ok, array('id' => 1))),
            'no status' => array(array_diff_key($ok, array('status' => 1))),
            'no amount' => array(array_diff_key($ok, array('amount' => 1))),
        );
    }

    public function test_complete_payload_is_accepted() {
        $this->assertTrue(Cynder_Paymaya_Webhook_Guard::is_valid_webhook_payload(array('requestReferenceNumber' => '1', 'id' => 'x', 'status' => 'PAYMENT_SUCCESS', 'amount' => '100')));
    }

    // ---- classify_lookup (normal orders: null -> 503, false -> 503, match -> complete) ----

    /** @dataProvider unreachableResponses */
    public function test_lookup_error_or_malformed_body_is_unreachable($response) {
        $this->assertSame(array('unreachable', null), $this->lookup($response));
    }

    public function unreachableResponses() {
        return array(
            'api error' => array(array('error' => 'Service unavailable')),
            'api error object' => array(array('error' => array('code' => 'PY0001'))),
            'null body' => array(null),
            'false (timeout)' => array(false),
            'string body' => array('<html>502</html>'),
        );
    }

    public function test_empty_lookup_is_no_match() {
        $this->assertSame(array('no_match', null), $this->lookup(array()));
    }

    public function test_only_a_failed_payment_is_no_match() {
        $this->assertSame(array('no_match', null), $this->lookup(array($this->payment(array('status' => 'PAYMENT_FAILED')))));
    }

    public function test_failed_attempt_then_successful_payment_matches() {
        $good = $this->payment(array('id' => 'good'));
        list($outcome, $matched) = $this->lookup(array($this->payment(array('id' => 'bad', 'status' => 'PAYMENT_FAILED')), $good));
        $this->assertSame('match', $outcome);
        $this->assertSame('good', $matched['id']);
    }

    public function test_wrong_currency_or_amount_is_no_match() {
        $this->assertSame('no_match', $this->lookup(array($this->payment(array('currency' => 'USD'))))[0]);
        $this->assertSame('no_match', $this->lookup(array($this->payment(array('amount' => '99'))))[0]);
    }

    // ---- classify_manual_capture ----

    public function test_manual_lookup_error_is_unreachable() {
        $this->assertSame(array('unreachable', null), $this->manual(array('error' => 'boom')));
        $this->assertSame(array('unreachable', null), $this->manual(null));
    }

    public function test_manual_with_no_payments_or_no_authorization_record_is_none() {
        $this->assertSame(array('none', null), $this->manual(array()));
        $this->assertSame(array('none', null), $this->manual(array($this->payment())));
    }

    public function test_manual_full_capture_with_success_webhook_completes() {
        list($outcome, $matched) = $this->manual(array($this->captured()));
        $this->assertSame('complete', $outcome);
        $this->assertSame('e732f996-cb87-4120-b712-166d8183c01d', $matched['id']);
    }

    public function test_manual_authorization_only_is_pending() {
        $this->assertSame('pending', $this->manual(array($this->captured(array('status' => 'AUTHORIZED', 'capturedAmount' => '0'))))[0]);
    }

    public function test_manual_partial_capture_is_pending() {
        $this->assertSame('pending', $this->manual(array($this->captured(array('capturedAmount' => '40'))))[0]);
    }

    /** A failure webhook can never complete an order, even if Maya reports it fully captured. */
    public function test_manual_failure_webhook_never_completes() {
        foreach (array('PAYMENT_FAILED', 'PAYMENT_EXPIRED', 'AUTH_FAILED') as $status) {
            $this->assertSame('pending', $this->manual(array($this->captured()), $status)[0]);
        }
    }

    /** Fully captured but not this order's payment: final 204, not a retry. */
    public function test_manual_fully_captured_but_mismatched_details_is_mismatch() {
        $this->assertSame('mismatch', $this->manual(array($this->captured(array('currency' => 'USD'))))[0]);
        $this->assertSame('mismatch', $this->manual(array($this->captured(array('amount' => '90', 'capturedAmount' => '90'))))[0]);
        $this->assertSame('mismatch', $this->manual(array($this->captured(array('requestReferenceNumber' => '9999'))))[0]);
    }

    /** A checkout retry leaves an extra, failed authorization record; it must not block or misdirect completion. */
    public function test_manual_retry_with_one_failed_and_one_captured_completes_the_captured_one() {
        $failed = $this->captured(array('id' => 'old', 'status' => 'PAYMENT_FAILED', 'capturedAmount' => '0'));
        list($outcome, $matched) = $this->manual(array($failed, $this->captured(array('id' => 'new'))));
        $this->assertSame('complete', $outcome);
        $this->assertSame('new', $matched['id']);
    }

    public function test_manual_two_confirmed_captured_payments_is_ambiguous() {
        $this->assertSame(array('ambiguous', null), $this->manual(array($this->captured(array('id' => 'a')), $this->captured(array('id' => 'b')))));
    }
}
