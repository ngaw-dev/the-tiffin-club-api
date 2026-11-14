<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'suburb',
    ];

    /**
     * Normalize identifiers on set: email lowercase, phone digits-only.
     */
    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value !== null && $value !== '' ? strtolower($value) : null;
    }

    public function setPhoneAttribute(?string $value): void
    {
        $digits = $value !== null && $value !== '' ? preg_replace('/\D+/', '', $value) : '';
        $this->attributes['phone'] = $digits !== '' ? $digits : null;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
