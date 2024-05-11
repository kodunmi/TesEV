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
        try {
            $user = $this->userRepository->findById(auth()->id());
            $validated = $request->validated();

            if (!$user->stripe_id) {
                $stripe_data = [
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone
                ];

                $user->createAsStripeCustomer($stripe_data);

                $user->refresh();
            }

            $data = [
                'token_id' => $validated['token_id'],
                'card_id' => $validated['card_id'],
                'stripe_id' => $user->stripe_id
            ];

            $response = $this->stripeService->addCard($data);

            if (!$response['status']) {
                return respondError($response['message'], null, 400);
            }

            $user->cards()->update([
                'is_default' => false,
            ]);

            $card = $user->cards()->create([
                'stripe_id' =>  $response['data']->id,
                'last_four' =>  $response['data']->last4,
                'is_default' => true,
                'is_active' => true,
                'public_id' => uuid(),
                'object' => $response['data'],
            ]);


            $setup_intent = $this->stripeService->setUpIntent(

                customer_id: $user->stripe_id,


            );

            if (!$setup_intent['status']) {
                return respondError($setup_intent['message'], null, 400);
            }

            $ephemeral_key = $this->stripeService->ephemeralKey($user->stripe_id);

            if (!$ephemeral_key['status']) {
                return respondError($ephemeral_key['message'], null, 400);
            }

            $card->refresh();

            $data = [
                'ephemeral_key' => $ephemeral_key['data']->secret,
                'customer_id' => $user->stripe_id,
                'setup_intent_id' => $setup_intent['data']->id,
                'client_secrete' =>  $setup_intent['data']->client_secret,
                'card' => $card
            ];

            return respondSuccess($response['message'], $data);
        } catch (\Throwable $th) {
            return respondError($th->getMessage(), null, 400);
        }
    }

    public function createSetUpIntent()
    {

        $user = $this->userRepository->findById(auth()->id());

        $setup_intent = $user->createSetupIntent();

        return respondSuccess('Setup  intent created successfully', $setup_intent);
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
}
