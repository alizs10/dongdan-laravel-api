<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;

class EventMember extends Model
{
    protected $fillable = [
        'name',
        'email',
        'scheme',
        'member_id',
        'member_type',
        'avatar'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function member(): MorphTo
    {
        return $this->morphTo();
    }

    public function expensesAsPayer()
    {
        return $this->hasMany(Expense::class, 'payer_id');
    }

    public function expensesAsTransmitter()
    {
        return $this->hasMany(Expense::class, 'transmitter_id');
    }

    public function expensesAsReceiver()
    {
        return $this->hasMany(Expense::class, 'receiver_id');
    }

    public function expensesAsContributor()
    {
        return $this->belongsToMany(Expense::class, 'expense_contributors', 'event_member_id', 'expense_id');
    }

    public function getTotalExpendsAmountAttribute()
    {
        return $this->expensesAsPayer()
            ->sum('amount');
    }


    public function getTotalContributionsAmountAttribute()
    {
        return $this->expensesAsContributor()
            ->withPivot('amount')
            ->get()
            ->sum('pivot.amount');
    }

    public function getTotalSentAmountAttribute()
    {
        return $this->expensesAsTransmitter()->sum('amount');
    }

    public function getTotalReceivedAmountAttribute()
    {
        return $this->expensesAsReceiver()->sum('amount');
    }

    public function getBalanceAttribute()
    {
        $paid = $this->total_expends_amount;
        $contributions = $this->total_contributions_amount;
        $sent = $this->total_sent_amount;
        $received = $this->total_received_amount;

        return ($paid + $sent) - ($contributions + $received);
    }

    public function getBalanceStatusAttribute()
    {
        $balance = $this->balance;
        if ($balance > 0) {
            return 'creditor';
        } elseif ($balance < 0) {
            return 'debtor';
        }
        return 'settled';
    }
}
