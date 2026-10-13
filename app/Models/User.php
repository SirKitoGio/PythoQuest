<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Mirrors the column default so a just-registered user has a role before reloading.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'role' => 'researcher',
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
            'role' => UserRole::class,
        ];
    }

    public function isReviewer(): bool
    {
        return $this->role->canReview();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function sightings(): HasMany
    {
        return $this->hasMany(Sighting::class);
    }

    public function identifications(): HasMany
    {
        return $this->hasMany(Identification::class);
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(SightingConfirmation::class);
    }

    public function searchHistories(): HasMany
    {
        return $this->hasMany(SearchHistory::class);
    }

    public function favoritePlants(): BelongsToMany
    {
        return $this->belongsToMany(Plant::class, 'favorites')->withTimestamps();
    }
}
