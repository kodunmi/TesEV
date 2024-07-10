<?php

namespace App\Services\Admin;

use App\Repositories\User\UserRepository;

class UserManagementService
{
    public function __construct(protected UserRepository $userRepository)
    {
    }
    public function getUsers($params)
    {
        // Extract parameters with default values
        $page = $params['page'] ?? 1;
        $perPage = $params['perPage'] ?? 10;
        $sortKey = $params['sortKey'];
        $sortDirection = $params['sortDirection'];
        $searchTerm = $params['searchTerm'];

        // Create the query builder
        $query = $this->userRepository->query();

        // Apply search term if provided
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('phone', 'like', '%' . $searchTerm . '%');
            });
        }

        if (isset($params['filters'])) {
            $filters = json_decode($params['filters'], true);

            if (isset($filters['status']) &&  $filters['status'] !== "") {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['createdAt'])) {

                if (!is_null($filters['createdAt'][0]) && !is_null($filters['createdAt'][1])) {
                    $query->whereBetween('created_at', $filters['createdAt']);
                }
            }

            if ($filters['subscription'][0] !== "" && $filters['subscription'][0] !== "") {

                $sub_data = [
                    dollarToCent($filters['subscription'][0]),
                    dollarToCent($filters['subscription'][1])
                ];

                $query->whereIn('subscription_balance', $sub_data);
            }


            if ($filters['wallet'][0] !== "" && $filters['wallet'][0] !== "") {

                $wallet_data = [
                    dollarToCent($filters['wallet'][0]),
                    dollarToCent($filters['wallet'][1])
                ];

                $query->whereIn('wallet', $wallet_data);
            }


            $isFiltered = true;
        }

        // Apply filters if provided


        // dd($sortDirection);
        // Apply sorting
        $query->orderBy($sortKey, $sortDirection);

        // Paginate results
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return $users;
    }
}
