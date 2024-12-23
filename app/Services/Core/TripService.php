<?php

namespace App\Services\Core;

use App\Actions\Cloud\CloudService;
use App\Actions\Notifications\NotificationService;
use App\Actions\Payment\StripeService;
use App\Enum\ChargeTypeEnum;
use App\Enum\CloudTypeEnum;
use App\Enum\NotificationTypeEnum;
use App\Enum\PaymentTypeEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use App\Enum\TripPaymentTypeEnum;
use App\Enum\TripStatusEnum;
use App\Enum\TripTransactionTypeEnum;
use App\Http\Resources\Core\TripResource;
use App\Http\Resources\Core\VehicleResource;
use App\Jobs\Core\Trip\ProcessExtraTimePaymentJob;
use App\Jobs\Core\Trip\ProcessRefundJob;
use App\Models\Package;
use App\Models\Product;
use App\Models\TripMetaData;
use App\Models\TripSetting;
use App\Models\TripTransaction;
use App\Models\Vehicle;
use App\Repositories\Core\ReportRepository;
use App\Repositories\Core\TransactionRepository;
use App\Repositories\Core\TripRepository;
use App\Repositories\Core\VehicleRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class TripService
{
    public function __construct(
        protected VehicleRepository $vehicleRepository,
        protected UserRepository $userRepository,
        protected ReportRepository $reportRepository,
        protected CloudService $cloudService,
        protected TripRepository $tripRepository,
        protected TransactionRepository $transactionRepository,
        protected StripeService $stripeService
    ) {}
    public function getTripCosting($validated)
    {
        $vehicle = $this->vehicleRepository->findById($validated->vehicle_id);

        $mins_difference = calculateMinutesDifference($validated->start_time, $validated->end_time);

        $price_per_minute = $vehicle->price_per_hour / 60;

        $total_amount_before_tax = $mins_difference * $price_per_minute;

        $user = $this->userRepository->findById(auth()->id());

        $product = Product::all()->first();

        $subscribed = $user->subscribed($product->stripe_id);


        $settings = TripSetting::first();

        $total_amount = $total_amount_before_tax + calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax);


        if ($subscribed) {

            $total_amount_before_tax = $mins_difference / dollarToCent(pricePerHourToPricePerMinute($settings->subscriber_price_per_hour));

            $total_amount = $total_amount_before_tax + calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax);


            if ($user->subscription_balance < $total_amount_before_tax) {

                $outstanding_after_subscription_balance_deduction_before_tax = $total_amount_before_tax - $user->subscription_balance;
                $outstanding_after_subscription_balance_deduction_after_tax =  $outstanding_after_subscription_balance_deduction_before_tax + calculatePercentageOfValue($settings->tax_percentage, $outstanding_after_subscription_balance_deduction_before_tax);

                $data = [
                    'vehicle' => new VehicleResource($vehicle),
                    'hours' => $mins_difference / 60,
                    'amount' => $total_amount_before_tax,
                    'tax' => 0.00,
                    'total_cost' => $total_amount_before_tax,
                    'payment_type' =>  TripPaymentTypeEnum::SUBSCRIPTION->value,
                    'has_outstanding' => $outstanding_after_subscription_balance_deduction_before_tax > 0,
                    'outstanding' => $outstanding_after_subscription_balance_deduction_before_tax,
                    'choose_payment_type_to_cover_outstanding' => $outstanding_after_subscription_balance_deduction_before_tax > 0,
                    'wallet_amount' => centToDollar($user->wallet),
                    'subscription_balance' => centToDollar($user->subscription_balance)
                ];
            } else {

                $data = [
                    'vehicle' => new VehicleResource($vehicle),
                    'hours' => $mins_difference / 60,
                    'amount' => $total_amount_before_tax,
                    'tax' => 0.00,
                    'total_cost' => $total_amount_before_tax,
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
                'amount' => $total_amount_before_tax,
                'tax' => calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax),
                'total_cost' => $total_amount,
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

    public function reportTrip($validated, $trip_id)
    {
        DB::beginTransaction();

        try {

            $trip = $this->tripRepository->findById($trip_id);

            if (!$trip) {
                return [
                    'status' => false,
                    'message' => 'Trip not found',
                    'data' => null
                ];
            }

            $created = $this->reportRepository->create([
                'trip_id' => $trip_id,
                'description' => $validated->description,
                'type' => $validated->type,
            ]);

            foreach ($validated->images as $image) {
                $file_upload = $this->cloudService->upload(
                    file: $image,
                    provider: CloudTypeEnum::CLOUDINARY,
                    folder: 'reports',
                    owner_id: $created->id,
                    name: "report-for-trip" . $trip_id,
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

        if (!isEndTimeGreaterThanStartTime($validated->start_time, $validated->end_time)) {
            return [
                'status' => false,
                'message' => 'End time should be in the future',
                'data' => null
            ];
        }

        $mins_difference = calculateMinutesDifference($validated->start_time, $validated->end_time);

        $price_per_minute = roundToWholeNumber(dollarToCent($vehicle->price_per_hour)  / 60);

        $total_amount_before_tax = $mins_difference * $price_per_minute;

        $user = $this->userRepository->findById(auth()->id());

        $product = Product::all()->first();

        $subscribed = $user->subscribed($product->stripe_id);

        $end_time = $validated->end_time;


        $settings = TripSetting::first();

        $total_amount = $total_amount_before_tax + calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax);


        if (!$this->checkVehicleAvailability($validated)) {
            return [
                'status' => false,
                'message' => 'Sorry! The selected vehicle is not available anymore, select a different time or a different vehicle',
                'data' => null
            ];
        }

        if ($subscribed) {

            $total_amount_before_tax = $mins_difference * roundToWholeNumber(dollarToCent(pricePerHourToPricePerMinute($settings->subscriber_price_per_hour)));

            $total_amount = $total_amount_before_tax + calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax);


            if ($user->subscription_balance < $total_amount) {

                $outstanding_after_subscription_balance_deduction_before_tax = $total_amount_before_tax - $user->subscription_balance;
                $outstanding_after_subscription_balance_deduction_after_tax =  $outstanding_after_subscription_balance_deduction_before_tax + calculatePercentageOfValue($settings->tax_percentage, $outstanding_after_subscription_balance_deduction_before_tax);


                if (!isset($validated->charge_from)) {
                    return [
                        'status' => false,
                        'message' => 'Subscription cannot cover trip, select where to charge outstanding.',
                        'data' => null
                    ];
                } elseif ($validated->charge_from === PaymentTypeEnum::WALLET->value) {
                    if ($user->wallet < $outstanding_after_subscription_balance_deduction_after_tax) {
                        return [
                            'status' => false,
                            'message' => 'The amount in wallet cannot cover outstanding, please select another payment method.',
                            'data' => null
                        ];
                    }

                    // remove from sub and wallet
                    $removed_charge =  $user->update([
                        'subscription_balance' => 0,
                        'wallet' => $user->wallet - $outstanding_after_subscription_balance_deduction_after_tax
                    ]);

                    if ($removed_charge) {
                        $trip = $this->tripRepository->create([
                            'user_id' => $user->id,
                            'vehicle_id' => $validated->vehicle_id,
                            'start_time' => $validated->start_time,
                            'end_time' => $end_time,
                            'status' => TripStatusEnum::RESERVED->value,
                            'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $outstanding_after_subscription_balance_deduction_before_tax),
                            'tax_percentage' => $settings->tax_percentage
                        ]);

                        $payment = TripTransaction::create([
                            'trip_id' => $trip->id,
                            'building_id' => $trip->vehicle->building->id,
                            'vehicle_id' => $trip->vehicle->id,
                            'user_id' => auth()->id(),
                            'reference' => generateReference(),
                            'public_id' => uuid(),
                            'status' => TransactionStatusEnum::SUCCESSFUL->value,
                            'amount' => $total_amount_before_tax,
                            'total_amount' => $total_amount,
                            'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $outstanding_after_subscription_balance_deduction_before_tax),
                            'tax_percentage' => $settings->tax_percentage,
                            'start_time' => $validated->start_time,
                            'end_time' => $end_time,
                            'rate' => $vehicle->price_per_hour
                        ]);


                        $transaction_one = $this->transactionRepository->create(
                            [
                                'user_id' => $user->id,
                                'amount' => $user->subscription_balance,
                                'total_amount' => $user->subscription_balance,
                                'title' => "Payment for trip",
                                'narration' => "Part payment of " . Number::currency(centToDollar($user->subscription_balance))  . " for trip " . $trip->booking_id,
                                'status' => TransactionStatusEnum::SUCCESSFUL->value,
                                'type' => TransactionTypeEnum::TRIP->value,
                                'entry' => "debit",
                                'channel' => PaymentTypeEnum::SUBSCRIPTION->value,
                                'tax_amount' => 0.00,
                                'tax_percentage' => 0
                            ]
                        );

                        $transaction_two = $this->transactionRepository->create(
                            [
                                'user_id' => $user->id,
                                'amount' => $outstanding_after_subscription_balance_deduction_before_tax,
                                'total_amount' => $outstanding_after_subscription_balance_deduction_after_tax,
                                'title' => "Payment for trip",
                                'narration' => "Part payment of " . Number::currency(centToDollar($outstanding_after_subscription_balance_deduction_after_tax))  . " for trip " . $trip->booking_id,
                                'status' => TransactionStatusEnum::SUCCESSFUL->value,
                                'type' => TransactionTypeEnum::TRIP->value,
                                'entry' => "debit",
                                'channel' => PaymentTypeEnum::WALLET->value,
                                'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $outstanding_after_subscription_balance_deduction_before_tax),
                                'tax_percentage' => $settings->tax_percentage
                            ]
                        );

                        logInfo('error', [
                            'transaction_one' => $transaction_one,
                            'transaction_two' => $transaction_two
                        ]);

                        $payment->transactions()->saveMany([
                            $transaction_one,
                            $transaction_two
                        ]);


                        $notification = new NotificationService($user);

                        $notification
                            ->setBody("Your trip has been reserved successfully, your booking id is $trip->booking_id")
                            ->setTitle('Trip booked successfully')
                            ->setUrl('http://google.com')
                            ->setType(NotificationTypeEnum::TRIP_BOOKED)
                            ->sendPushNotification()
                            ->sendInAppNotification();

                        return [
                            'status' => true,
                            'message' => "Your trip has been reserved successfully, your booking id is $trip->booking_id",
                            'data' => $trip
                        ];
                    }
                } elseif ($validated->charge_from === PaymentTypeEnum::CARD->value) {

                    // get active card
                    $active_card = $user->activeCard;

                    if (!$active_card) {
                        return [
                            'status' => false,
                            'message' => 'You need to have an active card for card transactions',
                            'data' => null
                        ];
                    }

                    $trip = $this->tripRepository->create([
                        'user_id' => $user->id,
                        'vehicle_id' => $validated->vehicle_id,
                        'start_time' => $validated->start_time,
                        'end_time' => $end_time,
                        'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax),
                        'tax_percentage' => $settings->tax_percentage,
                    ]);

                    $payment = TripTransaction::create([
                        'trip_id' => $trip->id,
                        'building_id' => $trip->vehicle->building->id,
                        'vehicle_id' => $trip->vehicle->id,
                        'user_id' => $user->id,
                        'status' => TransactionStatusEnum::PENDING->value,
                        'reference' => generateReference(),
                        'public_id' => uuid(),
                        'amount' => $total_amount_before_tax,
                        'total_amount' => $total_amount,
                        'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $outstanding_after_subscription_balance_deduction_before_tax),
                        'tax_percentage' => $settings->tax_percentage,
                        'start_time' => $validated->start_time,
                        'end_time' => $end_time,
                        'rate' => $vehicle->price_per_hour
                    ]);


                    $transaction_one = $this->transactionRepository->create(
                        [
                            'user_id' => $user->id,
                            'amount' => $user->subscription_balance,
                            'total_amount' => $user->subscription_balance,
                            'title' => "Payment for trip",
                            'narration' => "Part payment of " . Number::currency(centToDollar($user->subscription_balance))  . " for trip " . $trip->booking_id,
                            'status' => TransactionStatusEnum::PENDING->value,
                            'type' => TransactionTypeEnum::TRIP->value,
                            'entry' => "debit",
                            'channel' => PaymentTypeEnum::SUBSCRIPTION->value,
                            'tax_amount' => 0.00,
                            'tax_percentage' => 0
                        ]
                    );

                    $transaction_two = $this->transactionRepository->create(
                        [
                            'user_id' => $user->id,
                            'amount' => $outstanding_after_subscription_balance_deduction_before_tax,
                            'total_amount' => $outstanding_after_subscription_balance_deduction_after_tax,
                            'title' => "Payment for trip",
                            'narration' => "Part payment of " . Number::currency(centToDollar($outstanding_after_subscription_balance_deduction_after_tax))  . " for trip " . $trip->booking_id,
                            'status' => TransactionStatusEnum::PENDING->value,
                            'type' => TransactionTypeEnum::TRIP->value,
                            'entry' => "debit",
                            'channel' => PaymentTypeEnum::CARD->value,
                            'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $outstanding_after_subscription_balance_deduction_before_tax),
                            'tax_percentage' => $settings->tax_percentage
                        ]
                    );

                    $payment->transactions()->saveMany([
                        $transaction_one,
                        $transaction_two
                    ]);

                    // charge card async
                    $charge_card = $this->stripeService->chargeCard(
                        $outstanding_after_subscription_balance_deduction_after_tax,
                        $user->id,
                        [
                            'trip_id' => $trip->id,
                            'trip_transaction_id' => $payment->id,
                            'type' => ChargeTypeEnum::TRIP_FUND->value,
                            'subscription_balance' => 0
                        ]
                    );

                    if (!$charge_card['status']) {
                        updateTripStatus($trip, $payment, TripStatusEnum::CANCELED, TransactionStatusEnum::FAILED);
                        return [
                            'status' => false,
                            'message' => $charge_card['message'],
                            'data' => $charge_card['data']
                        ];
                    }

                    $transaction_two->update([
                        'object' => $charge_card['data']
                    ]);


                    $notification = new NotificationService($user);

                    $notification
                        ->setBody("Transaction has been initiated, we will notify you soon")
                        ->setTitle('Transaction initiated successfully')
                        ->setUrl('http://google.com')
                        ->setType(NotificationTypeEnum::TRIP_BOOKED)
                        ->sendPushNotification()
                        ->sendInAppNotification();

                    return [
                        'status' => true,
                        'message' => 'Transaction has been initiated, we will notify when your trip is booked',
                        'data' => $trip
                    ];
                }
            } else {

                $removed_charge =  $user->update([
                    'subscription_balance' => $user->subscription_balance - $total_amount_before_tax,
                ]);

                if ($removed_charge) {
                    $trip = $this->tripRepository->create([
                        'user_id' => $user->id,
                        'vehicle_id' => $validated->vehicle_id,
                        'start_time' => $validated->start_time,
                        'end_time' => $end_time,
                        'status' => TripStatusEnum::RESERVED->value,
                        'tax_amount' => 0.00,
                        'tax_percentage' => 0
                    ]);

                    $payment = TripTransaction::create([
                        'trip_id' => $trip->id,
                        'building_id' => $trip->vehicle->building->id,
                        'vehicle_id' => $trip->vehicle->id,
                        'user_id' => $user->id,
                        'reference' => generateReference(),
                        'public_id' => uuid(),
                        'status' => TransactionStatusEnum::SUCCESSFUL->value,
                        'amount' => $total_amount_before_tax,
                        'total_amount' => $total_amount_before_tax,
                        'tax_amount' => 0.00,
                        'tax_percentage' => 0,
                        'start_time' => $validated->start_time,
                        'end_time' => $end_time,
                        'rate' => $settings->subscriber_price_per_hour
                    ]);


                    $transaction = $this->transactionRepository->create(
                        [
                            'user_id' => $user->id,
                            'amount' => $total_amount_before_tax,
                            'total_amount' => $total_amount_before_tax,
                            'title' => "Payment for trip",
                            'narration' => "Part payment of " . Number::currency(centToDollar($total_amount_before_tax))  . " for trip " . $trip->booking_id,
                            'status' => TransactionStatusEnum::SUCCESSFUL->value,
                            'type' => TransactionTypeEnum::TRIP->value,
                            'entry' => "debit",
                            'channel' => PaymentTypeEnum::SUBSCRIPTION->value,
                            'tax_amount' => 0.00,
                            'tax_percentage' => 0
                        ]
                    );

                    $payment->transactions()->save($transaction);


                    $notification = new NotificationService($user);

                    $notification
                        ->setBody("Your trip has been reserved successfully, your booking id is $trip->booking_id")
                        ->setTitle('Trip booked successfully')
                        ->setUrl('http://google.com')
                        ->setType(NotificationTypeEnum::TRIP_BOOKED)
                        ->sendPushNotification()
                        ->sendInAppNotification();

                    return [
                        'status' => true,
                        'message' => "Your trip has been reserved successfully, your booking id is $trip->booking_id",
                        'data' => $trip
                    ];
                }
            }
        } else {


            if (!isset($validated->charge_from)) {
                return [
                    'status' => false,
                    'message' => 'Please select where to charge from',
                    'data' => null
                ];
            } elseif ($validated->charge_from === PaymentTypeEnum::WALLET->value) {

                if ($user->wallet < $total_amount) {
                    return [
                        'status' => false,
                        'message' => 'The amount in wallet cannot cover outstanding, please select another payment method.',
                        'data' => null
                    ];
                }

                // remove from sub and wallet
                $removed_charge =  $user->update([
                    'subscription_balance' => 0,
                    'wallet' => $user->wallet - $total_amount
                ]);

                if ($removed_charge) {
                    $trip = $this->tripRepository->create([
                        'user_id' => $user->id,
                        'vehicle_id' => $validated->vehicle_id,
                        'start_time' => $validated->start_time,
                        'end_time' => $end_time,
                        'status' => TripStatusEnum::RESERVED->value,
                        'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax),
                        'tax_percentage' => $settings->tax_percentage
                    ]);

                    $payment = TripTransaction::create([
                        'trip_id' => $trip->id,
                        'building_id' => $trip->vehicle->building->id,
                        'vehicle_id' => $trip->vehicle->id,
                        'user_id' => auth()->id(),
                        'reference' => generateReference(),
                        'public_id' => uuid(),
                        'status' => TransactionStatusEnum::SUCCESSFUL->value,
                        'amount' => $total_amount_before_tax,
                        'total_amount' => $total_amount,
                        'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax),
                        'tax_percentage' => $settings->tax_percentage,
                        'start_time' => $validated->start_time,
                        'end_time' => $end_time,
                        'rate' => $vehicle->price_per_hour
                    ]);

                    $transaction = $this->transactionRepository->create(
                        [
                            'user_id' => $user->id,
                            'amount' => $total_amount_before_tax,
                            'total_amount' => $total_amount,
                            'title' => "Payment for trip",
                            'narration' => "Payment of " . Number::currency(centToDollar($total_amount))  . " for trip " . $trip->booking_id,
                            'status' => TransactionStatusEnum::SUCCESSFUL->value,
                            'type' => TransactionTypeEnum::TRIP->value,
                            'entry' => "debit",
                            'channel' => PaymentTypeEnum::WALLET->value,
                            'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax),
                            'tax_percentage' => $settings->tax_percentage
                        ]
                    );

                    $payment->transactions()->save($transaction);


                    $notification = new NotificationService($user);

                    $notification
                        ->setBody("Your trip has been reserved successfully, your booking id is $trip->booking_id")
                        ->setTitle('Trip booked successfully')
                        ->setUrl('http://google.com')
                        ->setType(NotificationTypeEnum::TRIP_BOOKED)
                        ->sendPushNotification()
                        ->sendInAppNotification();

                    return [
                        'status' => true,
                        'message' => "Your trip has been reserved successfully, your booking id is $trip->booking_id",
                        'data' => $trip
                    ];
                }
            } elseif ($validated->charge_from === PaymentTypeEnum::CARD->value) {

                // get active card
                $active_card = $user->activeCard;

                if (!$active_card) {
                    return [
                        'status' => false,
                        'message' => 'You need to have an active card for card transactions',
                        'data' => null
                    ];
                }

                $trip = $this->tripRepository->create([
                    'user_id' => $user->id,
                    'vehicle_id' => $validated->vehicle_id,
                    'start_time' => $validated->start_time,
                    'end_time' => $end_time,
                    'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax),
                    'tax_percentage' => $settings->tax_percentage
                ]);

                $payment = TripTransaction::create([
                    'trip_id' => $trip->id,
                    'building_id' => $trip->vehicle->building->id,
                    'vehicle_id' => $trip->vehicle->id,
                    'user_id' => $user->id,
                    'status' => TransactionStatusEnum::PENDING->value,
                    'reference' => generateReference(),
                    'public_id' => uuid(),
                    'amount' => $total_amount_before_tax,
                    'total_amount' => $total_amount,
                    'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax),
                    'tax_percentage' => $settings->tax_percentage,
                    'start_time' => $validated->start_time,
                    'end_time' => $end_time,
                    'rate' => $vehicle->price_per_hour
                ]);

                $transaction = $this->transactionRepository->create(
                    [
                        'user_id' => $user->id,
                        'amount' => $total_amount_before_tax,
                        'total_amount' => $total_amount,
                        'title' => "Payment for trip",
                        'narration' => "Part payment of " . Number::currency(centToDollar($total_amount))  . " for trip " . $trip->booking_id,
                        'status' => TransactionStatusEnum::PENDING->value,
                        'type' => TransactionTypeEnum::TRIP->value,
                        'entry' => "debit",
                        'channel' => PaymentTypeEnum::CARD->value,
                        'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax),
                        'tax_percentage' => $settings->tax_percentage
                    ]
                );

                $payment->transactions()->save($transaction);


                // charge card async
                $charge_card = $this->stripeService->chargeCard(
                    $total_amount,
                    $user->id,
                    [
                        'trip_id' => $trip->id,
                        'trip_transaction_id' => $payment->id,
                        'type' => ChargeTypeEnum::TRIP_FUND->value
                    ]
                );

                if (!$charge_card['status']) {

                    updateTripStatus($trip, $payment, TripStatusEnum::CANCELED, TransactionStatusEnum::FAILED);

                    return [
                        'status' => false,
                        'message' => $charge_card['message'],
                        'data' => $charge_card['data']
                    ];
                }

                $transaction->update([
                    'object' => $charge_card['data']
                ]);


                $notification = new NotificationService($user);

                $notification
                    ->setBody("Transaction has been initiated, we will notify you soon")
                    ->setTitle('Transaction initiated successfully')
                    ->setUrl('http://google.com')
                    ->setType(NotificationTypeEnum::TRIP_BOOKED)
                    ->sendPushNotification()
                    ->sendInAppNotification();

                return [
                    'status' => true,
                    'message' => 'Transaction has been initiated, we will notify when your trip is booked',
                    'data' => $trip
                ];
            }
        }

        return [
            'status' => false,
            'message' => 'Trip could not be created try again',
            'data' => null
        ];
    }

    public function addExtraTime($validated, $trip_id)
    {
        $trip = $this->tripRepository->findById($trip_id);

        $trip_start_time_plus_buffer = Carbon::parse($trip->end_time)->addHour()->toDayDateTimeString();

        $next_reservation = $this->tripRepository->query()
            ->where('vehicle_id', $trip->vehicle_id)
            ->where('start_time', '>', $trip->end_time)
            ->whereIn('status', ['reserved', 'pending'])
            ->orderBy('start_time', 'asc')
            ->first();

        if ($next_reservation) {
            $time_between_current_and_next_reservation = calculateMinutesDifference($trip_start_time_plus_buffer, $next_reservation->start_time);
            $time_between_current_and_next_reservation_hour = $time_between_current_and_next_reservation / 60;
        } else {
            $time_between_current_and_next_reservation = PHP_INT_MAX; // No limit if there's no next reservation
            $time_between_current_and_next_reservation_hour = $time_between_current_and_next_reservation / 60;
        }

        $max_reservation_time = 4;
        $max_reservation_time_in_minutes = $max_reservation_time * 60;
        $minutes_to_be_added = (int) $validated->minutes;

        if ($minutes_to_be_added > $max_reservation_time_in_minutes - 1) {
            return [
                'status' => false,
                'message' => "Sorry the maximum extra time you can add is $max_reservation_time hour(s)",
                'data' => null
            ];
        }

        if ($time_between_current_and_next_reservation_hour == 0) {
            return [
                'status' => false,
                'message' => "You cannot extend or add extra time at this time",
                'data' => null
            ];
        }

        if ($time_between_current_and_next_reservation < $minutes_to_be_added) {
            return [
                'status' => false,
                'message' => "Please add time within {$time_between_current_and_next_reservation_hour} hour(s) range",
                'data' => null
            ];
        }

        $user = $this->userRepository->findById(auth()->id());
        $product = Product::all()->first();
        $subscribed = $user->subscribed($product->stripe_id);
        $settings = TripSetting::first();

        $extra_time_start_time = $trip->end_time;
        $extra_time_end_time = Carbon::parse($trip->end_time)->addMinutes($minutes_to_be_added)->toDateTimeString();

        $vehicle = $this->vehicleRepository->findById($trip->vehicle_id);
        $mins_difference = calculateMinutesDifference($extra_time_start_time, $extra_time_end_time);
        $price_per_minute = roundToWholeNumber(dollarToCent($vehicle->price_per_hour) / 60);
        $total_amount_before_tax = $mins_difference * $price_per_minute;
        $total_amount = $total_amount_before_tax + calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax);

        if ($subscribed) {
            $total_amount_before_tax = $minutes_to_be_added * roundToWholeNumber(dollarToCent(pricePerHourToPricePerMinute($settings->subscriber_price_per_hour)));
            $total_amount = $total_amount_before_tax + calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax);

            if ($user->subscription_balance < $total_amount) {
                $outstanding_after_subscription_balance_deduction_before_tax = $total_amount_before_tax - $user->subscription_balance;
                $outstanding_after_subscription_balance_deduction_after_tax = $outstanding_after_subscription_balance_deduction_before_tax + calculatePercentageOfValue($settings->tax_percentage, $outstanding_after_subscription_balance_deduction_before_tax);

                $trip = $this->tripRepository->update($trip_id, [
                    'end_time' => $extra_time_end_time,
                    'added_extra_time' => true
                ]);

                $payment = TripTransaction::create([
                    'trip_id' => $trip->id,
                    'building_id' => $trip->vehicle->building->id,
                    'vehicle_id' => $trip->vehicle->id,
                    'user_id' => $user->id,
                    'status' => TransactionStatusEnum::PENDING->value,
                    'reference' => generateReference(),
                    'public_id' => uuid(),
                    'amount' => $total_amount_before_tax,
                    'total_amount' => $total_amount_before_tax,
                    'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $outstanding_after_subscription_balance_deduction_before_tax),
                    'tax_percentage' => $settings->tax_percentage,
                    'start_time' => $extra_time_start_time,
                    'end_time' => $extra_time_end_time,
                    'rate' => $vehicle->price_per_hour,
                    'type' => TripTransactionTypeEnum::EXTRA_TIME->value
                ]);

                $transaction_one = $this->transactionRepository->create([
                    'user_id' => $user->id,
                    'amount' => $user->subscription_balance,
                    'total_amount' => $user->subscription_balance,
                    'title' => "Payment for trip",
                    'narration' => "Part payment of " . Number::currency(centToDollar($user->subscription_balance)) . " for trip " . $trip->booking_id,
                    'status' => TransactionStatusEnum::PENDING->value,
                    'type' => TransactionTypeEnum::TRIP->value,
                    'entry' => "debit",
                    'channel' => PaymentTypeEnum::SUBSCRIPTION->value,
                    'tax_amount' => 0.00,
                    'tax_percentage' => 0
                ]);

                $transaction_two = $this->transactionRepository->create([
                    'user_id' => $user->id,
                    'amount' => $outstanding_after_subscription_balance_deduction_before_tax,
                    'total_amount' => $outstanding_after_subscription_balance_deduction_after_tax,
                    'title' => "Payment for trip",
                    'narration' => "Part payment of " . Number::currency(centToDollar($outstanding_after_subscription_balance_deduction_after_tax)) . " for trip " . $trip->booking_id,
                    'status' => TransactionStatusEnum::PENDING->value,
                    'type' => TransactionTypeEnum::TRIP->value,
                    'entry' => "debit",
                    'channel' => PaymentTypeEnum::CARD->value,
                    'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $outstanding_after_subscription_balance_deduction_before_tax),
                    'tax_percentage' => $settings->tax_percentage
                ]);

                $payment->transactions()->saveMany([$transaction_one, $transaction_two]);

                $notification = new NotificationService($user);
                $notification->setBody("Extra time has been added")
                    ->setTitle('Extra time has been added to your trip and will be charged when the trip is over')
                    ->setUrl('http://google.com')
                    ->setType(NotificationTypeEnum::TRIP_BOOKED)
                    ->sendPushNotification()
                    ->sendInAppNotification();

                return [
                    'status' => true,
                    'message' => 'Time extended successfully',
                    'data' => $trip
                ];
            } else {
                $removed_charge = $user->update([
                    'subscription_balance' => $user->subscription_balance - $total_amount_before_tax,
                ]);

                if ($removed_charge) {
                    $trip = $this->tripRepository->update($trip_id, [
                        'end_time' => $extra_time_end_time,
                    ]);

                    $payment = TripTransaction::create([
                        'trip_id' => $trip->id,
                        'building_id' => $trip->vehicle->building->id,
                        'vehicle_id' => $trip->vehicle->id,
                        'user_id' => $user->id,
                        'reference' => generateReference(),
                        'public_id' => uuid(),
                        'status' => TransactionStatusEnum::SUCCESSFUL->value,
                        'amount' => $total_amount_before_tax,
                        'total_amount' => $total_amount_before_tax,
                        'tax_amount' => 0.00,
                        'tax_percentage' => 0,
                        'start_time' => $extra_time_start_time,
                        'end_time' => $extra_time_end_time,
                        'rate' => $settings->subscriber_price_per_hour,
                        'type' => TripTransactionTypeEnum::EXTRA_TIME->value
                    ]);

                    $transaction = $this->transactionRepository->create([
                        'user_id' => $user->id,
                        'amount' => $total_amount_before_tax,
                        'total_amount' => $total_amount_before_tax,
                        'title' => "Payment for trip",
                        'narration' => "Part payment of " . Number::currency(centToDollar($total_amount_before_tax)) . " for trip " . $trip->booking_id,
                        'status' => TransactionStatusEnum::SUCCESSFUL->value,
                        'type' => TransactionTypeEnum::TRIP->value,
                        'entry' => "debit",
                        'channel' => PaymentTypeEnum::SUBSCRIPTION->value,
                        'tax_amount' => 0.00,
                        'tax_percentage' => 0
                    ]);

                    $payment->transactions()->save($transaction);

                    $notification = new NotificationService($user);
                    $notification->setBody("Your time has been extended")
                        ->setTitle('Your Trip time has been extended successfully')
                        ->setUrl('http://google.com')
                        ->setType(NotificationTypeEnum::TRIP_BOOKED)
                        ->sendPushNotification()
                        ->sendInAppNotification();

                    return [
                        'status' => true,
                        'message' => "Your time has been extended successfully",
                        'data' => $trip
                    ];
                }
            }
        } else {
            $trip = $this->tripRepository->update($trip_id, [
                'end_time' => $extra_time_end_time,
                'added_extra_time' => true
            ]);

            $payment = TripTransaction::create([
                'trip_id' => $trip->id,
                'building_id' => $trip->vehicle->building->id,
                'vehicle_id' => $trip->vehicle->id,
                'user_id' => $user->id,
                'status' => TransactionStatusEnum::PENDING->value,
                'reference' => generateReference(),
                'public_id' => uuid(),
                'amount' => $total_amount_before_tax,
                'total_amount' => $total_amount,
                'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax),
                'tax_percentage' => $settings->tax_percentage,
                'start_time' => $extra_time_start_time,
                'end_time' => $extra_time_end_time,
                'rate' => $vehicle->price_per_hour,
                'type' => TripTransactionTypeEnum::EXTRA_TIME->value
            ]);

            $transaction = $this->transactionRepository->create([
                'user_id' => $user->id,
                'amount' => $total_amount_before_tax,
                'total_amount' => $total_amount,
                'title' => "Payment for trip",
                'narration' => "Part payment of " . Number::currency(centToDollar($total_amount)) . " for trip " . $trip->booking_id,
                'status' => TransactionStatusEnum::PENDING->value,
                'type' => TransactionTypeEnum::TRIP->value,
                'entry' => "debit",
                'channel' => PaymentTypeEnum::CARD->value,
                'tax_amount' => calculatePercentageOfValue($settings->tax_percentage, $total_amount_before_tax),
                'tax_percentage' => $settings->tax_percentage
            ]);

            $payment->transactions()->save($transaction);

            $notification = new NotificationService($user);
            $notification->setBody("Extra time has been added")
                ->setTitle('Extra time has been added to your trip and will be charged when the trip is over')
                ->setUrl('http://google.com')
                ->setType(NotificationTypeEnum::TRIP_BOOKED)
                ->sendPushNotification()
                ->sendInAppNotification();

            return [
                'status' => true,
                'message' => 'Extra time has been added to your trip and will be charged when the trip is over',
                'data' => $trip
            ];
        }

        return [
            'status' => false,
            'message' => 'Extra time could not be added',
            'data' => null
        ];
    }

    public function cancelTrip($trip_id)
    {

        try {
            $trip = $this->tripRepository->findById($trip_id);

            if (!$trip) {

                return [
                    'status' => false,
                    'message' => 'Trip not found',
                    'data' => null
                ];
            }

            // check if trip has started
            if ($trip->status !== TripStatusEnum::RESERVED->value) {
                return [
                    'status' => false,
                    'message' => "Trip $trip->status, you cannot cancel trip",
                    'data' => null
                ];
            }

            ProcessRefundJob::dispatch($trip);

            return [
                'status' => true,
                'message' => "Refund process has started successfully",
                'data' => null
            ];
        } catch (\Throwable $th) {
            logError($th->getMessage(), ['error' => $th]);

            return [
                'status' => false,
                'message' => 'Error cancelling trip',
                'data' => null
            ];
        }
    }

    public function userEndTrip($validated, $trip_id)
    {
        try {

            $trip = $this->tripRepository->findById($trip_id);

            if (!$trip) {
                return [
                    'status' => false,
                    'message' => 'Trip not found',
                    'data' => null
                ];
            }

            if (!$trip->started_at) {

                return [
                    'status' => false,
                    'message' => 'You have not started the trip',
                    'data' => null
                ];
            }

            if ($trip->ended_at) {
                return [
                    'status' => false,
                    'message' => 'Trip already ended',
                    'data' => null
                ];
            }


            if (!$trip->ended_at == TripStatusEnum::STARTED->value) {
                return [
                    'status' => false,
                    'message' => 'You cannot end this trip',
                    'data' => null
                ];
            }

            if (!isParkedAndCharging($trip->vehicle_id)) {
                return [
                    'status' => false,
                    'message' => 'Vehicle is not parked or not charging, please make sure EV is parked in the right place and charging before you can end trip',
                    'data' => null
                ];
            }

            if ($trip->added_extra_time) {
                ProcessExtraTimePaymentJob::dispatch($trip);
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

            return [
                'status' => true,
                'message' => 'Trip ended successfully',
                'data' => $trip
            ];
        } catch (\Throwable $th) {
            logError($th->getMessage(), ['error' => $th]);
            return respondError('Error starting trip, try again', null, 400);
        }
    }

    private function checkVehicleAvailability($data)
    {
        $start_time = $data->start_time;
        $end_time = $data->end_time;

        $vehicle_id = $data->vehicle_id;

        // Add 1 hour to both start time and end time to create the buffer
        $start_time_with_buffer = date('Y-m-d H:i:s', strtotime($start_time . ' -1 hour'));
        $end_time_with_buffer = date('Y-m-d H:i:s', strtotime($end_time . ' +1 hour'));

        $check = $this->tripRepository->query()

            ->where(function ($query) use ($start_time_with_buffer, $end_time_with_buffer, $vehicle_id) {
                $query->where(function ($query) use ($start_time_with_buffer, $vehicle_id) {
                    $query->where('start_time', '<=', $start_time_with_buffer)
                        ->where('end_time', '>=', $start_time_with_buffer)
                        ->where('vehicle_id', $vehicle_id)
                        ->whereIn('status', ['started', 'reserved', 'pending']);
                })->orWhere(function ($query) use ($vehicle_id, $end_time_with_buffer) {
                    $query->where('start_time', '<=', $end_time_with_buffer)
                        ->where('end_time', '>=', $end_time_with_buffer)
                        ->where('vehicle_id', $vehicle_id)
                        ->whereIn('status', ['started', 'reserved', 'pending']);
                });
            })
            ->orWhere(function ($query) use ($start_time_with_buffer, $end_time_with_buffer, $vehicle_id) {
                $query->where('start_time', '>=', $start_time_with_buffer)
                    ->where('end_time', '<=', $end_time_with_buffer)
                    ->where('vehicle_id', $vehicle_id)
                    ->whereIn('status', ['started', 'reserved', 'pending']);
            })
            ->exists();

        return !$check;
    }
}
