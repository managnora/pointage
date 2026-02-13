<?php

namespace App\Enum;

enum StatusEnum: string
{
    case ALERT = 'alert';
    case WARNING = 'warning';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
}

