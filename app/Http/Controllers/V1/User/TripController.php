<?php

namespace App\Http\Controllers\V1\User;

use App\Actions\Cloud\CloudService;
use App\Enum\CloudTypeEnum;
use App\Enum\PaymentTypeEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use App\Enum\TripPaymentTypeEnum;
use App\Http\Controllers\Controller;
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



    public function getCosting(CreateTripRequest $request)
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

        $vehicle = $this->vehicleRepository->findById($validated->vehicle_id);

        // $price_per_minute = $vehicle->price_per_hour / 60;

        $mins_difference = calculateMinutesDifference($validated->start_time, $validated->end_time);


        // $total_amount = $mins_difference * $price_per_minute;


        $user = $this->userRepository->findById(auth()->id());

        $active_subscription = $user->activeSubscriptions()->first();

        $settings = TripSetting::first();


        // $tax_amount = calculatePercentageOfValue($settings->tax_percentage, $total_amount);

        if ($active_subscription) {

            $total_amount = $mins_difference / pricePerHourToPricePerMinute($settings->subscriber_price_per_hour);

            if ($active_subscription->subscription->balance < $total_amount) {


                $outstanding_after_subscription_balance_deduction = $total_amount - $active_subscription->subscription->balance;


                if ($outstanding_after_subscription_balance_deduction >= $user->wallet) {

                    // remove from subscription and remove outstanding from the wallet

                } else {

                    // charge from
                }




                return respondError("You do not have enough in your subscription to cover fare");
            }



            $balance = $active_subscription->subscription->balance - $total_amount;

            $user->unsubscribeSubscriptions()->updateExistingPivot($active_subscription->id, [
                'balance' => $balance
            ]);

            $payment_type = PaymentTypeEnum::SUBSCRIPTION;
        } else {
            // TODO:add card or charge card

            $payment_type = PaymentTypeEnum::CARD;
        }







        // calculate amount based on the vehicle

        // check if user has subscription

        // check if they has any unit left

        // check if unit will cover ride

        // tell them the amount the unit can cover

        // if

        $trip = $this->tripRepository->create([
            'user_id' => auth()->id(),
            'vehicle_id' => $validated->vehicle_id,
            'start_time' => $validated->start_time,
            'end_time' => $validated->end_time,
        ]);


        $payment = TripTransaction::create([
            'trip_id' => $trip->id,
            'building_id' => $trip->vehicle->building->id,
            'vehicle_id' => $trip->vehicle->id,
            'user_id' => auth()->id(),
            'reference' => generateReference(),
            'public_id' => uuid(),
            'payment_type' => $payment_type,
            'amount' => $total_amount,
        ]);

        $transaction = $this->transactionRepository->create(
            [
                'amount' => $payment->amount,
                'title' => "Trip payment",
                'narration' => "Payment for trip",
                'status' => TransactionStatusEnum::PENDING->value,
                'type' => TransactionTypeEnum::TRIP->value,
                'entry' => "debit",
                'channel' => 'web',
            ]
        );

        $payment->transaction()->save($transaction);
    }

    // public function getTripCost(CreateTripRequest $request)
    // {
    //     $validated = (object) $request->validated();

    //     $vehicle = $this->vehicleRepository->findById($validated->vehicle_id);

    //     // $price_per_minute = $vehicle->price_per_hour / 60;

    //     $mins_difference = calculateMinutesDifference($validated->start_time, $validated->end_time);


    //     // $total_amount = $mins_difference * $price_per_minute;


    //     $user = $this->userRepository->findById(auth()->id());

    //     $active_subscription = $user->activeSubscriptions()->first();

    //     $settings = TripSetting::first();


    //     // $tax_amount = calculatePercentageOfValue($settings->tax_percentage, $total_amount);

    //     if ($active_subscription) {

    //         $total_amount = $mins_difference / pricePerHourToPricePerMinute($settings->subscriber_price_per_hour);

    //         if ($active_subscription->subscription->balance < $total_amount) {

    //             return respondSuccess("Choose channel pay outstanding");



    //             // return respondError("You do not have enough in your subscription to cover fare");
    //         }
    //     } else {
    //         // return respondSuccess("User can book ride", [
    //         //     'can_book' => true,
    //         //     'amount'
    //         //     'outstanding'=> 0
    //         // ]);
    //     }







    //     // calculate amount based on the vehicle

    //     // check if user has subscription

    //     // check if they has any unit left

    //     // check if unit will cover ride

    //     // tell them the amount the unit can cover

    //     // if

    //     $trip = $this->tripRepository->create([
    //         'user_id' => auth()->id(),
    //         'vehicle_id' => $validated->vehicle_id,
    //         'start_time' => $validated->start_time,
    //         'end_time' => $validated->end_time,
    //     ]);
    // }

    public function addExtraTime()
    {
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
