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

        $data = [
            'exp_month' => $validated['exp_month'],
            'exp_year' => $validated['exp_year'],
            'number' => $validated['number'],
            'customer_id' => $user->customer_id
        ];

        $response = $this->stripeService->addCard($data);

        if (!$response['status']) {
            return respondError($response['message'], null, 400);
        }

        $user->cards()->update([
            'is_default' => false,
        ]);

        $user->cards()->create([
            'card_id' =>  $response['data']->id,
            'last_four' =>  $response['data']->last4,
            'exp_year' => $validated['exp_year'],
            'exp_month' => $validated['exp_month'],
            'number' => $validated['number'],
            'is_default' => true,
            'is_active' => true,
            'public_id' => uuid(),
            'object' => json_decode($response['data']),
        ]);


        return respondSuccess($response['message'], $response['data']);
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
