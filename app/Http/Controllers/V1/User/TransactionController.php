<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Core\SingleTransactionResource;
use App\Http\Resources\Core\TransactionResource;
use App\Models\Transaction;
use App\Repositories\Core\TransactionRepository;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(protected TransactionRepository $transactionRepository)
    {
    }

    public function getAllTransactions(Request $request)
    {

        $query = Transaction::query();

        $user_id = auth()->id();

        $type = $request->query('type');

        $query->when($type, function ($q) use ($type) {
            return $q->where('type', $type);
        });

        $transactions = $query->where('user_id', $user_id)->paginate(10);

        return respondSuccess('transactions fetched successfully', paginateResource($transactions, TransactionResource::class));
    }

    public function getTransaction($transaction_id)
    {
        $transaction = Transaction::find($transaction_id);



        return respondSuccess('transaction fetched successfully', new SingleTransactionResource($transaction));
    }
}
