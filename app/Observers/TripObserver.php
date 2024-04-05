<?php

namespace App\Observers;

use App\Models\Trip;
use App\Models\TripMetaData;
use App\Models\TripTransaction;
use App\Repositories\Core\TransactionRepository;

class TripObserver
{
    public function __construct(protected TransactionRepository $transactionRepository)
    {
    }
    /**
     * Handle the Trip "created" event.
     */
    public function created(Trip $trip): void
    {

        if (!$trip->parent_trip_id) {
            $trip->tripMetaData()->save(new TripMetaData([
                'public_id' => uuid()
            ]));
        }
    }

    /**
     * Handle the Trip "updated" event.
     */
    public function updated(Trip $trip): void
    {
        //
    }

    /**
     * Handle the Trip "deleted" event.
     */
    public function deleted(Trip $trip): void
    {
        //
    }

    /**
     * Handle the Trip "restored" event.
     */
    public function restored(Trip $trip): void
    {
        //
    }

    /**
     * Handle the Trip "force deleted" event.
     */
    public function forceDeleted(Trip $trip): void
    {
        //
    }
}
