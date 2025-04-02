<?php

namespace App\Models;

use App\Notifications\ResetPasswordLinkPersian;
use App\Notifications\VerifyEmailPersian;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
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

    public function verificationUrl()
    {
        $expires = now()->addMinutes(60)->timestamp;
        $signature = hash_hmac('sha256', $this->id . $this->email . $expires, config('app.key'));

        $verificationUrl = "http://localhost:3000/auth/verify-email?" . http_build_query([
            'id' => $this->id,
            'hash' => sha1($this->email),
            'signature' => $signature,
            'expires' => $expires
        ]);

        return $verificationUrl;
    }
    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailPersian());
    }

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function sendPasswordResetNotification($token): void
    {
        $url = 'http://localhost:3000/auth/reset-password?token=' . $token . '&email=' . $this->email;
        $this->notify(new ResetPasswordLinkPersian($url));
    }


    // personal expense manager

    public function transactions()
    {
        return $this->hasMany(PersonalTransaction::class, 'user_id');
    }

    public function savingsGoals()
    {
        return $this->hasMany(PersonalSavingsGoal::class, 'user_id');
    }

    public function categories()
    {
        return $this->hasMany(PersonalCategory::class, 'user_id');
    }
}
