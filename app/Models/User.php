<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'prenom',
        'cni',
        'telephone',
        'sexe',
        'password',
        'pin',
        'role',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'pin' => 'hashed',
    ];

    public function compte()
    {
        return $this->hasOne(Compte::class);
    }

    public function marchandCode()
    {
        return $this->hasOne(MarchandCode::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'marchand_id');
    }

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class, 'marchand_id');
    }
}
