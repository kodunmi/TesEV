<?php

namespace App\Http\Controllers\V1\User;

use App\Actions\Payment\StripeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\AddCardRequest;
use App\Repositories\Core\CardRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function __construct(
        protected StripeService $stripeService,
        protected UserRepository $userRepository,
        protected CardRepository $cardRepository
    ) {
    }
    public function addCard(AddCardRequest $request)
    {
        $user = $this->userRepository->findById(auth()->id());
        $validated = $request->validated();

        if (!$user->customer_id) {
            $stripe_data = [
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone
            ];

            $created_stripe_customer = $this->stripeService->createCustomer($stripe_data);

            if (!$created_stripe_customer['status']) {
                return respondError("We cannot add card for user at the moment");
            }

            $update = $this->userRepository->updateUser($user->id, [
                'customer_id' => $created_stripe_customer['data']->id
            ]);

            if (!$update) {
                return respondError("We cannot add card for user at the moment");
            }

            $user->refresh();
        }

        $data = [
            'token_id' => $validated['token_id'],
            'card_id' => $validated['card_id'],
            'customer_id' => $user->customer_id
        ];

        $response = $this->stripeService->addCard($data);

        if (!$response['status']) {
            return respondError($response['message'], null, 400);
        }

        $user->cards()->update([
            'is_default' => false,
        ]);

        $card = $user->cards()->create([
            'card_id' =>  $response['data']->id,
            'last_four' =>  $response['data']->last4,
            'is_default' => true,
            'is_active' => true,
            'public_id' => uuid(),
            'object' => $response['data'],
        ]);


        $payment_intent = $this->stripeService->createPaymentIntent(
            amount: 50,
            customer_id: $user->customer_id,
            card_id: $card->card_id,

        );

        if (!$payment_intent['status']) {
            return respondError($payment_intent['message'], null, 400);
        }

        $ephemeral_key = $this->stripeService->ephemeralKey($user->customer_id);

        if (!$ephemeral_key['status']) {
            return respondError($ephemeral_key['message'], null, 400);
        }

        $card->refresh();

        $data = [
            'ephemeral_key' => $ephemeral_key['data']->secret,
            'customer_id' => $user->customer_id,
            'payment_intent_id' => $payment_intent['data']->id,
            'client_secrete' =>  $payment_intent['data']->client_secret,
            'card' => $card
        ];

        return respondSuccess($response['message'], $data);
    }

    public function getCards()
    {
        $user = $this->userRepository->findById(auth()->id());
        $cards = $user->cards()->paginate(10);

        return respondSuccess('Cards fetched successfully', $cards);
    }

    public function getDefaultCard()
    {
        $user = $this->userRepository->findById(auth()->id());
        $card = $user->cards()->where('is_default', true)->first();

        return respondSuccess('Default card fetched successfully', $card);
    }

    public function createCustomer()
    {
    }

    public function updateCustomer()
    {
    }

    public function getReceipts()
    {
    }

    public function getReceipt()
    {
    }
}
