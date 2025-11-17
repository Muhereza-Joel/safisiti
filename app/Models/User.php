<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'organisation_id',
        'name',
        'email',
        'password',
        'is_active',
        'is_suspended',
        'suspended_until',
        'suspension_reason',
        'last_login_at',
    ];

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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_suspended' => 'boolean',
            'suspended_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }

            // Set default password if empty
            if (empty($model->password)) {
                $model->password = Hash::make('12345678');
            }
        });
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }


    /**
     * Register media collections for the user
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('avatars') // you can name it "profile_pictures"
            ->singleFile(); // ensures only 1 profile picture exists
    }

    /**
     * Example: Add conversions (thumbnail, etc.)
     */
    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this
            ->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10);
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // Allowed Roles To Access Panel
        return $this->hasRole(['super_admin', 'System Administrator', 'Organisation Administrator', 'Health Inspector']);
    }
}
