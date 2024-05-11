<?php

namespace App\Http\Controllers\V1\Admin;

use App\Actions\Notifications\NotificationService;
use App\Enum\AdminRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeAdminRoleRequest;
use App\Http\Requests\Admin\CreateAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Repositories\Admin\AdminRepository;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\Request;

class AdminManagementController extends Controller
{
    public function __construct(
        protected AdminRepository $adminRepository,
        protected AdminAuthService $adminAuthService
    ) {
    }
    public function getAdmins()
    {
        return respondSuccess('Admins fetched successfully', $this->adminRepository->all());
    }

    public function getAdmin($admin_id)
    {
        return respondSuccess('Admin fetched successfully', $this->adminRepository->findById($admin_id));
    }

    public function getAdminAnalytics()
    {
        $data = [
            'super_admin_count' => $this->adminRepository->query()->where('role', AdminRoleEnum::SUPER_ADMIN->value)->count(),
            'admin_count' => $this->adminRepository->query()->where('role', AdminRoleEnum::ADMIN->value)->count(),
            'total_count' => $this->adminRepository->query()->count()
        ];
        return respondSuccess('Admin analytics fetched successfully', $data);
    }

    public function addAdmin(CreateAdminRequest $request)
    {
        $validated =  $request->validated();


        $register = $this->adminAuthService->register($validated);


        if ($register['status']) {
            return respondSuccess($register['message'], $register['data']);
        }

        return respondError($register['message'], data: null, code: 401);
    }

    public function updateAdmin(UpdateAdminRequest $request, $admin_id)
    {
        $validated =  $request->validated();

        $admin = $this->adminRepository->updateAdmin($admin_id, $validated);


        if ($admin) {
            return respondSuccess('Admin updated successfully', $admin);
        }

        return respondError('Error updating admin', data: null, code: 400);
    }

    public function changeAdminRole(ChangeAdminRoleRequest $request, $admin_id)
    {
        $validated =  $request->validated();

        $admin = $this->adminRepository->updateAdmin($admin_id, $validated);


        if ($admin) {
            return respondSuccess('Admin role updated successfully', $admin);
        }

        return respondError('Error updating admin role', data: null, code: 400);
    }

    public function getRoles()
    {
        return respondSuccess('Admin role fetched successfully', AdminRoleEnum::cases());
    }

    public function getAdminRole($admin_id)
    {
        $admin = $this->adminRepository->findById($admin_id);

        $data = [
            'role' => $admin->role,
        ];

        return respondSuccess('Admin role fetched successfully', $data);
    }

    public function removeAdmin($admin_id)
    {
        $admin = $this->adminRepository->deleteAdmin($admin_id);

        if ($admin) {
            return respondSuccess('Admin deleted successfully');
        }

        return respondError('Error deleting admin', data: null, code: 400);
    }

    public function resetAdminPassword($admin_id)
    {
        $password = generateRandomString();

        $admin = $this->adminRepository->updateAdmin($admin_id, [
            'password' => $password
        ]);

        if (!$admin) {
            return respondError('Error updating password', data: null, code: 400);
        }

        $notification = new NotificationService();

        $notification->setSubject('You admin password has been updated')
            ->setView('emails.admin.update_password')
            ->setData([
                'password' => $password,
                'admin' => $admin
            ])
            ->sendEmail($admin->email);

        return respondSuccess('Password has been generated and sent to admin');
    }

    public function toggleStatus($admin_id)
    {
        $admin = $this->adminRepository->findById($admin_id);

        if (!$admin) {
            return respondError('Admin not found', data: null, code: 404);
        }

        $update = $this->adminRepository->updateAdmin($admin_id, [
            'active' => !$admin->active
        ]);

        if (!$update) {
            return respondError('Admin could not be updated', data: null, code: 400);
        }

        $admin->refresh();

        $condition = $admin->active ? "enabled" : "disabled";

        return respondSuccess("Admin $condition successfully");
    }
}
