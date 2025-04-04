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

    public function categories()
    {
        return $this->belongsToMany(PersonalCategory::class, 'personal_transaction_category', 'transaction_id', 'category_id');
    }
}
