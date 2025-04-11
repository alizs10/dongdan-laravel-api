<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalLimit extends Model
{
    protected $table = 'personal_limits'; // Explicit table name

    protected $fillable = ['user_id', 'category_id', 'name', 'amount', 'period'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PersonalCategory::class)->withDefault();
    }
}
