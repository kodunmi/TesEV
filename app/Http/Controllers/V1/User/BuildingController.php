<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AddBuildingRequest;
use App\Repositories\Core\BuildingRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function __construct(
        protected BuildingRepository $buildingRepository,
        protected UserRepository $userRepository
    ) {
    }
    public function addBuilding(AddBuildingRequest $request)
    {
        try {

            $user = $this->userRepository->findById(auth()->id());

            $validated = (object) $request->validated();

            $building = $this->buildingRepository->findById($validated->building_id);

            $user->buildings()->attach($building->id);

            return respondSuccess('Building added successfully', $building);
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

            return respondSuccess('Building removed successfully', $building);
        } catch (\Throwable $th) {
            return respondError('Error removing building', null, 400);
        }
    }

    public function getBuildings()
    {
        $user = $this->userRepository->findById(auth()->id());

        $buildings = $user->buildings()->paginate(10);

        return respondSuccess('Building fetched successfully', $buildings);
    }

    public function getAllAvailableBuildings(Request $request)
    {
        $search = $request->query('search');

        $buildings = $this->buildingRepository->all($search);

        return respondSuccess('Buildings fetched successfully', $buildings);
    }

    public function getBuilding($building_id)
    {
        $building = $this->buildingRepository->findById($building_id);

        return respondSuccess('Building fetched successfully', $building);
    }
}
