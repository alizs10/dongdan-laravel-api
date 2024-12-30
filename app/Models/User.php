<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'scheme',
    ];

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }


    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
