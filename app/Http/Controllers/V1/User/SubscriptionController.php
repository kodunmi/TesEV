<?php

namespace App\Http\Controllers\V1\User;


use App\Http\Controllers\Controller;
use App\Http\Resources\Core\PackageResource;
use App\Http\Resources\Core\TransactionResource;
use App\Http\Resources\User\UserSubscriptionResource;
use App\Repositories\User\UserRepository;
use App\Services\Core\SubscriptionService;
use Illuminate\Http\Request;

/**
 * @group Subscription
 *
 * Manage subscription
 */
class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected UserRepository $userRepository
    ) {
    }


    public function getPackages()
    {
        return respondSuccess('get all packages successfully', PackageResource::collection($this->subscriptionService->packages()));
    }


    public function getUserActiveSubscriptions()
    {
        $user = $this->userRepository->findById(auth()->id());

        return respondSuccess('all user active subscriptions', UserSubscriptionResource::collection($user->subscriptions()->active()->get()));
    }


    public function getUserExpiredSubscriptions()
    {
        $user = $this->userRepository->findById(auth()->id());

        return respondSuccess('all user active subscriptions', UserSubscriptionResource::collection($user->subscriptions()->pastDue()->get()));
    }

    public function getAllSubscriptions()
    {
        $user = $this->userRepository->findById(auth()->id());

        return respondSuccess('all user subscriptions', UserSubscriptionResource::collection($user->subscriptions));
    }


    public function subscribe($package_id)
    {


        $response = $this->subscriptionService->subscribe($package_id);

        if (!$response['status']) {
            return respondError($response['message'], null, $response['code']);
        }

        return respondSuccess($response['message'], new UserSubscriptionResource($response['data']));
    }

    public function unsubscribe()
    {
        $response = $this->subscriptionService->unsubscribe();

        if (!$response['status']) {
            return respondError($response['message'], null, $response['code']);
        }

        return respondSuccess($response['message'], $response['data']);
    }


    public function reactiveSubscription()
    {

        $response = $this->subscriptionService->resume();

        if (!$response['status']) {
            return respondError($response['code'], null, $response['message']);
        }

        return respondSuccess($response['message'], $response['data']);
    }

    public function transactionHistory()
    {
        $response = $this->subscriptionService->transactions();

        if (!$response['status']) {
            return respondError($response['code'], null, $response['message']);
        }

        return respondSuccess($response['message'], paginateResource($response['data'], TransactionResource::class));
    }
}
