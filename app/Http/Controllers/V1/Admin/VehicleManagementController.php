<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Core\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleManagementController extends Controller
{
    public function getVehicles(Request $request)
    {
        // Retrieve query parameters
        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 4);
        $sortKey = $request->input('sortKey', 'created_at');
        $sortDirection = $request->input('sortDirection', 'asc');
        $buildingId = $request->input('building_id');
        $filters = json_decode($request->input('filters', '{}'), true);

        // Start building the query
        $query = Vehicle::query();

        // Filter by building_id if provided
        if (!empty($buildingId)) {
            $query->where('building_id', $buildingId);
        }

        // Apply filters if provided
        if (!empty($filters)) {
            if (!empty($filters['createdAt'][0])) {
                $query->whereDate('created_at', '>=', $filters['createdAt'][0]);
            }
            if (!empty($filters['createdAt'][1])) {
                $query->whereDate('created_at', '<=', $filters['createdAt'][1]);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        }

        // Apply sorting
        $query->orderBy($sortKey, $sortDirection);

        // Paginate the results
        $vehicles = $query->paginate($perPage, ['*'], 'page', $page);

        return respondSuccess("Vehicle fetched successfully", paginateResource($vehicles, VehicleResource::class));
    }

    public function getVehicle()
    {
    }
    public function getVehicleAnalytics()
    {
    }

    public function getVehicleTrips()
    {
    }

    public function addVehicles()
    {
    }

    public function updateVehicle()
    {
    }

    public function deleteVehicle()
    {
    }

    public function toggleVehicleStatus()
    {
    }
}
