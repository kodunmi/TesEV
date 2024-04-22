<?php

namespace App\Repositories\Core;

use App\Interfaces\Core\TokenRepositoryInterface;
use App\Models\Token;
use Carbon\Carbon;

class TokenRepository implements TokenRepositoryInterface
{
    public function getAll()
    {
        return Token::all();
    }

    public function findById(string $id)
    {
        return Token::find($id);
    }

    public function findByToken(string $token)
    {
        return Token::where('token', $token)->first() ?? null;
    }

    public function findByTokenAndId(string $token, string $id)
    {
        return Token::where('token', $token)->where('id', $id)->first() ?? null;
    }

    public function findUserToken(string $user_id, string $token)
    {
        return Token::where('user_id', $user_id)->where('token', $token)->first() ?? null;
    }

    public function findByTokenAndPhone(string $recipient, string $token)
    {
        return Token::where('recipient', $recipient)->where('token', $token)->first() ?? null;
    }

    public function makeInvalid(string $id)
    {
        $token = $this->findById($id);
        $token->verified_at = Carbon::now();
        $token->valid = false;
        $token->save();

        return $token;
    }

    public function create(array $data)
    {
        $token = new Token;
        $token->user_id = $data['user_id'] ?? null;
        $token->token = $data['token'] ?? generateRandomNumber(4);
        $token->purpose = $data['purpose'] ?? null;
        $token->recipient = $data['recipient'] ?? null;
        $token->channel = $data['channel'] ?? null;
        $token->ttl = $data['ttl'] ?? 5;
        $token->data = $data['data'] ?? null;
        $token->valid = true;
        $token->meta = $data['meta'] ?? null;
        $token->public_id = uuid();
        $token->save();

        return $token;
    }

    public function update(string $id, array $data)
    {
        $token = $this->findById($id);
        if (!$token) {
            return null;
        }

        $token->expired_at = $data['expired_at'] ?? $token->expired_at;
        $token->valid = $data['valid'] ?? $token->valid;
        $updated = $token->save();

        return $updated ? $token : null;
    }

    public function destroyById(string $id)
    {
        return Token::destroy($id);
    }

    public function destroyUserTokens(string $user_id)
    {
        return Token::where('user_id', $user_id)->delete();
    }
}
