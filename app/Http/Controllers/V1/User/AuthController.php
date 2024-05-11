<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmAccountRequest;
use App\Http\Requests\Auth\ForgetPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendConfirmAccountTokenRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\User\UserAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(protected UserAuthService $userAuthService)
    {
    }
    public function login(LoginRequest $request)
    {
        $validated = (object) $request->validated();

        $credentials = [
            'email' => $validated->email,
            'password' => $validated->password
        ];

        $login = $this->userAuthService->login($credentials);


        if ($login['status']) {
            return respondSuccess($login['message'], $login['data']);
        }

        return respondError($login['message'], data: null, code: 401);
    }

    public function register(RegisterRequest $request)
    {
        $validated = (object) $request->validated();

        $data = [
            'password' => $validated->password,
            'phone' => $validated->phone,
            'email'  => $validated->email,
            'phone_code' => $validated->phone_code,
            'first_name'  => $validated->first_name,
            'last_name'  => $validated->last_name,
            'fcm_token' => $validated->fcm_token
        ];

        $register = $this->userAuthService->register($data);


        if ($register['status']) {
            return respondSuccess("Registration successful, please check your email for otp", $register['data']);
        }

        return respondError($register['message'], data: null, code: 400);
    }

    public function confirmAccount(ConfirmAccountRequest $request)
    {
        $validated = (object) $request->validated();

        $data = [
            'token' => $validated->token,
            'token_id' => $validated->token_id
        ];

        $confirm_account = $this->userAuthService->confirmAccount($data);

        if ($confirm_account['status']) {
            return respondSuccess($confirm_account['message'], $confirm_account['data']);
        }

        return respondError($confirm_account['message'], data: null, code: 400);
    }

    public function resendConfirmAccountToken(ResendConfirmAccountTokenRequest $request)
    {
        $validated = (object) $request->validated();

        $data = [
            'token_id' => $validated->token_id,
        ];

        $resend_confirm_account_token = $this->userAuthService->resendConfirmAccountToken($data);

        $data = [
            "token_id" => $resend_confirm_account_token['data']->id
        ];

        if ($resend_confirm_account_token['status']) {
            return respondSuccess($resend_confirm_account_token['message'], $data);
        }

        return respondError($resend_confirm_account_token['message'], data: $data, code: 400);
    }

    public function forgetPassword(ForgetPasswordRequest $request)
    {
        $validated = (object) $request->validated();


        $register = $this->userAuthService->forgetPasswordRequest($validated->email);


        if ($register['status']) {
            return respondSuccess("Please use the OTP to change password", $register['data']);
        }

        return respondError($register['message'], data: null, code: 400);
    }


    public function confirmResetPasswordToken(ConfirmAccountRequest $request)
    {
        $validated = (object) $request->validated();

        $confirm_reset_password_token = $this->userAuthService->confirmResetPasswordToken($validated->token, $validated->token_id);

        if ($confirm_reset_password_token['status']) {
            return respondSuccess($confirm_reset_password_token['message'], [
                'token' =>  $confirm_reset_password_token['data']
            ]);
        }

        return respondError($confirm_reset_password_token['message'], data: null, code: 400);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $validated = (object) $request->validated();

        $reset_password = $this->userAuthService->changePassword($validated->password);

        if ($reset_password['status']) {
            return respondSuccess($reset_password['message'], $reset_password['data']);
        }

        return respondError($reset_password['message'], data: null, code: 400);
    }
}
