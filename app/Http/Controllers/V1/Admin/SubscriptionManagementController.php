<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Core\SubscriptionResource;
use Illuminate\Http\Request;
use Laravel\Cashier\Subscription;

class SubscriptionManagementController extends Controller
{
    public function getSubscriptions(Request $request)
    {
        // Retrieve query parameters
        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 4);
        $userId = $request->input('user_id');
        $filters = json_decode($request->input('filters', '{}'), true);

        // Build the query
        $query = Subscription::query();

        // Filter by user_id if provided
        if (!empty($userId)) {
            $query->where('user_id', $userId);
        }

        // Apply additional filters if provided
        if (!empty($filters)) {
            if (!empty($filters['createdAt'][0])) {
                $query->whereDate('created_at', '>=', $filters['createdAt'][0]);
            }
            if (!empty($filters['createdAt'][1])) {
                $query->whereDate('created_at', '<=', $filters['createdAt'][1]);
            }
            if (!empty($filters['status'])) {
                // Map the filter status to the appropriate scope
                switch ($filters['status']) {
                    case 'active':
                        $query->active();
                        break;
                    case 'canceled':
                        $query->canceled();
                        break;
                    case 'ended':
                        $query->ended();
                        break;
                    case 'incomplete':
                        $query->incomplete();
                        break;
                    case 'notCanceled':
                        $query->notCanceled();
                        break;
                    case 'notOnGracePeriod':
                        $query->notOnGracePeriod();
                        break;
                    case 'notOnTrial':
                        $query->notOnTrial();
                        break;
                    case 'onGracePeriod':
                        $query->onGracePeriod();
                        break;
                    case 'onTrial':
                        $query->onTrial();
                        break;
                    case 'pastDue':
                        $query->pastDue();
                        break;
                    case 'recurring':
                        $query->recurring();
                        break;
                }
            }
        }

        // Apply sorting if provided
        if ($sortKey = $request->input('sortKey')) {
            $sortDirection = $request->input('sortDirection', 'asc');
            $query->orderBy($sortKey, $sortDirection);
        }

        // Paginate the results
        $subscriptions = $query->paginate($perPage, ['*'], 'page', $page);

        // Return the paginated results

        return respondSuccess("Subscriptions fetched successfully", paginateResource($subscriptions, SubscriptionResource::class));
    }

    public function getSubscription()
    {
    }

    public function getSubscriptionUsers()
    {
    }

    public function getSubscriptionsAnalytics()
    {
    }

    public function getProducts()
    {
    }

    public function getProduct()
    {
    }

    public function updateProduct()
    {
    }

    public function deleteProduct()
    {
    }
}
