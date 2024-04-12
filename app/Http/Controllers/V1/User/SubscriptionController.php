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

        return respondSuccess('all user active subscriptions', UserSubscriptionResource::collection($user->activeSubscriptions));
    }


    public function getUserExpiredSubscriptions()
    {
        $user = $this->userRepository->findById(auth()->id());

        return respondSuccess('all user active subscriptions', UserSubscriptionResource::collection($user->expiredSubscriptions));
    }

    public function getAllSubscriptions()
    {
        $user = $this->userRepository->findById(auth()->id());

        return respondSuccess('all user subscriptions', UserSubscriptionResource::collection($user->subscriptions));
    }


    public function subscribe(Request $request, $package_id)
    {


        $response = $this->subscriptionService->subscribe($package_id);

        if (!$response['status']) {
            return respondError($response['code'], null, $response['message']);
        }

        return respondSuccess($response['message'], new UserSubscriptionResource($response['data']));
    }

    public function unsubscribe($package_id)
    {
        $response = $this->subscriptionService->unsubscribe($package_id);

        if (!$response['status']) {
            return respondError($response['code'], null, $response['message']);
        }

        return respondSuccess($response['message'], $response['data']);
    }


    public function reactiveSubscription(Request $request, $package_id)
    {
        $auto_renew = $request->query('autoRenew', false);
        $response = $this->subscriptionService->subscribe($package_id, $auto_renew);

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
