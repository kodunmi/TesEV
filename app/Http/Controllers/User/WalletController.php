<?php

namespace App\Http\Controllers\User;

use App\Actions\Payment\StripeService;
use App\Enum\ChargeTypeEnum;
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

        if (!$card) {
            return respondError("User does not have an active card");
        }

        $resp = $this->stripeService->chargeCard(
            $amount,
            $user->id,
            [
                'user_id' => $user->id,
                'type' => ChargeTypeEnum::WALLET_FUND->value
            ]
        );

        if (!$resp['status']) {
            return respondError($resp['message'], $resp['data']);
        }

        return respondSuccess($resp['message'], $resp['data']);
    }

    public function getBalance(Request $request)
    {

        return respondSuccess("Balance fetched successfully", [
            'balance' => centToDollar(auth()->user()->wallet)
        ]);
    }

    public function getTransactions(Request $request)
    {
        // Logic to get the transactions of the wallet
    }
}
