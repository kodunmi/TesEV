<?php

namespace App\Http\Controllers\User;

use App\Actions\Payment\StripeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\FundWalletRequest;
use App\Repositories\User\UserRepository;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository,
        protected StripeService $stripeService,
    ) {
    }

    public function fundWallet(FundWalletRequest $request)
    {

        $user = $this->userRepository->findById(auth()->id());
        $validated = (object) $request->validated();

        $amount = $validated->amount;

        $card = $user->activeCard;

        $payment_intent = $this->stripeService->createPaymentIntent(

        )
    }

    public function getBalance(Request $request)
    {
        // Logic to get the balance of the wallet
    }

    public function getTransactions(Request $request)
    {
        // Logic to get the transactions of the wallet
    }
}
