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

    public function getAllTransactions()
    {
        $user_id = auth()->id();

        $transactions = Transaction::where('user_id', $user_id)->paginate(10);

        return respondSuccess('transactions fetched successfully', paginateResource($transactions, TransactionResource::class));
    }

    public function getTransaction($transaction_id)
    {
        $transaction = Transaction::find($transaction_id);

        return respondSuccess('transaction fetched successfully', new SingleTransactionResource($transaction));
    }
}
