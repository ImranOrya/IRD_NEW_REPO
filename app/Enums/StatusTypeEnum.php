<?php

namespace App\Enums;

enum StatusTypeEnum: int
{
    case active = 1;
    case blocked = 2;
    case unregistered = 3;
    case not_logged_in = 4;
    case in_progress = 5;
}
