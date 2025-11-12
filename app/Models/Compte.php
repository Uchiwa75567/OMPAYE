<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Compte extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'solde',
        'user_id',
        'type',
    ];

    protected $casts = [
        'solde' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionsSource()
    {
        return $this->hasMany(Transaction::class, 'compte_source_id');
    }

    public function transactionsDest()
    {
        return $this->hasMany(Transaction::class, 'compte_dest_id');
    }
}