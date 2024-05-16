<?php

namespace App\Actions\Payment;

use App\Enum\SubscriptionPaymentFrequencyEnum;
use App\Models\Package;
use App\Models\Product;
use App\Repositories\Core\CardRepository;
use App\Repositories\User\UserRepository;
use Laravel\Cashier\Cashier;
use Stripe\StripeClient;

class StripeService
{
    protected $stripe;

    public function __construct(
        protected CardRepository $cardRepository,
        protected UserRepository $userRepository
    ) {
        $this->stripe = Cashier::stripe();
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

            logError($th->getMessage(), ['error' => $th]);
            return [
                "status" => false,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
    }

    public function addCard($data)
    {
        try {
            $response =  $this->stripe->customers->createSource($data['stripe_id'], ['source' => $data['token_id']]);

            return [
                "status" => true,
                "message" => "card created successfully",
                "data" => $response
            ];
        } catch (\Throwable $th) {
            logError($th->getMessage(), ['error' => $th]);
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
            logError($th->getMessage(), ['error' => $th]);
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
            logError($th->getMessage(), ['error' => $th]);
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
            logError($th->getMessage(), ['error' => $th]);
            return [
                "status" => false,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
    }

    public function createProduct($name, $price, $frequency, $description, $hours)
    {
        try {
            if (!SubscriptionPaymentFrequencyEnum::tryFrom($frequency)) {
                return [
                    "status" => false,
                    "message" => 'Frequency not in option',
                    "data" => $frequency
                ];
            }

            $product = Product::all()->first();

            if (!$product) {

                $stripe_product = $this->stripe->products->create([
                    'name' => 'TesEv Premium'
                ]);


                $product = Product::create([
                    'name' => $stripe_product->name,
                    'stripe_id' => $stripe_product->id,
                    'description' => 'TesEv Premium product'
                ]);
            }

            $stripe_price = $this->stripe->prices->create([
                'product' => $product->stripe_id,
                'currency' => 'usd',
                'recurring' => [
                    'interval' => $frequency,
                    'interval_count' => 1
                ],
                'unit_amount' => dollarToCent($price),
                'metadata' => [
                    'hours' => $hours
                ]
            ]);


            $data = [
                'title' => $name,
                'description' => $description,
                'amount' => dollarToCent($price),
                'hours' => $hours,
                'frequency' => $frequency,
                'active' => true,
                'public_id' => uuid(),
                'stripe_id' => $stripe_price->id,
            ];


            $package = Package::create($data);

            return [
                "status" => true,
                "message" => 'Package created successfully',
                "data" => $package
            ];
        } catch (\Throwable $th) {
            logError($th->getMessage(), ['error' => $th]);
            return [
                "status" => false,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
    }

    public function setUpIntent($customer_id)
    {
        try {
            $response = $this->stripe->setupIntents->create([
                'customer' => $customer_id,

                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                "status" => true,
                "message" => "intent created successfully",
                "data" => $response
            ];
        } catch (\Throwable $th) {
            logError($th->getMessage(), ['error' => $th]);
            return [
                "status" => false,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
    }

    public function chargeCard($amount, $user_id, $meta = [])
    {

        try {

            $user = $this->userRepository->findById($user_id);

            if (!$user->activeCard) {
                return [
                    "status" => false,
                    "message" => "User does not have an active card",
                    "data" => null
                ];
            }

            $payment = $user->charge($amount, $user->activeCard->stripe_id, [
                'metadata' => $meta,
                'off_session' => true
            ]);

            return [
                "status" => true,
                "message" => "Transaction in progress",
                "data" => null
            ];
        } catch (\Throwable $th) {
            logError($th->getMessage(), ['error' => $th]);
            return [
                "status" => false,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
    }
}
