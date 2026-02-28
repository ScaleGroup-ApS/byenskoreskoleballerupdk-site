<?php

namespace App\Actions\Enrollment;

use App\Models\Enrollment;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class CreateStripeCheckoutSession
{
    public function handle(Enrollment $enrollment): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $enrollment->load('offer');

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'dkk',
                        'unit_amount' => (int) ($enrollment->offer->price * 100),
                        'product_data' => [
                            'name' => $enrollment->offer->name,
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => route('enrollment.stripe-return').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('enrollment.show', $enrollment->offer_id),
            'metadata' => [
                'enrollment_id' => $enrollment->id,
            ],
        ]);

        $enrollment->update(['stripe_session_id' => $session->id]);

        return $session->url;
    }
}
