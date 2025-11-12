<?php

namespace App\Enums;

enum StatutTransaction: string
{
    case ENVOYE = 'envoye';
    case ECHEC = 'echec';
    case ANNULE = 'annule';
    case EN_COURS = 'en_cours';
}