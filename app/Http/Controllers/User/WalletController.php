<?php

namespace App\Http\Controllers\User;

use App\Actions\Payment\StripeService;
use App\Enum\ChargeTypeEnum;
use App\Enum\PaymentTypeEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\FundWalletRequest;
use App\Models\WalletTransaction;
use App\Repositories\Core\TransactionRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository,
        protected StripeService $stripeService,
        protected TransactionRepository $transactionRepository,
    ) {
    }

    public function fundWallet(FundWalletRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->findById(auth()->id());
            $validated = (object)$request->validated();

            $amount = $validated->amount;
            $amount = dollarToCent($amount);

            $card = $user->activeCard;

            if (!$card) {
                return respondError("User does not have an active card");
            }

            $wallet_transaction = WalletTransaction::create([
                'user_id' => auth()->id(),
                'reference' => generateReference(),
                'public_id' => uuid(),
                'status' => TransactionStatusEnum::PENDING->value,
                'amount' => $amount,
            ]);

            $transaction = $this->transactionRepository->create(
                [
                    'amount' => $amount,
                    'title' => "Wallet funding",
                    'narration' => "Wallet funding with " . centToDollar($amount),
                    'status' => TransactionStatusEnum::PENDING->value,
                    'type' => TransactionTypeEnum::WALLET->value,
                    'entry' => "debit",
                    'channel' => PaymentTypeEnum::CARD->value,
                ]
            );

            $wallet_transaction->transaction()->save($transaction);

            $resp = $this->stripeService->chargeCard(
                $amount,
                $user->id,
                [
                    'user_id' => $user->id,
                    'wallet_transaction_id' => $wallet_transaction->id,
                    'type' => ChargeTypeEnum::WALLET_FUND->value
                ]
            );

            if (!$resp['status']) {
                return respondError($resp['message'], $resp['data']);
            }

            DB::commit();

            return respondSuccess($resp['message'], $resp['data']);
        } catch (\Exception $e) {
            DB::rollBack();

            logError("Error occurred @ wallet funding controller", [
                'error' => $e
            ]);
            return respondError('Transaction failed');
        }
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
