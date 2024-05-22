<?php

use App\Enum\TransactionStatusEnum;
use App\Enum\TripStatusEnum;
use App\Http\Resources\Core\PaginateResource;
use App\Models\Trip;
use App\Models\TripTransaction;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


if (!function_exists('ulid')) {
    function ulid(): string
    {
        return Str::ulid();
    }
}

if (!function_exists('generateRandomString')) {
    function generateRandomString($length = 10): string
    {
        $characters = '01234567890123456789abcdefghijklmnop' . ulid() . 'qrstuvwxyz0987654321ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters_length = strlen($characters);
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $characters_length - 1)];
        }

        return strtoupper($random_string);
    }
}

if (!function_exists('generateRandomNumber')) {
    function generateRandomNumber($length = 6): ?int
    {
        if ($length <= 0) {
            return null; // Handle invalid input
        }

        $min = pow(10, ($length - 1)); // Minimum value based on length
        $max = pow(10, $length) - 1;    // Maximum value based on length

        return mt_rand($min, $max); // Generate a random number within the specified range
    }
}

if (!function_exists('generateCode')) {
    function generateCode($string = null, $length = 6): string
    {
        srand((float) microtime() * 10000000000);
        $rand = mt_rand(12, 12);
        $characters = strtoupper("{$string}012345678998765432100011223344556677889900998877665544332211{$rand}");

        return substr(
            str_shuffle($characters),
            0,
            $length
        );
    }
}

if (!function_exists('logInfo')) {
    function logInfo(string $message, array $context = [], ?User $user = null): bool
    {
        if ($user) {
            $context['user'] = $user;
        }
        Log::info($message, $context);

        return true;
    }
}
if (!function_exists('logError')) {
    function logError(string $message, array $context = [], ?User $user = null): bool
    {
        if ($user) {
            $context['user'] = $user;
        }
        Log::error($message, $context);

        return true;
    }
}
if (!function_exists('logCritical')) {
    function logCritical(string $message, array $context = [], ?User $user = null): bool
    {
        if ($user) {
            $context['user'] = $user;
        }
        Log::critical($message, $context);

        return true;
    }
}

if (!function_exists('setFullPhoneNumber')) {
    function setFullPhoneNumber($phone, $country = 'NGN')
    {
        if ($country === 'NGN') {
            $full_phone_length = 11;
            $phone_number_edited = $phone[0] === '0' && strlen($phone) === $full_phone_length ? $phone : '0' . $phone;
            $format = str_replace(' ', '', $phone_number_edited);

            return $format;
        }
        $format = str_replace(' ', '', $phone);

        return $phone;
    }
}

if (!function_exists('trimPhone')) {
    function trimPhone($phone, $country = 'NGN')
    {
        if ($country === 'NGN') {
            $trim = substr($phone, 1);
            $phone_number_edited = $phone[0] === '0' ? $trim : $phone;
            $format = str_replace(' ', '', $phone_number_edited);

            return $format;
        }

        return $phone;
    }
}

if (!function_exists('randomString')) {
    function randomString($length = 7)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ9876543210abcdefghijklmnopqrstuvwxyz0011223344556677889';
        $characters_length = strlen($characters);
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $characters_length - 1)];
        }

        return strtoupper($random_string);
    }
}

if (!function_exists('hashData')) {
    function hashData($data, $type = 'string')
    {
        if ($type == 'array') {
            $data = json_encode($data);
        }

        return Hash::make($data);
    }
}
if (!function_exists('hashCheck')) {
    function hashCheck($incoming_data, $current_data, $type = 'string'): bool
    {
        if ($type == 'array') {
            $incoming_data = json_encode($incoming_data);
        }

        return Hash::check($incoming_data, $current_data);
    }
}

if (!function_exists('isUuid')) {
    function isUuid(string $uuid): bool
    {
        try {
            return $uuid && preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $uuid);
        } catch (Throwable $exception) {
            return false;
        }
    }
}

if (!function_exists('uuid')) {
    function uuid()
    {
        return Str::uuid()->toString();
    }
}

if (!function_exists('slug')) {
    function slug($string, $separator = '_'): string
    {
        return Str::slug($string, $separator);
    }
}

if (!function_exists('encryption')) {
    function encryption(string $string): ?string
    {
        try {
            return Crypt::encryptString($string);
        } catch (DecryptException $e) {
            logError('Could not encrypt value');

            return null;
        }
    }
}

if (!function_exists('decryption')) {
    function decryption(?string $encrypted_value = null): ?string
    {
        try {
            return Crypt::decryptString($encrypted_value);
        } catch (DecryptException $e) {
            logError('Could not decrypt value');

            return null;
        }
    }
}

if (!function_exists('generateReference')) {
    function generateReference($length = 57, $type = 'mixed'): string
    {
        srand((float) microtime() * 10000000000);
        $rand = mt_rand(1131671141, 8999992121);
        $characters = $type == 'mixed'
            ? strtoupper(str_replace('-', '', uuid()) . ulid() . $rand)
            : ulid() . $rand;

        return substr(
            str_shuffle($characters),
            0,
            $length
        );
    }
}


if (!function_exists('paginateResource')) {
    function paginateResource($data, $resource)
    {
        return PaginateResource::make($data, $resource);
    }
}

if (!function_exists('dollarToCent')) {
    function dollarToCent($amount)
    {
        return $amount * 100;
    }
}

if (!function_exists('centToDollar')) {
    function centToDollar($amount)
    {
        return $amount / 100;
    }
}



if (!function_exists('calculateMinutesDifference')) {
    function calculateMinutesDifference($startTime, $endTime)
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        return  roundToWholeNumber($start->diffInMinutes($end));
    }
}
if (!function_exists('calculatePercentageOfValue')) {
    function calculatePercentageOfValue($percentage, $value)
    {
        if ($percentage <= 0 || $value <= 0) {
            return 0;
        }
        $p = ($percentage / 100) * $value;

        return roundToWholeNumber($p);
    }
}

if (!function_exists('pricePerHourToPricePerMinute')) {
    function pricePerHourToPricePerMinute($price)
    {
        return $price / 60;
    }
}

if (!function_exists('roundToWholeNumber')) {
    /**
     * Round a number to the nearest whole number.
     *
     * @param  float  $number
     * @return int
     */
    function roundToWholeNumber($number)
    {
        return (int) round($number);
    }
}

if (!function_exists('updateTripStatus')) {

    function updateTripStatus($trip, $payment, TripStatusEnum $trip_status, TransactionStatusEnum $transaction_status)
    {
        try {
            $trip->update([
                'status' => $trip_status->value
            ]);

            $trip_transaction = TripTransaction::find($payment->id);

            if ($trip_transaction) {
                $trip_transaction->update([
                    'status' => $transaction_status->value
                ]);


                foreach ($trip_transaction->transactions as $key => $transaction) {
                    $transaction->update([
                        'status' => $transaction_status->value
                    ]);
                }
            }

            return true;
        } catch (\Throwable $th) {
            logError("Error occurred @ updateTripStatus method in TripService", [
                'error' => $th
            ]);

            return false;
        }
    }


    if (!function_exists('isEndTimeGreaterThanStartTime')) {

        function isEndTimeGreaterThanStartTime($start_time, $end_time)
        {
            $start = Carbon::parse($start_time);
            $end = Carbon::parse($end_time);

            return $end->gt($start);
        }
    }


    if (!function_exists('calculateTimeUsed')) {

        function calculateTimeUsed($start_time)
        {

            $start = Carbon::parse($start_time);
            $now = Carbon::now();
            if ($now < $start) {
                return '00:00:00';
            }
            $interval = $start->diff($now);

            $hours = $interval->days * 24 + $interval->h;
            $minutes = $interval->i;
            $seconds = $interval->s;

            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }
    }

    if (!function_exists('calculatePercentageOfTimeUsed')) {

        function calculatePercentageOfTimeUsed($start_time, $end_time)
        {
            $start = Carbon::parse($start_time);
            $end = Carbon::parse($end_time);
            $now = Carbon::now();

            if ($end < $start || $now < $start) {
                return 0;
            }

            $total_minutes = $start->diffInMinutes($end);
            $used_minutes = $start->diffInMinutes($now);

            if ($total_minutes == 0) {
                return 0;
            }

            $percentage_used = ($used_minutes / $total_minutes) * 100;
            return $percentage_used;
        }
    }
}
