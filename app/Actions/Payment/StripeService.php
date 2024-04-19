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
}
