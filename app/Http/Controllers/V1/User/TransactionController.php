<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Core\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function getAllTransactions()
    {
        $user_id = auth()->id();

        $transactions = Transaction::where('user_id', $user_id)->paginate(10);

        return respondSuccess('transaction fetched successfully', paginateResource($transactions, TransactionResource::class));
    }
}
