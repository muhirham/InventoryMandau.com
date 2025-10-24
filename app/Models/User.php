<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Boleh di-mass assign (buat store dari form)
     */
    protected $fillable = [
        'name',        // atau full_name kalau kamu pakai itu di DB
        'username',
        'email',
        'phone_number',
        'role',
        'password', // <-- penting untuk kolom ROLE
    ];

    /**
     * Hidden saat serialize
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Cast kolom
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'password'          => 'hashed',
    ];

    /**
     * Field virtual yang mau ikut tampil kalau diakses sebagai array/json
     * (opsional, tapi enak dipakai di Blade juga)
     */
    protected $appends = [
        'created_date',
        'updated_human',
        'status_text',
        'status_badge_class',
    ];

    // ===== Accessors nyaman untuk Blade =====

    public function getCreatedDateAttribute(): ?string
    {
        return $this->created_at?->format('Y-m-d');
    }

    public function getUpdatedHumanAttribute(): ?string
    {
        return $this->updated_at?->diffForHumans();
    }

    public function getStatusTextAttribute(): string
    {
        return $this->email_verified_at ? 'Active' : 'Inactive';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->email_verified_at ? 'bg-label-success' : 'bg-label-secondary';
    }

    // ===== (Opsional) Scope buat filter cepat =====
    public function scopeRole($q, ?string $role)
    {
        if ($role) $q->where('role', $role);
        return $q;
    }
}