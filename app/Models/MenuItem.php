<?php

namespace App\Models;

use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    public const TYPE_MAIN = 'main';

    public const TYPE_EXTRA = 'extra';

    protected $fillable = ['menu_id', 'type', 'name', 'description', 'image_path', 'price', 'sort_order'];

    protected $casts = [
        'price' => 'integer',
        'sort_order' => 'integer',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
