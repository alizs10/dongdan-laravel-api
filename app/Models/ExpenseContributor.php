<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseContributor extends Model
{
    protected $fillable = [
        'expense_id',
        'event_member_id',
        'amount'
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function eventMember()
    {
        return $this->belongsTo(EventMember::class, 'event_member_id');
    }
}
