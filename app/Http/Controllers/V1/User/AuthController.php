<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ConfirmAccountRequest;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Requests\User\ResendConfirmAccountTokenRequest;
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

        if ($resend_confirm_account_token['status']) {
            return respondSuccess($resend_confirm_account_token['message'], $resend_confirm_account_token['data']);
        }

        return respondError($resend_confirm_account_token['message'], data: null, code: 400);
    }

    public function forgetPassword()
    {
    }

    public function resetPassword()
    {
    }


    public function resendForgetPassword()
    {
    }

    public function sendForgetPasswordOtp()
    {
    }
}
