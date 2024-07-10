<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Repositories\User\UserRepository;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(
        protected UserManagementService $userManagementService,
        protected UserRepository $userRepository
    ) {
    }

    public function getUsers(Request $request)
    {
        $params = [
            'page' => $request->query('page', 1),
            'perPage' => $request->query('perPage', 10),
            'sortKey' => $request->query('sortKey', 'created_at'),
            'sortDirection' => $request->query('sortDirection', 'asc'),
            'searchTerm' => $request->query('searchTerm', ''),
            'filters' => $request->query('filters')
        ];

        $users = $this->userManagementService->getUsers($params);

        return respondSuccess("Users fetched successfully", paginateResource($users, UserResource::class));
    }

    public function getUser($user_id)
    {
        $user = $this->userRepository->findById($user_id);

        return respondSuccess("User fetched successfully", new UserResource($user));
    }

    public function addUser()
    {
    }

    public function updateUser()
    {
    }

    public function deleteUser()
    {
    }

    public function verifyUser()
    {
    }

    public function verifyUserBuilding()
    {
    }

    public function getUserBuildings($user_id, Request $request)
    {
        $user = User::find($user_id);

        $per_page = $request->query('perPage', 10);

        if ($user) {
            return respondSuccess("Building fetched successfully", $user->buildings()->withCount(['trips'])->paginate($per_page));
        }
    }

    public function toggleUserStatus()
    {
    }

    public function getUserAnalytics()
    {
    }
}
