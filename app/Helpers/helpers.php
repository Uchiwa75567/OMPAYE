<?php

use Illuminate\Support\Str;

if (!function_exists('format_phone')) {
    function format_phone($phone)
    {
        // Format +221 77 001 122
        return '+221 ' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 3) . ' ' . substr($phone, 5, 3);
    }
}

if (!function_exists('mask_balance')) {
    function mask_balance($balance)
    {
        // Masquer le solde, afficher seulement les 2 derniers chiffres
        $balance = $balance / 100; // Convertir en FCFA
        $str = (string) $balance;
        return str_repeat('*', strlen($str) - 2) . substr($str, -2);
    }
}

if (!function_exists('generate_qr_code')) {
    function generate_qr_code()
    {
        return 'OM-' . strtoupper(Str::random(8));
    }
}

if (!function_exists('generate_reference')) {
    function generate_reference()
    {
        return 'TXN-' . strtoupper(Str::random(10));
    }
}