<?php

namespace App\Services\Core;


use App\Actions\Notifications\NotificationService;
use App\Enum\SubscriptionPaymentFrequencyEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use App\Models\Package;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Repositories\Core\PackageRepository;
use App\Repositories\Core\TransactionRepository;
use App\Repositories\User\UserRepository;
use Carbon\Carbon;

class SubscriptionService
{
    public function __construct(
        protected PackageRepository $packageRepository,
        protected TransactionRepository $transactionRepository,
        protected UserRepository $userRepository
    ) {
    }

    public function packages()
    {
        return $this->packageRepository->all();
    }

    public function subscribe($package_id)
    {
        $user = $this->userRepository->findById(auth()->id());

        $subscription = $this->packageRepository->findById($package_id);

        if (!$subscription) {
            return [
                'status' => false,
                'code' => 400,
                'message' => 'subscription not found',
                'data' => null,
            ];
        }

        $subscribed = $user->subscriptions()->find($package_id);

        if ($subscribed) {
            return [
                'status' => false,
                'code' => 400,
                'message' => "User already subscribed to $subscribed->title",
                'data' => $subscribed,
            ];
        }

        if (!$subscription->active) {
            return [
                'status' => false,
                'code' => 400,
                'message' => "$subscription->title not active",
                'data' => $subscription,
            ];
        }

        $charge = $this->chargeForSubscription($subscription, $user, $this->transactionRepository);

        if ($charge['status']) {
            logInfo("charge is successful for subscription:$subscription->id");

            $current_date = Carbon::now();
            $due_date = $subscription->frequency == SubscriptionPaymentFrequencyEnum::ANNUALLY->value
                ? $current_date->copy()->addYear()
                : $current_date->copy()->addMonth();

            $subscription_attributes = [
                'subscribed_at' => $current_date,
                'due_at' => $due_date,
                'frequency' => $subscription->frequency,
                'balance' => $subscription->amount
            ];

            if ($user->unsubscribeSubscriptions()->find($package_id)) {
                $user->unsubscribeSubscriptions()->updateExistingPivot($package_id, [
                    'unsubscribed_at' => null,
                    'subscribed_at' => $current_date,
                    'due_at' => $due_date,
                    'frequency' => $subscription->frequency,
                    'balance' => $subscription->amount
                ]);
            } else {
                $user->subscriptions()->attach($package_id, $subscription_attributes);
            }

            $this->userRepository->updateUser($user->id, [
                'wallet' => $user->wallet + $subscription->amount
            ]);

            $notification = new NotificationService(auth()->user());

            $notification->setSubject("You have successfully subscribed for $subscription->title")
                ->setData([
                    'subscription_name' => $subscription->title,
                    'subscribed_at' => $current_date->toDateString(),
                    'due_at' => $due_date->toDateString(),
                    'frequency' => $subscription->frequency,
                    'payment_reference' => $charge['data']->reference,
                    'service_name' => $subscription->title,
                    'service_amount' => $subscription->amount,
                ])
                ->setView('email.school.subscription.subscription_receipt')
                ->sendEmail();

            return [
                'status' => true,
                'code' => 200,
                'message' => "You have successfully subscribed to $subscription->title",
                'data' => $user->subscriptions()->find($package_id),
            ];
        }
    }

    public function unsubscribe($package_id)
    {
        $user = $this->userRepository->findById(auth()->id());

        $subscription = $this->packageRepository->findById($package_id);

        if (!$subscription) {
            return [
                'status' => false,
                'code' => 400,
                'message' => 'subscription not found',
                'data' => null,
            ];
        }

        $subscribed = $user->subscriptions()->find($package_id);

        if (!$subscribed) {
            return [
                'status' => false,
                'code' => 400,
                'message' => "User not subscribed to $subscription->title",
                'data' => $subscription,
            ];
        }

        $current_date = Carbon::now();

        $unsubscribe_from_service = $user->subscriptions()->updateExistingPivot($package_id, [
            'unsubscribed_at' => $current_date,
        ]);

        if (!$unsubscribe_from_service) {
            return [
                'status' => false,
                'code' => 400,
                'message' => "Error unsubscribing from $subscription->title",
                'data' => $subscription,
            ];
        }

        $notification = new NotificationService(auth()->user());

        $notification->setSubject("You have successfully unsubscribe from $subscription->title")
            ->setData([
                'subscription_name' => $subscription->title,
                'unsubscribed_at' => $current_date,
                'service_name' => $subscription->title,
            ])
            ->setView('email.school.subscription.subscription_downgrade')
            ->sendEmail();

        return [
            'status' => true,
            'code' => 200,
            'message' => "You have successfully unsubscribed from $subscription->title",
            'data' => $subscription,
        ];
    }


    public function chargeForSubscription(Package $package, User $user, TransactionRepository $transactionRepository)
    {
        $payment = SubscriptionTransaction::create([
            'subscribed_by' => $user->id,
            'package_id' => $package->id,
            'reference' => generateReference(),
            'amount' => $package->amount,
            'public_id' => uuid(),
        ]);

        $transaction = $transactionRepository->create(
            [
                'amount' => $payment->amount,
                'title' => "Subscription payment",
                'narration' => "Subscription to {$package->title}",
                'status' => TransactionStatusEnum::PENDING->value,
                'type' => TransactionTypeEnum::SUBSCRIPTION->value,
                'entry' => "debit",
                'channel' => 'web',
            ]
        );

        $payment->transaction()->save($transaction);


        // TODO: Do the actual charge of the account
        $successful_transfer = [
            'status' => true,
        ];

        if (!$successful_transfer['status']) {
            logInfo("Transaction not successful, package:$package->id");

            logInfo("Update transaction to fail, package:$package->id");

            $payment->transaction->status = TransactionStatusEnum::FAILED->value;
            $payment->transaction->save();

            return [
                'status' => false,
                'message' => 'Error initiating transaction',
                'data' => $transaction,
            ];
        }

        logInfo("Transaction successful, package:$package->id");

        logInfo("Update transaction to successful, package:$package->id");

        $payment->transaction->status = TransactionStatusEnum::SUCCESSFUL->value;
        $payment->transaction->save();

        return [
            'status' => true,
            'message' => "Transaction is {$transaction->status}",
            'data' => $transaction,
        ];
    }

    public function transactions()
    {

        try {
            $school = auth()->user()->activeSchool;

            $transactions_query = $this->transactionRepository->query()
                ->where('owner_id', $school->id)
                ->where('type', TransactionTypeEnum::SUBSCRIPTION->value)
                ->with(['user']);

            return [
                'status' => true,
                'message' => 'Transaction successful',
                'data' => $transactions_query->paginate(10),
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
                'data' => null,
            ];
        }
    }
}
