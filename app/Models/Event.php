<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
}
