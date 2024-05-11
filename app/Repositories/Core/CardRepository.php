<?php

namespace App\Repositories\Core;

use App\Interfaces\Core\CardRepositoryInterface;
use App\Models\Card;

class CardRepository implements CardRepositoryInterface
{
    public function all()
    {
        return Card::paginate(10);
    }

    public function findById(string $id): ?Card
    {
        return Card::find($id);
    }

    public function findByStripeId(string $id): ?Card
    {
        return Card::where('stripe_id', $id)->first();
    }

    public function create(array $data): Card
    {
        $card = new Card();
        $card->user_id = $data['user_id'] ?? null;
        $card->exp_year = $data['exp_year'] ?? null;
        $card->exp_month = $data['exp_month'] ?? null;
        $card->number = $data['number'] ?? null;
        $card->is_default = $data['is_default'] ?? false;
        $card->is_active = $data['is_active'] ?? true;
        $card->public_id = $data['public_id'] ?? null;
        $card->object = $data['object'] ?? null;

        $card->save();
        return $card;
    }

    public function update(string $id, array $data): ?Card
    {
        $card = $this->findById($id);

        if (!$card) {
            return null;
        }

        $card->user_id = $data['user_id'] ?? $card->user_id;
        $card->exp_year = $data['exp_year'] ?? $card->exp_year;
        $card->exp_month = $data['exp_month'] ?? $card->exp_month;
        $card->number = $data['number'] ?? $card->number;
        $card->is_default = $data['is_default'] ?? $card->is_default;
        $card->is_active = $data['is_active'] ?? $card->is_active;
        $card->public_id = $data['public_id'] ?? $card->public_id;
        $card->object = $data['object'] ?? $card->object;

        $card->save();
        return $card;
    }

    public function delete(string $id): bool
    {
        $card = Card::find($id);
        if ($card) {
            $card->delete();
            return true;
        }
        return false;
    }
}
