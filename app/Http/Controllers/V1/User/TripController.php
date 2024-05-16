<?php

namespace App\Http\Controllers\V1\User;

use App\Actions\Cloud\CloudService;
use App\Enum\CloudTypeEnum;
use App\Enum\PaymentTypeEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use App\Enum\TripPaymentTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetCostingRequest;
use App\Http\Requests\User\AddTimeRequest;
use App\Http\Requests\User\CreateTripRequest;
use App\Http\Requests\User\ReportTripRequest;
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

    public function getTrips()
    {
        $trips = $this->tripRepository->query()->where('user_id', auth()->id())->paginate(10);

        return respondSuccess('Trips fetched successfully', $trips);
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

    public function addExtraTime(AddTimeRequest $request)
    {
        $validated = (object) $request->validated();

        $response = $this->tripService->addExtraTime($validated);

        if (!$response['status']) {
            return respondError($response['message']);
        }

        return respondSuccess($response['message'], $response['data']);
    }

    public function startTrip()
    {
    }

    public function endTrip()
    {
    }

    public function payForTrip()
    {
    }

    public function reportTrip(ReportTripRequest $request)
    {
        $validated = (object) $request->validated();

        $response = $this->tripService->reportTrip($validated);

        if (!$response['status']) {
            return respondError($response['message']);
        }

        return respondSuccess($response['message'], $response['data']);
    }
}
