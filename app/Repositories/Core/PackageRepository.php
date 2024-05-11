<?php

namespace App\Repositories\Core;

use App\Enum\SubscriptionPaymentFrequencyEnum;
use App\Models\Package;
use App\Models\PackageUser;
use Carbon\Carbon;

class PackageRepository
{
    public function all()
    {
        return Package::all();
    }

    public function findById($id)
    {
        return Package::find($id);
    }

    public function create(array $data)
    {
        $package = new Package();

        $package->public_id = uuid();
        $package->title = $data['title'] ?? null;
        $package->description = $data['description'] ?? null;
        $package->amount = $data['amount'] ?? null;
        $package->frequency = $data['frequency'] ?? SubscriptionPaymentFrequencyEnum::YEAR->value;
        $package->status = $data['status'] ?? null;
        $package->active = $data['active'] ?? false;
        $package->hours = $data['hours'] ?? null;

        $package->save();

        return $package;
    }

    public function update($id, array $data)
    {
        $package = Package::find($id);

        $package->title = $data['title'] ?? $package->title;
        $package->description = $data['description'] ?? $package->description;
        $package->amount = $data['amount'] ?? $package->amount;
        $package->frequency = $data['frequency'] ?? $package->frequency;
        $package->status = $data['status'] ?? $package->status;
        $package->active = $data['active'] ?? $package->active;
        $package->hours = $data['hours'] ?? $package->hours;

        $package->save();

        return $package;
    }

    public function delete($id)
    {
        return Package::destroy($id);
    }



    public function getSubscriptionsDueInTwoWeeks()
    {
        $two_weeks_later = Carbon::now()->addWeeks(2);

        return PackageUser::with(['package'])
            ->whereDate('due_at', $two_weeks_later->toDateString())
            ->whereNull('unsubscribed_at')
            ->get();
    }

    public function getSubscriptionsDueInOneDay()
    {
        $one_day_later = Carbon::now()->addDay();

        return PackageUser::with(['package'])
            ->whereDate('due_at', $one_day_later->toDateString())
            ->whereNull('unsubscribed_at')
            ->get();
    }



    public function getAllExpiredSubscriptions()
    {
        return PackageUser::with(['package'])
            ->where('due_at', '<', now())
            ->get();
    }
}
