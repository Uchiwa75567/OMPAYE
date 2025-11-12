<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'montant',
        'type',
        'statut',
        'compte_source_id',
        'compte_dest_id',
        'marchand_id',
        'reference',
        'frais',
    ];

    protected $casts = [
        'montant' => 'integer',
        'frais' => 'integer',
    ];

    public function compteSource()
    {
        return $this->belongsTo(Compte::class, 'compte_source_id');
    }

    public function compteDest()
    {
        return $this->belongsTo(Compte::class, 'compte_dest_id');
    }

    public function marchand()
    {
        return $this->belongsTo(User::class, 'marchand_id');
    }
}