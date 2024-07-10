<?php

namespace App\Http\Controllers\V1\Admin;

use App\Actions\Cloud\CloudService;
use App\Enum\CloudTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Building\AddBuildingRequest;
use App\Http\Resources\Core\BuildingResource;
use App\Repositories\Core\BuildingRepository;
use Illuminate\Http\Request;

class BuildingManagementController extends Controller
{
    public function __construct(
        protected BuildingRepository $buildingRepository,
        protected CloudService $cloudService,
    ) {
    }

    public function getBuildings(Request $request)
    {
        $search = $request->query('search');
        $buildings = $this->buildingRepository->allWithoutPag($search);

        return respondSuccess("Vehicle fetched successfully", BuildingResource::collection($buildings));
    }


    public function getBuilding()
    {
    }

    public function addBuilding(AddBuildingRequest $request)
    {
        $validated = (object) $request->validated();

        $data = [
            'name' => $validated->name,
            'code' => $validated->code,
            'address' => $validated->address,
            'opening_time' => $validated->opening_time,
            'closing_time' => $validated->closing_time,
            'status' => $validated->status,
        ];

        $create_building = $this->buildingRepository->create($data);


        if (!$create_building) {
            return respondError('Building cannot be created at this moment');
        }

        $file_upload = $this->cloudService->upload(
            file: $validated->image,
            provider: CloudTypeEnum::CLOUDINARY,
            folder: 'buildings',
            owner_id: $create_building->id,
            name: $create_building->id . 'image',
            type: 'building_image',
            extension: $validated->image->getClientOriginalExtension()
        );

        if (!$file_upload['status']) {
            logError('Building image could not be uploaded full back to default');
        }


        return respondSuccess("Building created successfully", new  BuildingResource($create_building));
    }

    public function updateBuilding()
    {
    }

    public function addVehicleToBuilding()
    {
    }

    public function removeVehicleFromBuilding()
    {
    }

    public function addVehicleToBuildingAsync() // add it to the building and remove it from the other building
    {
    }

    public function removeVehicleFromBuildingAsync() // remove it from the building and add it to the provided building
    {
    }

    public function getBuildingAnalytics()
    {
    }

    public function toggleAvailability($building_id)
    {
        $building = $this->buildingRepository->toggleById($building_id);

        if (!$building) {
            return respondError('Building cannot be updated at this moment');
        }

        return respondSuccess("Building updated successfully", new  BuildingResource($building));
    }

    public function deleteBuilding($building_id)
    {
        $building = $this->buildingRepository->findById($building_id);

        if (!$building) {
            return respondError('Building not found', null, 404);
        }

        $delete_building = $building->delete();

        if (!$delete_building) {
            return respondError('Building could not be deleted', null, 400);
        }

        return respondSuccess("Building deleted successfully");
    }
}
