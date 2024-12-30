<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = ['show_as_me'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
