<?php

namespace App\Services\Core;

use App\Actions\Cloud\CloudService;
use App\Enum\CloudTypeEnum;
use App\Enum\PaymentTypeEnum;
use App\Enum\TripPaymentTypeEnum;
use App\Http\Resources\Core\VehicleResource;
use App\Models\Package;
use App\Models\Product;
use App\Models\TripSetting;
use App\Repositories\Core\ReportRepository;
use App\Repositories\Core\VehicleRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Support\Facades\DB;

class TripService
{
    public function __construct(
        protected VehicleRepository $vehicleRepository,
        protected UserRepository $userRepository,
        protected ReportRepository $reportRepository,
        protected CloudService $cloudService,
    ) {
    }
    public function getTripCosting($validated)
    {
        $vehicle = $this->vehicleRepository->findById($validated->vehicle_id);

        $mins_difference = calculateMinutesDifference($validated->start_time, $validated->end_time);

        $price_per_minute = $vehicle->price_per_hour / 60;

        $total_amount = $mins_difference * $price_per_minute;

        $user = $this->userRepository->findById(auth()->id());

        $product = Product::all()->first();

        $subscribed = $user->subscribed($product->stripe_id);


        $settings = TripSetting::first();


        if ($subscribed) {

            $total_amount = $mins_difference / pricePerHourToPricePerMinute($settings->subscriber_price_per_hour);

            if ($user->subscription_balance < $total_amount) {

                $outstanding_after_subscription_balance_deduction = $total_amount - $user->subscription_balance;

                $data = [
                    'vehicle' => new VehicleResource($vehicle),
                    'hours' => $mins_difference / 60,
                    'amount' => $total_amount,
                    'tax' => calculatePercentageOfValue($settings->tax_percentage, $total_amount),
                    'total_cost' => $total_amount + calculatePercentageOfValue($settings->tax_percentage, $total_amount),
                    'payment_type' =>  TripPaymentTypeEnum::SUBSCRIPTION->value,
                    'has_outstanding' => $outstanding_after_subscription_balance_deduction > 0,
                    'outstanding' => $outstanding_after_subscription_balance_deduction,
                    'choose_payment_type_to_cover_outstanding' => $outstanding_after_subscription_balance_deduction > 0,
                    'wallet_amount' => centToDollar($user->wallet),
                    'subscription_balance' => centToDollar($user->subscription_balance)
                ];
            } else {

                $data = [
                    'vehicle' => new VehicleResource($vehicle),
                    'hours' => $mins_difference / 60,
                    'amount' => $total_amount,
                    'tax' => calculatePercentageOfValue($settings->tax_percentage, $total_amount),
                    'total_cost' => $total_amount + calculatePercentageOfValue($settings->tax_percentage, $total_amount),
                    'payment_type' =>  TripPaymentTypeEnum::SUBSCRIPTION->value,
                    'has_outstanding' => false,
                    'outstanding' => 0,
                    'choose_payment_type_to_cover_outstanding' => false,
                    'wallet_amount' => centToDollar($user->wallet),
                    'subscription_balance' => centToDollar($user->subscription_balance)
                ];
            }
        } else {

            $data = [
                'vehicle' => new VehicleResource($vehicle),
                'hours' => $mins_difference / 60,
                'amount' => $total_amount,
                'tax' => calculatePercentageOfValue($settings->tax_percentage, $total_amount),
                'total_cost' => $total_amount + calculatePercentageOfValue($settings->tax_percentage, $total_amount),
                'payment_type' =>  TripPaymentTypeEnum::OTHERS->value,
                'has_outstanding' => false,
                'outstanding' => 0,
                'choose_payment_type_to_cover_outstanding' => false,
                'wallet_amount' => centToDollar($user->wallet)
            ];
        }

        return [
            'status' => true,
            'message' => 'trip costing fetched',
            'data' => $data
        ];
    }

    public function reportTrip($validated)
    {
        DB::beginTransaction();

        try {

            $created = $this->reportRepository->create([
                'trip_id' => $validated->trip_id,
                'description' => $validated->description,
                'type' => $validated->type,
            ]);

            foreach ($validated->images as $image) {
                $file_upload = $this->cloudService->upload(
                    file: $image,
                    provider: CloudTypeEnum::CLOUDINARY,
                    folder: 'reports',
                    owner_id: $created->id,
                    name: "report-for-trip" . $validated->trip_id,
                    type: 'reports',
                    extension: $image->getClientOriginalExtension()
                );

                if (!$file_upload['status']) {
                    DB::rollBack();
                    return [
                        'status' => false,
                        'message' =>  "Error uploading image",
                        'data' => null
                    ];
                }
            }

            DB::commit();

            return [
                'status' => true,
                'message' => 'Report sent successfully, awaiting review',
                'data' => null
            ];
        } catch (\Throwable $th) {
            logError($th->getMessage());
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'An error occurred during reporting',
                'data' => null
            ];
        }
    }

    public function createTrip($validated)
    {
        $vehicle = $this->vehicleRepository->findById($validated->vehicle_id);

        $mins_difference = calculateMinutesDifference($validated->start_time, $validated->end_time);

        $price_per_minute = dollarToCent($vehicle->price_per_hour)  / 60;

        $total_amount = $mins_difference * $price_per_minute;

        $user = $this->userRepository->findById(auth()->id());

        $product = Product::all()->first();

        $subscribed = $user->subscribed($product->stripe_id);


        $settings = TripSetting::first();


        if ($subscribed) {

            $total_amount = $mins_difference / dollarToCent(pricePerHourToPricePerMinute($settings->subscriber_price_per_hour));

            if ($user->subscription_balance < $total_amount) {

                $outstanding_after_subscription_balance_deduction = $total_amount - $user->subscription_balance;

                if (!$validated->charge_from) {
                    return [
                        'status' => false,
                        'message' => 'Subscription cannot cover trip, select where to charge outstanding.',
                        'data' => null
                    ];
                } elseif ($validated->charge_from === PaymentTypeEnum::WALLET->value) {
                    if ($user->wallet < $outstanding_after_subscription_balance_deduction) {
                        return [
                            'status' => false,
                            'message' => 'The amount in wallet cannot cover outstanding, please select another payment method.',
                            'data' => null
                        ];
                    }

                    // remove cash from subscription and wallet
                    // create trip
                    // create transaction
                } elseif ($validated->charge_from === PaymentTypeEnum::CARD->value) {
                    // get active card
                    // charge card async
                    // if successful remove from subscription
                    // create trip
                    // create transaction
                }
            } else {

                $data = [
                    'vehicle' => new VehicleResource($vehicle),
                    'hours' => $mins_difference / 60,
                    'amount' => $total_amount,
                    'tax' => calculatePercentageOfValue($settings->tax_percentage, $total_amount),
                    'total_cost' => $total_amount + calculatePercentageOfValue($settings->tax_percentage, $total_amount),
                    'payment_type' =>  TripPaymentTypeEnum::SUBSCRIPTION->value,
                    'has_outstanding' => false,
                    'outstanding' => 0,
                    'choose_payment_type_to_cover_outstanding' => false,
                    'wallet_amount' => centToDollar($user->wallet),
                    'subscription_balance' => centToDollar($user->subscription_balance)
                ];
            }
        } else {

            $data = [
                'vehicle' => new VehicleResource($vehicle),
                'hours' => $mins_difference / 60,
                'amount' => $total_amount,
                'tax' => calculatePercentageOfValue($settings->tax_percentage, $total_amount),
                'total_cost' => $total_amount + calculatePercentageOfValue($settings->tax_percentage, $total_amount),
                'payment_type' =>  TripPaymentTypeEnum::OTHERS->value,
                'has_outstanding' => false,
                'outstanding' => 0,
                'choose_payment_type_to_cover_outstanding' => false,
                'wallet_amount' => centToDollar($user->wallet)
            ];
        }

        return [
            'status' => true,
            'message' => 'trip costing fetched',
            'data' => $data
        ];
    }
}
