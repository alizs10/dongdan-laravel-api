<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalTransaction extends Model
{
    protected $table = 'personal_transactions'; // Explicit table name

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'date',
        'title',
        'description',
        'category_id',
        'is_recurring',
        'frequency',
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'amount' => 'float',
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(PersonalCategory::class, 'category_id');
    }
}
