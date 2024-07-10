<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Core\TransactionResource;
use App\Repositories\Core\TransactionRepository;
use Illuminate\Http\Request;

class TransactionManagementController extends Controller
{
    public function __construct(protected TransactionRepository $transactionRepository)
    {
    }


    public function getTransactions(Request $request)
    {
        // Retrieve query parameters
        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 4);
        $sortKey = $request->input('sortKey', 'created_at');
        $sortDirection = $request->input('sortDirection', 'asc');
        $userId = $request->input('user_id');
        $filters = json_decode($request->input('filters', '{}'), true);

        // Start building the query
        $query = $this->transactionRepository->query();

        // Filter by user_id if provided
        if (!empty($userId)) {
            $query->where('user_id', $userId);
        }

        // Apply filters if provided
        if (!empty($filters)) {
            if (!empty($filters['createdAt'][0])) {
                $query->whereDate('created_at', '>=', $filters['createdAt'][0]);
            }
            if (!empty($filters['createdAt'][1])) {
                $query->whereDate('created_at', '<=', $filters['createdAt'][1]);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        }

        // Apply sorting
        $query->orderBy($sortKey, $sortDirection);

        // Paginate the results
        $transactions = $query->with('user')->paginate($perPage, ['*'], 'page', $page);

        return respondSuccess("Transactions fetched successfully", paginateResource($transactions, TransactionResource::class));
    }

    public function getTransaction()
    {
    }

    public function getTransactionAnalytics()
    {
    }
}
