<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AdminAuthService $adminAuthService)
    {
    }
    public function login(LoginRequest $request)
    {
        $validated = (object) $request->validated();

        $credentials = [
            'email' => $validated->email,
            'password' => $validated->password
        ];

        $login = $this->adminAuthService->login($credentials);


        if ($login['status']) {
            return respondSuccess($login['message'], $login['data']);
        }

        return respondError($login['message'], data: null, code: 401);
    }
}
