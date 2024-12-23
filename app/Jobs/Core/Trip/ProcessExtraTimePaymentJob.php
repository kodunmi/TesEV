<?php

// namespace App\Jobs\Core\Trip;

// use App\Actions\Payment\StripeService;
// use App\Enum\ChargeTypeEnum;
// use App\Enum\PaymentTypeEnum;
// use App\Enum\TransactionStatusEnum;
// use App\Enum\TripStatusEnum;
// use App\Enum\TripTransactionTypeEnum;
// use App\Models\Trip;
// use App\Models\User;
// use App\Services\Core\TripService;
// use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;
// use Illuminate\Queue\InteractsWithQueue;
// use Illuminate\Queue\SerializesModels;

// class ProcessExtraTimePaymentJob implements ShouldQueue
// {
//     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//     /**
//      * Create a new job instance.
//      */
//     public function __construct(protected Trip $trip)
//     {
//         //
//     }

//     /**
//      * Execute the job.
//      */
//     public function handle(StripeService $stripeService): void
//     {
//         $trip = $this->trip;
//         $trip_transactions = $trip->tripTransactions()->where('type', TripTransactionTypeEnum::EXTRA_TIME->value)->get();
//         $user = User::find($trip_transactions->trip->user_id);

//         foreach ($trip_transactions as $trip_transaction) {
//             if ($trip_transaction->transactions->count() > 1) {
//                 foreach ($trip_transaction->transactions as  $transaction) {

//                     if ($transaction->channel == PaymentTypeEnum::CARD->value) {
//                         // charge card async
//                         $charge_card = $stripeService->chargeCard(
//                             $transaction->total_amount,
//                             $user->id,
//                             [
//                                 'trip_id' => $trip->id,
//                                 'trip_transaction_id' => $trip_transaction->id,
//                                 'type' => ChargeTypeEnum::TRIP_FUND->value,
//                                 'subscription_balance' => $user->subscription_balance - $transaction->total_amount
//                             ]
//                         );

//                         if (!$charge_card['status']) {

//                             updateTripStatus($trip, $trip_transaction, TripStatusEnum::CANCELED, TransactionStatusEnum::FAILED);

//                             logError("Error occurred @ processPayment method inside EndTripsJob", [
//                                 'error' => $charge_card['message'],
//                                 'message' => $charge_card['message'],
//                                 'data' => $charge_card['data']
//                             ]);
//                         }

//                         $transaction->update([
//                             'object' => $charge_card['data']
//                         ]);
//                     }
//                 }
//             } else {
//                 foreach ($trip_transaction->transactions as  $transaction) {
//                     if ($transaction->channel == PaymentTypeEnum::CARD->value) {
//                         $charge_card = $stripeService->chargeCard(
//                             $transaction->total_amount,
//                             $user->id,
//                             [
//                                 'trip_id' => $trip->id,
//                                 'trip_transaction_id' => $trip_transaction->id,
//                                 'type' => ChargeTypeEnum::TRIP_FUND->value,
//                             ]
//                         );

//                         if (!$charge_card['status']) {

//                             updateTripStatus($trip, $trip_transaction, TripStatusEnum::CANCELED, TransactionStatusEnum::FAILED);

//                             logError("Error occurred @ processPayment method inside EndTripsJob", [
//                                 'error' => $charge_card['message'],
//                                 'message' => $charge_card['message'],
//                                 'data' => $charge_card['data']
//                             ]);
//                         }

//                         $transaction->update([
//                             'object' => $charge_card['data']
//                         ]);
//                     }
//                 }
//             }
//         }
//     }
// }



namespace App\Jobs\Core\Trip;

use Illuminate\Support\Facades\Log;
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
        Log::info('ProcessExtraTimePaymentJob constructed', [
            'trip_id' => $this->trip->id
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(StripeService $stripeService): void
    {
        Log::info('Processing extra time payment', [
            'trip_id' => $this->trip->id
        ]);

        try {
            $trip = $this->trip;
            $trip_transactions = $trip->tripTransactions()->where('type', TripTransactionTypeEnum::EXTRA_TIME->value)->get();

            Log::info('Extra time transactions found', [
                'trip_id' => $trip->id,
                'transaction_count' => $trip_transactions->count()
            ]);

            $user = User::find($trip_transactions->first()->trip->user_id);

            if (!$user) {
                Log::error('User not found for trip', [
                    'trip_id' => $trip->id
                ]);
                return;
            }

            foreach ($trip_transactions as $trip_transaction) {
                Log::info('Processing trip transaction', [
                    'trip_transaction_id' => $trip_transaction->id
                ]);

                $transactionsCount = $trip_transaction->transactions->count();
                Log::info('Transaction count', [
                    'transaction_count' => $transactionsCount
                ]);

                foreach ($trip_transaction->transactions as $transaction) {
                    if ($transaction->channel == PaymentTypeEnum::CARD->value) {
                        Log::info('Processing card payment', [
                            'transaction_id' => $transaction->id,
                            'amount' => $transaction->total_amount
                        ]);

                        $chargeParams = [
                            'trip_id' => $trip->id,
                            'trip_transaction_id' => $trip_transaction->id,
                            'type' => ChargeTypeEnum::TRIP_FUND->value,
                        ];

                        // Add subscription balance only if transactions count > 1
                        if ($transactionsCount > 1) {
                            $chargeParams['subscription_balance'] = $user->subscription_balance - $transaction->total_amount;
                        }

                        $charge_card = $stripeService->chargeCard(
                            $transaction->total_amount,
                            $user->id,
                            $chargeParams
                        );

                        if (!$charge_card['status']) {
                            Log::error('Card charge failed', [
                                'trip_id' => $trip->id,
                                'transaction_id' => $transaction->id,
                                'error_message' => $charge_card['message']
                            ]);

                            updateTripStatus($trip, $trip_transaction, TripStatusEnum::CANCELED, TransactionStatusEnum::FAILED);

                            logError("Error occurred @ processPayment method inside EndTripsJob", [
                                'error' => $charge_card['message'],
                                'message' => $charge_card['message'],
                                'data' => $charge_card['data']
                            ]);
                        } else {
                            Log::info('Card charge successful', [
                                'trip_id' => $trip->id,
                                'transaction_id' => $transaction->id
                            ]);
                        }

                        $transaction->update([
                            'object' => $charge_card['data']
                        ]);
                    }
                }
            }

            Log::info('Extra time payment processing completed', [
                'trip_id' => $trip->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProcessExtraTimePaymentJob', [
                'trip_id' => $this->trip->id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
