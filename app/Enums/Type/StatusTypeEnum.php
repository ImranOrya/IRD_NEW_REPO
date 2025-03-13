<?php

namespace App\Enums\Type;

enum StatusTypeEnum: int
{
    case register_form_not_completed = 1;
    case register_form_completed = 2;
    case signed_register_form_submitted = 3;
    case registered = 4;
    case blocked = 5;
    case registration_expired = 6;
    case registration_extended = 7;
}
