<?php

namespace App\Services\User;

use App\Actions\Cloud\CloudService;
use App\Enum\CloudTypeEnum;
use App\Repositories\User\ComplianceRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class UserService
{
    public function __construct(
        protected CloudService $cloudService,
        protected ComplianceRepository $complianceRepository,
        protected UserRepository $userRepository,
    ) {}

    public function completeRegistration($validated)
    {
        DB::beginTransaction();

        try {
            $compliance = $this->complianceRepository->create([
                'user_id' => auth()->id(),
                'license_state' => $validated->license_state,
                'poster_code' => $validated->poster_code,
                'license_number' => $validated->license_number,
                'expiration_date' => $validated->expiration_date,
                'active' => true,
                'license_verified' => true,
                'license_verified_at' => now()
            ]);

            $data_for_upload = [
                'driver_license_front' => $validated->driver_license_front,
                'driver_license_back' => $validated->driver_license_back,
                'photo' => $validated->photo,
            ];

            // verifiy here


            foreach ($data_for_upload as $key => $image) {
                $file_upload = $this->cloudService->upload(
                    file: $image,
                    provider: CloudTypeEnum::CLOUDINARY,
                    folder: 'compliance',
                    owner_id: $compliance->id,
                    name: $key,
                    type: $key,
                    extension: $image->getClientOriginalExtension()
                );
                if (!$file_upload['status']) {
                    DB::rollBack();
                    return [
                        'status' => false,
                        'message' =>  "Error uploading " . str_replace('_', ' ', $key) . $file_upload['message'],
                        'data' => null
                    ];
                }
                $this->complianceRepository->update($compliance->id, [
                    $key => $file_upload['data']->id
                ]);
            }

            // Perform the verification using Guzzle
            $verificationResponse = $this->verifyDocuments([
                'documentType' => 1,
                'frontImageBase64' => base64_encode(file_get_contents($validated->driver_license_front->path())),
                'backOrSecondImageBase64' => base64_encode(file_get_contents($validated->driver_license_back->path())),
                'faceImageBase64' => base64_encode(file_get_contents($validated->photo->path())),
                'trackString' => ['data' => '', 'barcodeParams' => ''],
                'ssn' => '',
                'overriddenSettings' => [
                    'isOCREnabled' => true,
                    'isBackOrSecondImageProcessingEnabled' => true,
                    'isFaceMatchEnabled' => true
                ],

            ]);

            // Process the verification response as needed
            if (!$verificationResponse['status']) {
                DB::rollBack();
                return [
                    'status' => false,
                    'message' => $verificationResponse['message'],
                    'data' => $verificationResponse['data']
                ];
            }

            $this->complianceRepository->markOtherComplianceAsFalse($compliance->id);

            DB::commit();

            return [
                'status' => true,
                'message' => 'Compliance updated successfully, awaiting verification',
                'data' => $verificationResponse
            ];
        } catch (\Exception $e) {


            logError($e->getMessage());

            DB::rollBack();
            return [
                'status' => false,
                'message' => 'An error occurred during compliance upload, try again',
                'data' => null
            ];
        }
    }

    public function updateProfile($id, $data)
    {

        $updated = $this->userRepository->updateUser($id, $data);

        if (!$updated) {
            return [
                'status' => false,
                'message' => 'Error updating profile',
                'data' => null
            ];
        }

        return [
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => null
        ];
    }

    public function updateProfileImage($user_id, $image)
    {

        $file_upload = $this->cloudService->upload(
            file: $image,
            provider: CloudTypeEnum::CLOUDINARY,
            folder: 'profile',
            owner_id: $user_id,
            name: $user_id . 'profile_image',
            type: 'profile_image',
            extension: $image->getClientOriginalExtension()
        );

        if (!$file_upload['status']) {
            return [
                'status' => false,
                'message' =>  "Error uploading image",
                'data' => null
            ];
        }
        return [
            'status' => true,
            'message' =>  "Profile image uploaded successfully",
            'data' => null
        ];
    }

    private function verifyDocuments(array $data)
    {
        $user =  Auth::user();
        try {
            $response = Http::timeout(-1)->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('IDWARE_API_TOKEN'),
            ])->post(env('IDWARE_API_URL'), $data);


            logError($response->body());

            $body = $response->json();

            if ($response->successful()) {

                if (
                    Carbon::parse($body['document']['dob'])->format('Y-m-d') !== Carbon::parse($user->date_of_birth)->format('Y-m-d')
                    || strtolower($body['document']['familyName']) !== strtolower($user->last_name)
                    || strtolower($body['document']['firstName']) !== strtolower($user->first_name)
                ) {
                    return [
                        'status' => false,
                        'message' => 'Verification failed, document info does not match record',
                        'data' => null
                    ];
                }

                return [
                    'status' => true,
                    'message' => 'ID verification successful',
                    'data' => $response->json()
                ];
            } else {



                return [
                    'status' => false,
                    'message' => 'Verification failed, try again',
                    'data' => $this->formatApiErrors($body)
                ];
            }
        } catch (\Exception $e) {
            logError($e->getMessage());

            return [
                'status' => false,
                'reason' => 'Error in verification process: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    private function formatApiErrors($errorResponse)
    {
        $formattedErrors = [];
        $messages = [];

        if (isset($errorResponse['code']) &&  $errorResponse['code'] == 'MultipleErrors') {
            foreach ($errorResponse['multipleErrors'] as $error) {
                $messages[] = $error['message'];
            }
        } else {
            $messages[] = $errorResponse['message'];
        }

        return $messages;
    }
}
