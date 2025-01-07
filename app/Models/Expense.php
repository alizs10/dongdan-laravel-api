<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'description',
        'amount',
        'type',
        'payer_id',
        'transmitter_id',
        'receiver_id',
        'date'
    ];


    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function payer()
    {
        return $this->belongsTo(EventMember::class, 'payer_id');
    }
    public function contributors()
    {
        return $this->hasMany(ExpenseContributor::class);
    }

    public function transmitter()
    {
        return $this->belongsTo(EventMember::class, 'transmitter_id');
    }

    public function receiver()
    {
        return $this->belongsTo(EventMember::class, 'receiver_id');
    }
}
