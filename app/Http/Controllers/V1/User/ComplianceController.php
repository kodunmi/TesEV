<?php

namespace App\Http\Controllers\V1\User;

use App\Actions\Cloud\CloudService;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UploadComplianceRequest;
use App\Repositories\User\ComplianceRepository;
use App\Services\User\UserService;

class ComplianceController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected CloudService $cloudService,
        protected ComplianceRepository $complianceRepository
    ) {
    }

    public function completeCompliance(UploadComplianceRequest $request)
    {
        $validated = (object) $request->validated();

        $response = $this->userService->completeRegistration($validated);

        if (!$response['status']) {
            return respondError($response['message']);
        }

        return respondSuccess($response['message']);
    }
}
