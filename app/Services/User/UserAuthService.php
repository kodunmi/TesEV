<?php

namespace App\Services\User;

use App\Actions\Notifications\NotificationService;
use App\Actions\Payment\StripeService;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Repositories\Core\TokenRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

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

            $token = $user->createToken('access-token')->plainTextToken;

            return [
                'status' => true,
                'message' => 'Login successful',
                'data' => [
                    "user" => new UserResource($user),
                    "token" => $token
                ]
            ];
        }

        if (isset($credentials['fcm_token'])) {
            $this->userRepository->updateUser($user->id, [
                'fcm_token' => $credentials['fcm_token']
            ]);
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

        $stripe_data = [
            'name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone
        ];

        $this->userRepository->updateUser($user->id, [
            'email_verified_at' => now()
        ]);

        $user->createAsStripeCustomer($stripe_data);

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

    public function refreshToken(Request $request)
    {
        if (empty($token = $request->header('Authorization'))) {
            return [
                'message' => 'Token is invalid',
                'data' => null,
                'status' => false,
                'code' => 422
            ];
        }

        $tokenParts = explode('Bearer ', $token);
        if (count($tokenParts) !== 2 || empty($tokenParts[1]) || empty($token = PersonalAccessToken::findToken($tokenParts[1]))) {
            return [
                'message' => 'Token is invalid',
                'data' => null,
                'status' => false,
                'code' => 422
            ];
        }

        if (!$token->tokenable instanceof User) {
            return [
                'message' => 'Token is invalid',
                'data' => null,
                'status' => false,
                'code' => 422
            ];
        }

        $token->delete();

        return [
            'message' => '',
            'data' => ['access_token' => $token->tokenable->createToken('access-token')->plainTextToken],
            'status' => true,
            'code' => 200
        ];
    }


    public function deleteToken(Request $request)
    {
        if (empty($token = $request->header('Authorization'))) {
            return [
                'message' => 'Token is invalid',
                'data' => null,
                'status' => false,
                'code' => 422
            ];
        }

        $tokenParts = explode('Bearer ', $token);
        if (count($tokenParts) !== 2 || empty($tokenParts[1]) || empty($token = PersonalAccessToken::findToken($tokenParts[1]))) {
            return [
                'message' => 'Token is invalid',
                'data' => null,
                'status' => false,
                'code' => 422
            ];
        }

        if (!$token->tokenable instanceof User) {
            return [
                'message' => 'Token is invalid',
                'data' => null,
                'status' => false,
                'code' => 422
            ];
        }

        // Revoke (delete) the token
        $token->delete();

        return [
            'message' => 'Token deleted successfully',
            'data' => null,
            'status' => true,
            'code' => 200
        ];
    }
}
