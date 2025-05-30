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
        // 'transaction_id', // Added field for associated transaction
        // 'status', // e.g., boolean for completed or not
    ];

    protected $casts = [
        'target_amount' => 'float',
        'due_date' => 'date',
        'status' => 'boolean', // Assuming status is a boolean
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaction()
    {
        return $this->hasOne(PersonalTransaction::class, 'savings_goal_id');
    }
}
