<?php

namespace App\Repository\Admin\Payment;

interface PaymentInterface
{
    public function createCheckoutSession($invoice);
    public function handleWebhook($payload, $sigHeader);
    public function getPayment($id);
    public function getPayments($request);
    public function refundPayment($paymentId, $amount = null);
}
