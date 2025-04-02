<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalSavingsGoal extends Model
{
    protected $table = 'personal_savings_goals'; // Explicit table name

    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'due_date',
    ];

    protected $casts = [
        'target_amount' => 'float',
        'due_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
