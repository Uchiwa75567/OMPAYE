<?php

namespace App\Enums;

enum TypeTransaction: string
{
    case DEPOT = 'depot';
    case RETRAIT = 'retrait';
    case TRANSFERT = 'transfert';
    case PAIEMENT = 'paiement';
}