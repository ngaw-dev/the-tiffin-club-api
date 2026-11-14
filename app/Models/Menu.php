<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    /** @use HasFactory<\Database\Factories\MenuFactory> */
    use HasFactory;

    protected $casts = [
        'menu_date' => 'date',
        'published_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }
}
