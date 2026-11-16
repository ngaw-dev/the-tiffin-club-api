<?php

namespace App\Models;

use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    /** @use HasFactory<MenuFactory> */
    use HasFactory;

    protected $fillable = ['menu_date', 'published_at', 'cutoff_time'];

    protected $casts = [
        'menu_date' => 'date',
        'published_at' => 'datetime',
        'cutoff_time' => 'datetime:H:i',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
