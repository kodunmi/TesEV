<?php

namespace App\Services\Core;


use App\Actions\Notifications\NotificationService;
use App\Enum\SubscriptionPaymentFrequencyEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use App\Models\Package;
use App\Models\Product;
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
        try {
            $user = $this->userRepository->findById(auth()->id());

            $package = $this->packageRepository->findById($package_id);

            if (!$package) {
                return [
                    'status' => false,
                    'code' => 400,
                    'message' => 'subscription not found',
                    'data' => null,
                ];
            }

            $product = Product::all()->first();

            $subscribed = $user->subscribed($product->stripe_id);

            if ($subscribed) {
                return [
                    'status' => false,
                    'code' => 400,
                    'message' => "You are already a subscriber",
                    'data' => $subscribed,
                ];
            }

            if (!$package->active) {
                return [
                    'status' => false,
                    'code' => 400,
                    'message' => "$package->title not active",
                    'data' => $package,
                ];
            }

            if (!$user->activeCard) {
                return [
                    'status' => false,
                    'code' => 400,
                    'message' => "User does not have an active card",
                    'data' => null,
                ];
            }

            $sub = $user->newSubscription(
                $product->stripe_id,
                $package->stripe_id
            )->create($user->activeCard->stripe_id);

            $user->refresh();
            return [
                'status' => true,
                'code' => 200,
                'message' => "You have successfully subscribed to $package->title",
                'data' => $user->subscription($sub->type),
            ];
        } catch (\Throwable $th) {
            logError($th->getMessage(), ['error' => $th]);

            return [
                "status" => false,
                "code" => 400,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
    }

    public function unsubscribe()
    {
        try {
            $user = $this->userRepository->findById(auth()->id());

            $product = Product::all()->first();

            $user->subscription($product->stripe_id)->cancelNow();

            return [
                'status' => true,
                'code' => 200,
                'message' => "You have successfully unsubscribed",
                'data' => null,
            ];
        } catch (\Throwable $th) {
            logError($th->getMessage(), ['error' => $th]);

            return [
                "status" => false,
                "code" => 400,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
    }

    public function resume()
    {
        try {
            $user = $this->userRepository->findById(auth()->id());

            $product = Product::all()->first();

            $user->subscription($product->stripe_id)->resume();

            return [
                'status' => true,
                'code' => 200,
                'message' => "You have successfully resume subscription",
                'data' => null,
            ];
        } catch (\Throwable $th) {
            logError($th->getMessage(), ['error' => $th]);

            return [
                "status" => false,
                "code" => 400,
                "message" => $th->getMessage(),
                "data" => null
            ];
        }
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
