<?php

namespace App\Repositories\Core;

use App\Interfaces\Core\TransactionRepositoryInterface;
use App\Models\Transaction;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function findById(string $id)
    {
        return Transaction::find($id);
    }

    public function findByReference(string $reference)
    {
        return Transaction::where('reference', $reference)->get();
    }

    public function findManyById(array $array)
    {
        return Transaction::find('id', $array);
    }

    public function create(array $data): Transaction
    {
        $transaction = new Transaction();

        $transaction->reference = $data['reference'] ?? null;
        $transaction->amount = $data['amount'] ?? 0;
        $transaction->narration = $data['narration'] ?? null;
        $transaction->title = $data['title'] ?? null;
        $transaction->status = $data['status'] ?? null;
        $transaction->entry = $data['entry'] ?? null;
        $transaction->type = $data['type'] ?? null;
        $transaction->channel = $data['channel'] ?? null;
        $transaction->transaction_date = $data['transaction_date'] ?? null;
        $transaction->meta = $data['meta'] ?? null;
        $transaction->object = $data['object'] ?? null;
        $transaction->public_id = uuid();

        if (isset($data['transactable_type']) && isset($data['transactable_id'])) {
            $transaction->save();
            logInfo('Transaction saved');

            return $transaction;
        }

        logInfo('Transaction init');

        return $transaction;
    }

    public function update(string $id, array $data)
    {
        $transaction = $this->findById($id);
        if ($transaction) {

            $transaction->status = $data['status'] ?? $transaction->status;

            $transaction->reference = $data['reference'] ?? $transaction->reference;
            $transaction->amount = $data['amount'] ?? $transaction->amount;
            $transaction->narration = $data['narration'] ?? $transaction->narration;
            $transaction->title = $data['title'] ?? $transaction->title;
            $transaction->type = $data['type'] ?? $transaction->type;
            $transaction->channel = $data['channel'] ?? $transaction->channel;
            $transaction->object = $data['object'] ?? $transaction->object;
            $transaction->meta = $data['meta'] ?? $transaction->meta;
            $transaction->transaction_date = $data['transaction_date'] ?? $transaction->transaction_date;
            $transaction->save();

            return $transaction;
        }

        return false;
    }

    public function delete(string $id)
    {
        return Transaction::destroy($id);
    }

    public function all()
    {
        return Transaction::all();
    }

    public function query()
    {
        return Transaction::query();
    }
}
