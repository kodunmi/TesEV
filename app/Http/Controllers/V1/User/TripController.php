<?php

namespace App\Http\Controllers\V1\User;

use App\Actions\Cloud\CloudService;
use App\Enum\CloudTypeEnum;
use App\Enum\PaymentTypeEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use App\Enum\TripPaymentTypeEnum;
use App\Enum\TripStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetCostingRequest;
use App\Http\Requests\User\AddTimeRequest;
use App\Http\Requests\User\CreateTripRequest;
use App\Http\Requests\User\EndTripRequest;
use App\Http\Requests\User\ReportTripRequest;
use App\Http\Resources\Core\MultiTripResource;
use App\Http\Resources\Core\TripResource;
use App\Models\TripMetaData;
use App\Models\TripSetting;
use App\Models\TripTransaction;
use App\Repositories\Core\ReportRepository;
use App\Repositories\Core\TransactionRepository;
use App\Repositories\Core\TripRepository;
use App\Repositories\Core\VehicleRepository;
use App\Repositories\User\UserRepository;
use App\Services\Core\TripService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    public function __construct(
        protected TripRepository $tripRepository,
        protected TransactionRepository $transactionRepository,
        protected VehicleRepository $vehicleRepository,
        protected UserRepository $userRepository,
        protected ReportRepository $reportRepository,
        protected CloudService $cloudService,
        protected TripService $tripService
    ) {
    }

    public function getTrips(Request $request)
    {
        $status = $request->query('status', [TripStatusEnum::RESERVED->value, TripStatusEnum::STARTED->value]);

        $trips = $this->tripRepository->query()->where('user_id', auth()->id())->whereIn('status', transformStringToArray($status))->paginate(10);

        return respondSuccess('Trips fetched successfully', MultiTripResource::collection($trips));
    }


    public function getTrip($trip_id)
    {
        $trip = $this->tripRepository->findById($trip_id);

        if (!$trip) {
            return respondError('Trip not found', null, 404);
        }

        return respondSuccess('Trips fetched successfully', new TripResource($trip));
    }


    public function getCosting(GetCostingRequest $request)
    {

        $validated = (object) $request->validated();

        $response = $this->tripService->getTripCosting($validated);

        if (!$response['status']) {
            return respondError('Error costing trip');
        }

        return respondSuccess($response['message'], $response['data']);
    }

    public function createTrip(CreateTripRequest $request)
    {

        $validated = (object) $request->validated();


        $response = $this->tripService->createTrip($validated);

        if (!$response['status']) {
            return respondError($response['message']);
        }

        return respondSuccess($response['message'], $response['data']);
    }

    public function addExtraTime(AddTimeRequest $request, $trip_id)
    {
        $validated = (object) $request->validated();

        $response = $this->tripService->addExtraTime($validated, $trip_id);

        if (!$response['status']) {
            return respondError($response['message']);
        }

        return respondSuccess($response['message'], $response['data']);
    }

    public function startTrip($trip_id)
    {

        try {
            $trip = $this->tripRepository->findById($trip_id);

            if (!$trip) {
                return respondError('Trip not found', null, 404);
            }

            if ($trip->started_at) {
                return respondError('Trip already started', null, 400);
            }

            $trip->update([
                'started_at' => now()
            ]);

            $trip->refresh();

            return respondSuccess('Trip started successfully', new TripResource($trip));
        } catch (\Throwable $th) {
            return respondError('Error starting trip, try again', null, 400);
        }
    }

    public function endTrip(EndTripRequest $request, $trip_id)
    {
        try {

            $validated = (object) $request->validated();



            $trip = $this->tripRepository->findById($trip_id);

            if (!$trip) {
                return respondError('Trip not found', null, 404);
            }

            if (!$trip->started_at) {
                return respondError('You have not started the trip', null, 400);
            }

            if ($trip->ended_at) {
                return respondError('Trip already ended', null, 400);
            }

            $trip->update([
                'ended_at' => now(),
                'status' => TripStatusEnum::ENDED->value
            ]);

            $trip_meta =  TripMetaData::make([
                'remove_belongings' => $validated->remove_belongings,
                'remove_trash' => $validated->remove_trash,
                'plug_vehicle' => $validated->plug_vehicle,
                'public_id' => uuid()
            ]);

            $trip->tripMetaData()->save($trip_meta);

            $trip->refresh();

            return respondSuccess('Trip ended successfully', new TripResource($trip));
        } catch (\Throwable $th) {
            logError($th->getMessage(), ['error' => $th]);
            return respondError('Error starting trip, try again', null, 400);
        }
    }

    public function cancelTrip($trip_id)
    {

        $response = $this->tripService->cancelTrip($trip_id);

        if (!$response['status']) {
            return respondError($response['message']);
        }

        return respondSuccess($response['message'], $response['data']);
    }

    public function payForTrip()
    {
    }

    public function reportTrip(ReportTripRequest $request, $trip_id)
    {
        $validated = (object) $request->validated();

        $response = $this->tripService->reportTrip($validated, $trip_id);

        if (!$response['status']) {
            return respondError($response['message']);
        }

        return respondSuccess($response['message'], $response['data']);
    }
}
