<?php

namespace App\Jobs\Core;

use App\Enum\PaymentTypeEnum;
use App\Enum\TransactionTypeEnum;
use App\Enum\TripStatusEnum;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\TripSetting;
use App\Models\TripTransaction;
use App\Models\User;
use App\Repositories\User\UserRepository;
use Aws\S3\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ProcessRefundJob implements ShouldQueue
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
    public function handle(): void
    {
        $settings = TripSetting::first();

        $now_plus_grace_period = Carbon::now()->addHours($settings->cancellation_grace_hour);

        $trip_transaction = TripTransaction::where('trip_id', $this->trip->id)->first();

        if ($now_plus_grace_period->gt($this->trip->start_time)) {
            // ride has exceeded grace period
            $percentage_refund = 100 - $settings->late_cancellation_charge_percent;

            foreach ($trip_transaction->transactions as  $transaction) {
                $this->processRefund($percentage_refund, $transaction);
            }
        } else {
            foreach ($trip_transaction->transactions as  $transaction) {
                $this->processRefund(100, $transaction);
            }
        }
    }

    private function processRefund(int $percent, Transaction $transaction)
    {
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
                # code...
                break;
        }
    }

    private function processCardRefund(int $percent, Transaction $transaction)
    {
        $payment_id =  $transaction->object['id'];

        $user = User::find($transaction->user_id);

        $user->refund($payment_id);

        $this->trip->update([
            'status' => TripStatusEnum::CANCELED->value
        ]);
    }

    private function processWalletRefund(int $percent, Transaction $transaction)
    {
        $user = User::find($transaction->user_id);

        $amount = $transaction->amount;

        $percentage_of_amount = $percent * $amount / 100;

        $user->update([
            'wallet' => $user->wallet + $percentage_of_amount
        ]);

        $this->trip->update([
            'status' => TripStatusEnum::CANCELED->value
        ]);
    }
    private function processSubscriptionRefund(int $percent, Transaction $transaction)
    {
        $user = User::find($transaction->user_id);

        $amount = $transaction->amount;

        $percentage_of_amount = $percent * $amount / 100;

        $user->update([
            'subscription_balance' => $user->subscription_balance + $percentage_of_amount
        ]);

        $this->trip->update([
            'status' => TripStatusEnum::CANCELED->value
        ]);
    }
}
