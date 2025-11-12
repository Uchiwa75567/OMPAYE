<?php

namespace App\Enums;

enum Role: string
{
    case CLIENT = 'client';
    case ADMIN = 'admin';
    case MARCHAND = 'marchand';
    case DISTRIBUTEUR = 'distributeur';
}