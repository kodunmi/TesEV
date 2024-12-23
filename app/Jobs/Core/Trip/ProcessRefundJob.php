<?php

// namespace App\Jobs\Core\Trip;


// use App\Enum\PaymentTypeEnum;
// use App\Enum\TransactionStatusEnum;
// use App\Enum\TransactionTypeEnum;
// use App\Enum\TripStatusEnum;
// use App\Models\Package;
// use App\Models\Transaction;
// use App\Models\Trip;
// use App\Models\TripSetting;
// use App\Models\TripTransaction;
// use App\Models\User;
// use App\Repositories\Core\TransactionRepository;
// use App\Repositories\User\UserRepository;
// use Aws\S3\Transfer;
// use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;
// use Illuminate\Queue\InteractsWithQueue;
// use Illuminate\Queue\SerializesModels;
// use Illuminate\Support\Carbon;
// use Illuminate\Support\Number;

// class ProcessRefundJob implements ShouldQueue
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
//     public function handle(): void
//     {
//         $settings = TripSetting::first();

//         $now_plus_grace_period = Carbon::now()->addHours($settings->cancellation_grace_hour);

//         $trip_transaction = TripTransaction::where('trip_id', $this->trip->id)->first();

//         if ($now_plus_grace_period->gt($this->trip->start_time)) {
//             // ride has exceeded grace period
//             $percentage_refund = 100 - $settings->late_cancellation_charge_percent;

//             foreach ($trip_transaction->transactions as  $transaction) {
//                 $this->processRefund($percentage_refund, $transaction);
//             }
//         } else {
//             foreach ($trip_transaction->transactions as  $transaction) {
//                 $this->processRefund(100, $transaction);
//             }
//         }
//     }

//     private function processRefund(int $percent, Transaction $transaction)
//     {
//         switch ($transaction->channel) {
//             case PaymentTypeEnum::SUBSCRIPTION->value:
//                 $this->processSubscriptionRefund($percent, $transaction);
//                 break;
//             case PaymentTypeEnum::WALLET->value:
//                 $this->processWalletRefund($percent, $transaction);
//                 break;
//             case PaymentTypeEnum::CARD->value:
//                 $this->processCardRefund($percent, $transaction);
//                 break;
//             default:
//                 # code...
//                 break;
//         }
//     }

//     private function processCardRefund(int $percent, Transaction $transaction)
//     {
//         $payment_id =  $transaction->object['id'];

//         $user = User::find($transaction->user_id);

//         $user->refund($payment_id, $percent);

//         $this->trip->update([
//             'status' => TripStatusEnum::CANCELED->value
//         ]);

//         $data = [
//             'user_id' => $user->id,
//             'amount' => $transaction->amount,
//             'total_amount' => $transaction->amount,
//             'title' => "Trip refund",
//             'narration' => "Refund of " . Number::currency(centToDollar($transaction->amount))  . " for trip " . $this->trip->booking_id,
//         ];

//         $this->createTransactionRecord($data, TransactionTypeEnum::TRIP, PaymentTypeEnum::CARD, $transaction);
//     }

//     private function processWalletRefund(int $percent, Transaction $transaction)
//     {
//         $user = User::find($transaction->user_id);

//         $amount = $transaction->amount;

//         $percentage_of_amount = $percent * $amount / 100;

//         $user->update([
//             'wallet' => $user->wallet + $percentage_of_amount
//         ]);

//         $this->trip->update([
//             'status' => TripStatusEnum::CANCELED->value
//         ]);

//         $data = [
//             'user_id' => $user->id,
//             'amount' => $transaction->amount,
//             'total_amount' => $transaction->amount,
//             'title' => "Trip refund",
//             'narration' => "Refund of " . Number::currency(centToDollar($transaction->amount))  . " for trip " . $this->trip->booking_id,
//         ];

//         $this->createTransactionRecord($data, TransactionTypeEnum::TRIP, PaymentTypeEnum::WALLET, $transaction);
//     }

//     private function processSubscriptionRefund(int $percent, Transaction $transaction)
//     {
//         $user = User::find($transaction->user_id);

//         $amount = $transaction->amount;

//         $percentage_of_amount = $percent * $amount / 100;

//         $user->update([
//             'subscription_balance' => $user->subscription_balance + $percentage_of_amount
//         ]);

//         $this->trip->update([
//             'status' => TripStatusEnum::CANCELED->value
//         ]);

//         $data = [
//             'user_id' => $user->id,
//             'amount' => $transaction->amount,
//             'total_amount' => $transaction->amount,
//             'title' => "Trip refund",
//             'narration' => "Refund of " . Number::currency(centToDollar($transaction->amount))  . " for trip " . $this->trip->booking_id,
//         ];

//         $this->createTransactionRecord($data, TransactionTypeEnum::TRIP, PaymentTypeEnum::SUBSCRIPTION, $transaction);
//     }

//     private function createTransactionRecord(array $data, TransactionTypeEnum $transactionTypeEnum, PaymentTypeEnum $paymentTypeEnum, $payment)
//     {

//         $transactionRepository = new TransactionRepository;

//         $transaction = $transactionRepository->create(
//             [
//                 'user_id' => $data['user_id'],
//                 'amount' => $data['amount'],
//                 'total_amount' => $data['total_amount'],
//                 'title' => $data['title'],
//                 'narration' => $data['narration'],
//                 'status' => TransactionStatusEnum::SUCCESSFUL->value,
//                 'type' => $transactionTypeEnum->value,
//                 'entry' => "credit",
//                 'channel' => $paymentTypeEnum->value,
//                 'tax_amount' => 0.0,
//                 'tax_percentage' => 0
//             ]
//         );

//         $payment->transactions()->save($transaction);
//     }
// }




namespace App\Jobs\Core\Trip;

use App\Enum\PaymentTypeEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use App\Enum\TripStatusEnum;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\TripSetting;
use App\Models\TripTransaction;
use App\Models\User;
use App\Repositories\Core\TransactionRepository;
use App\Repositories\User\UserRepository;
use Aws\S3\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;

class ProcessRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Trip $trip)
    {
        Log::info('ProcessRefundJob created for trip', [
            'trip_id' => $this->trip->id,
            'booking_id' => $this->trip->booking_id
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $settings = TripSetting::first();

            if (!$settings) {
                Log::error('No trip settings found', [
                    'trip_id' => $this->trip->id
                ]);
                return;
            }

            $now_plus_grace_period = Carbon::now()->addHours($settings->cancellation_grace_hour);

            $trip_transaction = TripTransaction::where('trip_id', $this->trip->id)->first();

            if (!$trip_transaction) {
                Log::error('No trip transaction found', [
                    'trip_id' => $this->trip->id
                ]);
                return;
            }

            Log::info('Processing refund for trip', [
                'trip_id' => $this->trip->id,
                'booking_id' => $this->trip->booking_id,
                'grace_period_end' => $now_plus_grace_period,
                'trip_start_time' => $this->trip->start_time
            ]);

            if ($now_plus_grace_period->gt($this->trip->start_time)) {
                // ride has exceeded grace period
                $percentage_refund = 100 - $settings->late_cancellation_charge_percent;
                Log::info('Late cancellation detected', [
                    'percentage_refund' => $percentage_refund
                ]);

                foreach ($trip_transaction->transactions as $transaction) {
                    $this->processRefund($percentage_refund, $transaction);
                }
            } else {
                foreach ($trip_transaction->transactions as $transaction) {
                    $this->processRefund(100, $transaction);
                }
            }

            Log::info('Refund process completed successfully', [
                'trip_id' => $this->trip->id,
                'booking_id' => $this->trip->booking_id
            ]);
        } catch (\Exception $e) {
            Log::error('Refund process failed', [
                'trip_id' => $this->trip->id,
                'booking_id' => $this->trip->booking_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function processRefund(int $percent, Transaction $transaction)
    {
        Log::info('Processing refund for transaction', [
            'transaction_id' => $transaction->id,
            'channel' => $transaction->channel,
            'percent' => $percent
        ]);

        try {
            switch ($transaction->channel) {
                case PaymentTypeEnum::SUBSCRIPTION->value:
                    $this->processSubscriptionRefund($percent, $transaction);
                    break;
                case PaymentTypeEnum::WALLET->value:
                    $this->processWalletRefund($percent, $transaction);
                    break;
                case PaymentTypeEnum::CARD->value:
                    $this->processCardRefund($percent, $transaction);
                    break;
                default:
                    Log::warning('Unsupported payment channel', [
                        'channel' => $transaction->channel,
                        'transaction_id' => $transaction->id
                    ]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Refund processing failed for transaction', [
                'transaction_id' => $transaction->id,
                'channel' => $transaction->channel,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function processCardRefund(int $percent, Transaction $transaction)
    {
        $payment_id = $transaction->object['id'];

        $user = User::find($transaction->user_id);

        if (!$user) {
            Log::error('User not found for card refund', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id
            ]);
            return;
        }

        Log::info('Processing card refund', [
            'payment_id' => $payment_id,
            'user_id' => $user->id,
            'percent' => $percent
        ]);

        $user->refund($payment_id, $percent);

        $this->trip->update([
            'status' => TripStatusEnum::CANCELED->value
        ]);

        $data = [
            'user_id' => $user->id,
            'amount' => $transaction->amount,
            'total_amount' => $transaction->amount,
            'title' => "Trip refund",
            'narration' => "Refund of " . Number::currency(centToDollar($transaction->amount))  . " for trip " . $this->trip->booking_id,
        ];

        $this->createTransactionRecord($data, TransactionTypeEnum::TRIP, PaymentTypeEnum::CARD, $transaction);
    }

    private function processWalletRefund(int $percent, Transaction $transaction)
    {
        $user = User::find($transaction->user_id);

        if (!$user) {
            Log::error('User not found for wallet refund', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id
            ]);
            return;
        }

        $amount = $transaction->amount;
        $percentage_of_amount = $percent * $amount / 100;

        Log::info('Processing wallet refund', [
            'user_id' => $user->id,
            'original_amount' => $amount,
            'refund_percent' => $percent,
            'refund_amount' => $percentage_of_amount
        ]);

        $user->update([
            'wallet' => $user->wallet + $percentage_of_amount
        ]);

        $this->trip->update([
            'status' => TripStatusEnum::CANCELED->value
        ]);

        $data = [
            'user_id' => $user->id,
            'amount' => $transaction->amount,
            'total_amount' => $transaction->amount,
            'title' => "Trip refund",
            'narration' => "Refund of " . Number::currency(centToDollar($transaction->amount))  . " for trip " . $this->trip->booking_id,
        ];

        $this->createTransactionRecord($data, TransactionTypeEnum::TRIP, PaymentTypeEnum::WALLET, $transaction);
    }

    private function processSubscriptionRefund(int $percent, Transaction $transaction)
    {
        $user = User::find($transaction->user_id);

        if (!$user) {
            Log::error('User not found for subscription refund', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id
            ]);
            return;
        }

        $amount = $transaction->amount;
        $percentage_of_amount = $percent * $amount / 100;

        Log::info('Processing subscription refund', [
            'user_id' => $user->id,
            'original_amount' => $amount,
            'refund_percent' => $percent,
            'refund_amount' => $percentage_of_amount
        ]);

        $user->update([
            'subscription_balance' => $user->subscription_balance + $percentage_of_amount
        ]);

        $this->trip->update([
            'status' => TripStatusEnum::CANCELED->value
        ]);

        $data = [
            'user_id' => $user->id,
            'amount' => $transaction->amount,
            'total_amount' => $transaction->amount,
            'title' => "Trip refund",
            'narration' => "Refund of " . Number::currency(centToDollar($transaction->amount))  . " for trip " . $this->trip->booking_id,
        ];

        $this->createTransactionRecord($data, TransactionTypeEnum::TRIP, PaymentTypeEnum::SUBSCRIPTION, $transaction);
    }

    private function createTransactionRecord(array $data, TransactionTypeEnum $transactionTypeEnum, PaymentTypeEnum $paymentTypeEnum, $payment)
    {
        Log::info('Creating transaction record', [
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'transaction_type' => $transactionTypeEnum->value,
            'payment_type' => $paymentTypeEnum->value
        ]);

        $transactionRepository = new TransactionRepository;

        $transaction = $transactionRepository->create(
            [
                'user_id' => $data['user_id'],
                'amount' => $data['amount'],
                'total_amount' => $data['total_amount'],
                'title' => $data['title'],
                'narration' => $data['narration'],
                'status' => TransactionStatusEnum::SUCCESSFUL->value,
                'type' => $transactionTypeEnum->value,
                'entry' => "credit",
                'channel' => $paymentTypeEnum->value,
                'tax_amount' => 0.0,
                'tax_percentage' => 0
            ]
        );

        $payment->transactions()->save($transaction);

        Log::info('Transaction record created successfully', [
            'transaction_id' => $transaction->id
        ]);
    }
}
