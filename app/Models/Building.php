<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use HasFactory;
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name', // The name of the building
        'public_id', // The public identifier of the building
        'address', // The address of the building
        'opening_time', // The opening time of the building
        'closing_time', // The closing time of the building
        'status', // The status of the building (e.g., active, inactive)
        'image', // The image URL or path of the building

        'description', // A brief description or summary of the building
        'type', // The type or category of the building (e.g., residential, commercial, industrial)
        'floor_count', // The number of floors or levels in the building
        'built_year', // The year when the building was constructed
        'contact_person', // The name of the contact person associated with the building
        'contact_email', // The email address of the contact person
        'contact_phone', // The phone number of the contact person
        'latitude', // The latitude coordinate of the building location
        'longitude', // The longitude coordinate of the building location
        'construction_material', // The primary material used in the construction of the building
        'architect', // The name of the architect or architectural firm responsible for designing the building
        'construction_company', // The name of the construction company that built the building
        'maintenance_company', // The name of the company responsible for building maintenance
        'security_level', // The level of security measures implemented in the building
        'insurance_policy_number', // The policy number of the insurance covering the building
        'last_inspection_date', // The date of the last inspection conducted on the building
    ];


    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
