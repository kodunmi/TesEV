<?php

namespace App\Jobs\Core\Trip;

use App\Actions\Payment\StripeService;
use App\Enum\ChargeTypeEnum;
use App\Enum\PaymentTypeEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TripStatusEnum;
use App\Enum\TripTransactionTypeEnum;
use App\Models\Trip;
use App\Models\User;
use App\Services\Core\TripService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessExtraTimePaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Trip $trip)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(StripeService $stripeService): void
    {
        $trip = $this->trip;
        $trip_transactions = $trip->tripTransactions()->where('type', TripTransactionTypeEnum::EXTRA_TIME->value)->get();
        $user = User::find($trip_transactions->trip->user_id);

        foreach ($trip_transactions as $trip_transaction) {
            if ($trip_transaction->transactions->count() > 1) {
                foreach ($trip_transaction->transactions as  $transaction) {

                    if ($transaction->channel == PaymentTypeEnum::CARD->value) {
                        // charge card async
                        $charge_card = $stripeService->chargeCard(
                            $transaction->total_amount,
                            $user->id,
                            [
                                'trip_id' => $trip->id,
                                'trip_transaction_id' => $trip_transaction->id,
                                'type' => ChargeTypeEnum::TRIP_FUND->value,
                                'subscription_balance' => $user->subscription_balance - $transaction->total_amount
                            ]
                        );

                        if (!$charge_card['status']) {

                            updateTripStatus($trip, $trip_transaction, TripStatusEnum::CANCELED, TransactionStatusEnum::FAILED);

                            logError("Error occurred @ processPayment method inside EndTripsJob", [
                                'error' => $charge_card['message'],
                                'message' => $charge_card['message'],
                                'data' => $charge_card['data']
                            ]);
                        }

                        $transaction->update([
                            'object' => $charge_card['data']
                        ]);
                    }
                }
            } else {
                foreach ($trip_transaction->transactions as  $transaction) {
                    if ($transaction->channel == PaymentTypeEnum::CARD->value) {
                        $charge_card = $stripeService->chargeCard(
                            $transaction->total_amount,
                            $user->id,
                            [
                                'trip_id' => $trip->id,
                                'trip_transaction_id' => $trip_transaction->id,
                                'type' => ChargeTypeEnum::TRIP_FUND->value,
                            ]
                        );

                        if (!$charge_card['status']) {

                            updateTripStatus($trip, $trip_transaction, TripStatusEnum::CANCELED, TransactionStatusEnum::FAILED);

                            logError("Error occurred @ processPayment method inside EndTripsJob", [
                                'error' => $charge_card['message'],
                                'message' => $charge_card['message'],
                                'data' => $charge_card['data']
                            ]);
                        }

                        $transaction->update([
                            'object' => $charge_card['data']
                        ]);
                    }
                }
            }
        }
    }
}
