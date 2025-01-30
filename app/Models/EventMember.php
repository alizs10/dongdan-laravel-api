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
        'member_type'
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
        return $this->hasMany(Expense::class, 'transmitter_id', 'member_id');
    }

    public function expensesAsReceiver()
    {
        return $this->hasMany(Expense::class, 'receiver_id');
    }

    public function expensesAsContributor()
    {
        return $this->belongsToMany(Expense::class, 'expense_contributors', 'event_member_id', 'expense_id');
    }

    public function getTotalExpendsAttribute()
    {
        return $this->expensesAsPayer()
            ->where('type', 'expend')
            ->sum('amount');
    }

    public function getTotalTransfersAttribute()
    {
        return $this->expensesAsPayer()
            ->where('type', 'transfer')
            ->sum('amount');
    }
}
