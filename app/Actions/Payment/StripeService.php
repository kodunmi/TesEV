<?php

namespace App\Actions\Payment;

use Stripe\StripeClient;

class StripeService
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient('sk_test_BQokikJOvBiI2HlWgH4olfQ2');
    }


    public function createCustomer($data)
    {
        try {
            $response =  $this->stripe->customers->create($data);

            return [
                "status" => true,
                "message" => "customer created successfully",
                "data" => $response
            ];
        } catch (\Throwable $th) {

            logError($th->getMessage());
            return [
                "status" => false,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
    }

    public function addCard($data)
    {
        $source = [
            'exp_month' => $data['exp_month'],
            'exp_year' => $data['exp_year'],
            'number' => $data['number'],
            'object' => 'card'
        ];

        try {
            $response =  $this->stripe->customers->createSource($data['customer_id'], ['source' => $source]);

            return [
                "status" => true,
                "message" => "card created successfully",
                "data" => $response
            ];
        } catch (\Throwable $th) {
            logError($th->getMessage());
            return [
                "status" => false,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
    }

    public function createPaymentIntent($amount, $customer_id, $card_id, $currency = 'usd', $setup_future_usage = 'off_session', $confirm = false)
    {

        try {
            $response = $this->stripe->paymentIntents->create([
                'amount' => $amount,
                'currency' => $currency,
                'confirm' => $confirm,
                'customer' => $customer_id,
                'payment_method' => $card_id,
                'setup_future_usage' => $setup_future_usage,
                'automatic_payment_methods' => [
                    'enabled' => true
                ]
            ]);

            return [
                "status" => true,
                "message" => "intent created successfully",
                "data" => $response
            ];
        } catch (\Throwable $th) {
            dd($th);
            logError($th->getMessage());
            return [
                "status" => false,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
    }

    public function confirmPaymentIntent($payment_intent)
    {

        try {

            $response = $this->stripe->paymentIntents->confirm(
                $payment_intent
            );

            return [
                "status" => true,
                "message" => "intent confirmed successfully",
                "data" => $response
            ];
        } catch (\Throwable $th) {
            dd($th);
            logError($th->getMessage());
            return [
                "status" => false,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
    }

    public function ephemeralKey($customer_id)
    {
        try {
            $response =  $this->stripe->ephemeralKeys->create(
                [
                    'customer' => $customer_id,

                ],
                [
                    'stripe_version' => '2023-08-16'
                ]
            );

            return [
                "status" => true,
                "message" => "key confirmed successfully",
                "data" => $response
            ];
        } catch (\Throwable $th) {
            dd($th);
            logError($th->getMessage());
            return [
                "status" => false,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
    }
}
