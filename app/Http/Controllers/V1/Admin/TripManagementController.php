<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Core\MultiTripResource;
use App\Repositories\Core\TripRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripManagementController extends Controller
{
    public function __construct(protected TripRepository $tripRepository)
    {
    }
    public function getTrips(Request $request)
    {
        $query = $this->tripRepository->query();
        $isFiltered = false;

        $per_page = $request->query('perPage', 10);

        $queryParams = camelToSnake($request->all());

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('searchTerm') && $request->searchTerm) {
            $searchTerm = strtolower($request->has('searchTerm'));
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(booking_id) LIKE ?', ["%$searchTerm%"])
                    ->orWhereRaw('LOWER(status) LIKE ?', ["%$searchTerm%"]);
            });
            $isFiltered = true;
        }

        if (isset($queryParams['filters'])) {
            $filters = json_decode($queryParams['filters'], true);

            if (isset($filters['status']) &&  $filters['status'] !== "") {
                $query->where('trips.status', $filters['status']);
            }

            if (isset($filters['createdAt'])) {

                if (!is_null($filters['createdAt'][0]) && !is_null($filters['createdAt'][1])) {
                    $query->whereBetween('created_at', $filters['createdAt']);
                }
            }
            $isFiltered = true;
        }

        if ($request->has('sortKey') && !is_null(convertNullString($request->sortKey)) && $request->has('sortDirection') && !is_null(convertNullString($request->sortDirection))) {
            $sortKey = $request->sortKey;
            $direction = $request->sortDirection === 'desc' ? 'desc' : 'asc';

            if ($sortKey === 'amount') {
                // Join the related tripTransactions table for sorting by amount
                $query->join('trip_transactions', 'trips.id', '=', 'trip_transactions.trip_id')
                    ->select('trips.*')
                    ->groupBy('trips.id')
                    ->orderByRaw("MIN(trip_transactions.amount) $direction");
            } else {
                $query->orderBy(camelToSnake($sortKey), $direction);
            }
        }


        $trips = $query->paginate($per_page);

        return respondSuccess('Trips fetched successfully', paginateResource($trips, MultiTripResource::class));
    }

    public function getTrip()
    {
    }

    public function updateTrip()
    {
    }

    public function getTripsAnalytics()
    {
    }

    public function addTripCharge()
    {
    }

    public function getTripCharges()
    {
    }

    public function toggleTripStatus()
    {
    }

    public function deleteTrip()
    {
    }
}
