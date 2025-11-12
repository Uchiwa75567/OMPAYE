<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class QrCode extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'marchand_id',
        'montant',
        'code',
        'statut',
        'expires_at',
    ];

    protected $casts = [
        'montant' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function marchand()
    {
        return $this->belongsTo(User::class, 'marchand_id');
    }
}