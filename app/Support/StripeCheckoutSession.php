<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Email cliente da una Checkout Session Stripe (oggetto API o payload webhook).
 */
final class StripeCheckoutSession
{
    public static function customerEmail(object|array $session): ?string
    {
        if (is_array($session)) {
            $e = $session['customer_email'] ?? null;
            if (is_string($e) && trim($e) !== '') {
                return strtolower(trim($e));
            }
            $fromDetails = data_get($session, 'customer_details.email');
            if (is_string($fromDetails) && trim($fromDetails) !== '') {
                return strtolower(trim($fromDetails));
            }

            $fromCustomer = data_get($session, 'customer.email');
            if (is_string($fromCustomer) && trim($fromCustomer) !== '') {
                return strtolower(trim($fromCustomer));
            }

            return null;
        }

        $e = $session->customer_email ?? null;
        if (is_string($e) && trim($e) !== '') {
            return strtolower(trim($e));
        }

        $details = $session->customer_details ?? null;
        if ($details !== null && isset($details->email) && is_string($details->email) && trim($details->email) !== '') {
            return strtolower(trim($details->email));
        }

        $customer = $session->customer ?? null;
        if (is_object($customer) && isset($customer->email) && is_string($customer->email) && trim($customer->email) !== '') {
            return strtolower(trim($customer->email));
        }

        return null;
    }
}
