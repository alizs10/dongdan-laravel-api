<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'scheme',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function eventMemberships()
    {
        return $this->morphMany(EventMember::class, 'member');
    }

    public function expensesAsPayer()
    {
        return $this->morphMany(Expense::class, 'payer');
    }
    public function expensesAsTo()
    {
        return $this->morphMany(Expense::class, 'to');
    }
    public function expensesAsFrom()
    {
        return $this->morphMany(Expense::class, 'from');
    }

    public function expensesPaidFor()
    {
        return $this->morphToMany(Expense::class, 'member', 'expense_members');
    }
}
