<?php

namespace App\Services\User;

use App\Actions\Notifications\NotificationService;
use App\Repositories\Core\TokenRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserAuthService
{

    public function __construct(
        protected UserRepository $userRepository,
        protected TokenRepository $tokenRepository
    ) {
    }

    public function login($credentials)
    {

        $user = $this->userRepository->getUserByEmail($credentials['email']);


        if ($user || !Hash::check($credentials['password'], $user->password)) {

            $token = $user->createToken('authToken')->plainTextToken;

            return [
                'status' => true,
                'message' => 'Login successful',
                'data' => [
                    "user" => $user,
                    "token" => $token
                ]
            ];
        }

        return [
            'status' => false,
            'message' => 'Email or password not correct',
            'data' => null
        ];
    }

    public function register(array $data)
    {
        $user = $this->userRepository->createUser($data);

        if (!$user) {
            return [
                'status' => false,
                'message' => 'Registration could not be completed at this time, try again',
                'data' => null
            ];
        }

        $notification = new NotificationService($user);

        $data = $notification->sendEmailOtp();

        return [
            'status' => true,
            'message' => $data['message'],
            'data' => [
                'token_id' => $data['data']->id
            ]
        ];
    }

    public function confirmAccount(array $data)
    {
        $notification = new NotificationService();

        $verify_token = $notification->verifyOtp($data['token']);

        if (!$verify_token['status']) {
            return $verify_token;
        }

        $email = $verify_token['data']->recipient;

        $user = $this->userRepository->getUserByEmail($email);

        if ($user->email_verified_at) {
            return [
                'status' => false,
                'message' => "Email already verified",
                'data' => null
            ];
        }

        $update = $this->userRepository->updateUser($user->id, [
            'email_verified_at' => now()
        ]);

        if (!$update) {
            return [
                'status' => false,
                'message' => "Error updating email verification status, try again",
                'data' => null
            ];
        }

        return [
            'status' => true,
            'message' => "Email verified successfully, proceed to login",
            'data' => $user
        ];
    }

    public function resendConfirmAccountToken(array $data)
    {

        $token = $this->tokenRepository->findById($data['token_id']);

        $email = $token->recipient;

        $user = $this->userRepository->getUserByEmail($email);

        $notification = new NotificationService($user);

        $resend_verify_token = $notification->resendEmailOtp($data['token_id']);

        if (!$resend_verify_token['status']) {
            return $resend_verify_token;
        }

        return $resend_verify_token;
    }
}
