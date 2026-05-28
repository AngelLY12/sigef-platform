<?php

namespace App\Models;

use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserStatus;
use App\Jobs\SendMailJob;
use App\Mail\SendPasswordResetLinkMail;
use App\Mail\SendVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\traits\ResolvesTargetUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;

/**
 * @method bool hasRole(string|array $roles)
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, ResolvesTargetUser, LogsActivity, MustVerifyEmailTrait;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'phone_number',
        'birthdate',
        'gender',
        'curp',
        'address',
        'password',
        'stripe_customer_id',
        'blood_type',
        'registration_date',
        'status',
        'mark_as_deleted_at'
    ];


    public function paymentConcepts(){
        return $this->belongsToMany(PaymentConcept::class);
    }

    public function payments(){
        return $this->hasMany(Payment::class);
    }

    public function paymentEvents()
    {
        return $this->hasMany(PaymentEvent::class);
    }

    public function refreshTokens()
    {
        return $this->hasMany(RefreshToken::class);

    }

    public function currentRefreshToken(): ?RefreshToken
    {
        return $this->refreshTokens()
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();
    }

    public function paymentMethods(){
        return $this->hasMany(PaymentMethod::class);
    }

    public function studentDetail(){
        return $this->hasOne(StudentDetail::class);
    }

    public function children()
    {
        return $this->hasMany(ParentStudent::class, 'parent_id');
    }

    public function parents()
    {
        return $this->hasMany(ParentStudent::class, 'student_id');
    }

    public function invitesAsStudent()
    {
        return $this->hasMany(ParentInvite::class, 'student_id');
    }

    public function invitesCreated()
    {
        return $this->hasMany(ParentInvite::class, 'created_by');
    }

    public function sendEmailVerificationNotification(): void
    {
        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $this->getKey(), 'hash' => sha1($this->getEmailForVerification())]
        );

        SendMailJob::dispatch(
            new SendVerifyEmail($this, $verifyUrl),
            $this->email,
            'email_verification'
        )->onQueue('emails');

    }
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $resetUrl=config('app.frontend_url')."/password-reset/$token?email={$this->getEmailForPasswordReset()}";
        SendMailJob::dispatch(
            new SendPasswordResetLinkMail($this,$resetUrl),
            $this->email,
            'password_reset')
        ->onQueue('emails');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'last_name' ,'email', 'status'])
            ->logOnlyDirty()
            ->useLogName('user');
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
            'birthdate' => 'date',
            'registration_date' => 'date',
            'address' => 'array',
            'email_verified_at' => 'datetime',
            'mark_as_deleted_at' => 'datetime',
            'password' => 'hashed',
            'gender' => UserGender::class,
            'blood_type' => UserBloodType::class,
            'status' => UserStatus::class
        ];
    }



}
