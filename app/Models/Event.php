<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'start_date',
        'end_date',
        'label',
        'user_id'
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->slug = static::generateUniqueSlug($model->name);
        });
        static::updating(function ($model) {
            if ($model->isDirty('name')) {
                $model->slug = static::generateUniqueSlug($model->name, $model->id);
            }
        });
    }

    public static function generateUniqueSlug($name, $excludeId = null)
    {
        $slug = Str::slug($name, '-', null);
        $originalSlug = $slug;
        $counter = 1;
        while (static::where('slug', $slug)->when($excludeId, function ($query, $excludeId) {
            return $query->where('id', '!=', $excludeId);
        })->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        return $slug;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(EventMember::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function getExpendsCountAttribute()
    {
        return $this->expenses()->where('type', 'expend')->count();
    }

    public function getTransfersCountAttribute()
    {
        return $this->expenses()->where('type', 'transfer')->count();
    }

    public function getTotalAmountAttribute()
    {
        return $this->expenses()->sum('amount');
    }

    public function getMaxExpendAmountAttribute()
    {
        return $this->expenses()
            ->where('type', 'expend')
            ->max('amount') ?? 0;
    }

    public function getMaxTransferAmountAttribute()
    {
        return $this->expenses()
            ->where('type', 'transfer')
            ->max('amount') ?? 0;
    }

    public function getMemberWithMostExpendsAttribute()
    {
        return $this->members()
            ->withSum('expensesAsPayer', 'amount')
            ->orderByDesc('expenses_as_payer_sum_amount')
            ->first();
    }

    public function getMemberWithMostTransfersAttribute()
    {
        return $this->members()
            ->withSum('expensesAsTransmitter', 'amount')
            ->orderByDesc('expenses_as_transmitter_sum_amount')
            ->first();
    }

    public function getTreasurerAttribute()
    {
        $member = $this->members()
            ->get()
            ->sortByDesc(function ($member) {
                return $member->total_expends_amount + $member->total_sent_amount;
            })
            ->first();

        if (!$member) {
            return null;
        }

        Log::debug($member->total_expends_amount);
        Log::debug($member->total_sent_amount);

        return [
            'member' => $member,
            'amount' => $member->total_expends_amount + $member->total_sent_amount
        ];
    }
}
