<?php

namespace App\Services\Admin;

use App\Actions\Notifications\NotificationService;
use App\Repositories\Admin\AdminRepository;
use Illuminate\Support\Facades\Hash;

class AdminAuthService
{
    public function __construct(protected AdminRepository $adminRepository)
    {
    }
    public function login($credentials)
    {

        $admin = $this->adminRepository->findByEmail($credentials['email']);

        if (!$admin) {
            return [
                'status' => false,
                'message' => 'Email or password not correct',
                'data' => null
            ];
        }

        if (!$admin->active) {
            return [
                'status' => false,
                'message' => 'Admin is not active, contact support',
                'data' => null
            ];
        }


        if ($admin || !Hash::check($credentials['password'], $admin->password)) {

            $token = $admin->createToken('adminAuthToken', [
                $admin->role
            ])->plainTextToken;

            return [
                'status' => true,
                'message' => 'Login successful',
                'data' => [
                    "admin" => $admin,
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

    public function register($data)
    {
        $password =  generateRandomString();
        $input = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $password
        ];
        $register = $this->adminRepository->createAdmin($input);


        if (!$register) {
            return [
                'status' => false,
                'message' => 'We could not register the admin',
                'data' => null
            ];
        }

        $notification = new NotificationService();

        $notification->setSubject('You have been added as admin')
            ->setView('emails.admin.register')
            ->setData([
                'password' => $password,
                'admin' => $register
            ])
            ->sendEmail($register->email);

        return [
            'status' => true,
            'message' => 'Admin registered successfully, credential sent',
            'data' => $register
        ];
    }
}
