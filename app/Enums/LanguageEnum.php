<?php

namespace App\Enums;

enum LanguageEnum: string
{
    case farsi = "fa";
    case pashto = "ps";
    case default = "en";
    // Define the static array as a constant
    public const LANGUAGES = [
        'ps' => 'pashto',
        'fa' => 'farsi',
        'en' => 'english'
    ];
}
