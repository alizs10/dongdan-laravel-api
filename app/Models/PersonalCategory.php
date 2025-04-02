<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalCategory extends Model
{
    protected $table = 'personal_categories'; // Explicit table name

    protected $fillable = [
        'user_id',
        'name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(PersonalTransaction::class, 'category_id');
    }
}
