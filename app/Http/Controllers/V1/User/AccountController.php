<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\User\UserResource;
use App\Services\User\UserAuthService;
use App\Services\User\UserService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        protected UserAuthService $userAuthService,
        protected UserService $userService
    ) {
    }

    public function getProfile()
    {
        $user = auth()->user();

        return respondSuccess('User fetched', new UserResource($user));
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $data = $request->validated();
        $user_id = auth()->id();
        $updated = $this->userService->updateProfile($user_id, $data);

        if ($updated['status']) {
            return respondSuccess($updated['message'], $updated['data']);
        }

        return respondError($updated['message']);
    }

    public function deleteToken(Request $request)
    {
        $delete = $this->userAuthService->deleteToken($request);


        if ($delete['status']) {
            return respondSuccess($delete['message'], $delete['data'], $delete['code']);
        }

        return respondError($delete['message'], data: null, code: $delete['code']);
    }


    public function refreshToken(Request $request)
    {
        $refresh = $this->userAuthService->refreshToken($request);


        if ($refresh['status']) {
            return respondSuccess($refresh['message'], $refresh['data'], $refresh['code']);
        }

        return respondError($refresh['message'], data: null, code: $refresh['code']);
    }
}
