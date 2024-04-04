<?php

namespace App\Services\User;

use App\Actions\Cloud\CloudService;
use App\Enum\CloudTypeEnum;
use App\Repositories\User\ComplianceRepository;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        protected CloudService $cloudService,
        protected ComplianceRepository $complianceRepository
    ) {
    }

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
                'active' => true
            ]);

            $data_for_upload = [
                'driver_license_front' => $validated->driver_license_front,
                'driver_license_back' => $validated->driver_license_back,
                'photo' => $validated->photo,
            ];

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

            $this->complianceRepository->markOtherComplianceAsFalse($compliance->id);

            DB::commit();

            return [
                'status' => true,
                'message' => 'Compliance updated successfully, awaiting verification',
                'data' => null
            ];
        } catch (\Exception $e) {
            logError($e->getMessage());
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'An error occurred during compliance upload',
                'data' => null
            ];
        }
    }
}
