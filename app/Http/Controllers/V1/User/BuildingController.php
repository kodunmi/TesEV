<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AddBuildingRequest;
use App\Http\Requests\User\GetAvailableVehiclesForBuilding;
use App\Http\Resources\Core\BuildingResource;
use App\Http\Resources\Core\PaginateResource;
use App\Http\Resources\Core\VehicleResource;
use App\Repositories\Core\BuildingRepository;
use App\Repositories\Core\TripRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BuildingController extends Controller
{
    public function __construct(
        protected BuildingRepository $buildingRepository,
        protected UserRepository $userRepository,
        protected TripRepository $tripRepository
    ) {
    }
    public function addBuilding(AddBuildingRequest $request)
    {
        try {

            $user = $this->userRepository->findById(auth()->id());

            $validated = (object) $request->validated();

            $building = $this->buildingRepository->findById($validated->building_id);

            if ($user->buildings()->find($building)) {
                return respondError('Building already adding', null, 400);
            }

            $user->buildings()->attach($building->id);

            return respondSuccess('Building added successfully', new BuildingResource($building));
        } catch (\Throwable $th) {
            logError($th->getMessage());
            return respondError('Error adding building', null, 400);
        }
    }

    public function removeBuilding(AddBuildingRequest $request)
    {
        try {

            $user = $this->userRepository->findById(auth()->id());

            $validated = (object) $request->validated();

            $building = $this->buildingRepository->findById($validated->building_id);

            $user->buildings()->detach($building->id);

            return respondSuccess('Building removed successfully', new BuildingResource($building));
        } catch (\Throwable $th) {
            return respondError('Error removing building', null, 400);
        }
    }

    public function getBuildings()
    {
        $user = $this->userRepository->findById(auth()->id());

        $buildings = $user->buildings()->paginate(10);

        return respondSuccess('Building fetched successfully', PaginateResource($buildings, BuildingResource::class));
    }

    public function getAllAvailableBuildings(Request $request)
    {
        $search = $request->query('search');

        $buildings = $this->buildingRepository->all($search);


        return respondSuccess('Buildings fetched successfully', PaginateResource($buildings, BuildingResource::class));
    }

    public function getBuilding($building_id)
    {
        if (!isUuid($building_id)) {
            return respondError("Building not found", null, 404);
        }

        $building = $this->buildingRepository->findById($building_id);

        if (!$building) {
            return respondError("Building not found", null, 404);
        }

        $building = $this->buildingRepository->findById($building_id);

        return respondSuccess('Building fetched successfully', new BuildingResource($building));
    }

    public function getVehicles($building_id)
    {

        if (!isUuid($building_id)) {
            return respondError("Building not found", null, 404);
        }

        $building = $this->buildingRepository->findById($building_id);

        if (!$building) {
            return respondError("Building not found", null, 404);
        }

        return respondSuccess('Building vehicles fetched successfully', PaginateResource($building->vehicles()->paginate(10), VehicleResource::class));
    }

    public function getAvailableVehicles(GetAvailableVehiclesForBuilding $request, $building_id)
    {
        if (!isUuid($building_id)) {
            return respondError("Building not found", null, 404);
        }

        $building = $this->buildingRepository->findById($building_id);

        if (!$building) {
            return respondError("Building not found", null, 404);
        }

        $validated = (object) $request->validated();

        $start_time = Carbon::parse($validated->start);
        $end_time = Carbon::parse($validated->end);

        $vehicles_id = $building->vehicles()->pluck('id');

        $trips = $this->tripRepository->query()
            ->whereIn('vehicle_id', $vehicles_id)
            ->whereRaw('start_time >= ?', [$start_time])
            ->whereRaw('end_time + INTERVAL \'1 hour\' <= ?', [$end_time])
            ->get();

        $available_vehicles = $building->vehicles()->whereNotIn('id', $trips->pluck('vehicle_id'))->paginate(10);

        return respondSuccess('Available vehicles fetched successfully', PaginateResource($available_vehicles, VehicleResource::class));
    }
}
