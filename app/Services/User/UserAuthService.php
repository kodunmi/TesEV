<?php

namespace App\Services\User;

use App\Actions\Notifications\NotificationService;
use App\Actions\Payment\StripeService;
use App\Repositories\Core\TokenRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserAuthService
{

    public function __construct(
        protected UserRepository $userRepository,
        protected TokenRepository $tokenRepository,
        protected StripeService $stripeService
    ) {
    }

    public function login($credentials)
    {

        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user) {
            return [
                'status' => false,
                'message' => 'Email or password not correct',
                'data' => null
            ];
        }

        if (!$user->email_verified_at) {

            $notification = new NotificationService($user);

            $data = $notification->sendEmailOtp();

            return [
                'status' => false,
                'message' => 'Email not verified, please check you email for token',
                'data' => [
                    'token_id' => $data['data']->id
                ]
            ];
        }


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

        $verify_token = $notification->verifyOtp($data['token'], $data['token_id']);

        if (!$verify_token['status']) {
            return $verify_token;
        }

        $email = $verify_token['data']->recipient;

        $user = $this->userRepository->findByEmail($email);

        $token = $user->createToken('authToken')->plainTextToken;

        if ($user->email_verified_at) {
            return [
                'status' => false,
                'message' => "Email already verified",
                'data' => null
            ];
        }

        // create stripe customer
        $stripe_data = [
            'name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone
        ];

        $response = $this->stripeService->createCustomer($stripe_data);

        $update = $this->userRepository->updateUser($user->id, [
            'email_verified_at' => now(),
            'customer_id' => $response['data']->id
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
            'message' => "Email verified successfully",
            'data' =>  [
                'token' => $token
            ]
        ];
    }

    public function resendConfirmAccountToken(array $data)
    {

        $token = $this->tokenRepository->findById($data['token_id']);

        $email = $token->recipient;

        $user = $this->userRepository->findByEmail($email);

        $notification = new NotificationService($user);

        $resend_verify_token = $notification->resendEmailOtp($data['token_id']);

        if (!$resend_verify_token['status']) {
            return $resend_verify_token;
        }

        return $resend_verify_token;
    }

    public function forgetPasswordRequest($email)
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return [
                'status' => false,
                'message' => "User with the email not found",
                'data' => null
            ];
        }

        $notification = new NotificationService($user);

        $data = $notification->sendEmailOtp(title: "Please use the OTP to change your password");

        return [
            'status' => true,
            'message' => $data['message'],
            'data' => [
                'token_id' => $data['data']->id
            ]
        ];
    }

    public function confirmResetPasswordToken($token, $token_id)
    {
        $notification = new NotificationService();

        $verify_token = $notification->verifyOtp($token, $token_id);



        if (!$verify_token['status']) {
            return $verify_token;
        }

        $email = $verify_token['data']->recipient;

        $user = $this->userRepository->findByEmail($email);


        $token = $user->createToken('authToken')->plainTextToken;

        return [
            "status" => true,
            "message" => "OTP verified successfully",
            "data" => $token
        ];
    }

    public function changePassword($password)
    {
        $update = $this->userRepository->updateUser(auth()->id(), [
            'password' => $password
        ]);

        if (!$update) {
            return [
                "status" => false,
                "message" => "We could not update your password at this time, please try again",
                "data" => null
            ];
        }

        $user = $this->userRepository->findById(auth()->id());

        $user->tokens()->delete();

        return [
            "status" => true,
            "message" => "Password updated successfully, please login",
            "data" => null
        ];
    }
}
